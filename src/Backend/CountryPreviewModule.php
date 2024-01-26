<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\Backend;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Intl\Countries;
use Contao\Input;
use Contao\MaintenanceModuleInterface;
use Contao\SelectMenu;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class CountryPreviewModule implements MaintenanceModuleInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly Countries $countries,
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
        private readonly array $supportedTables,
    ) {
    }

    public function isActive(): bool
    {
        return false;
    }

    public function run(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $countries = $this->getUsedCountries();

        if (empty($countries) || !$request) {
            return '';
        }

        $session = $request->getSession();
        $currentCountry = $session->get(CountryProvider::SESSION_KEY);
        $widget = $this->generateWidget($countries, $currentCountry);

        if ('geoip2_switch' === Input::post('FORM_SUBMIT')) {
            $widget->validate();

            if (!$widget->hasErrors()) {
                if (empty($widget->value)) {
                    $session->remove(CountryProvider::SESSION_KEY);
                } else {
                    $session->set(CountryProvider::SESSION_KEY, (string) $widget->value);
                }

                Controller::reload();
            }
        }

        $template = new BackendTemplate('be_geoip2_switch');
        $template->requestToken = $this->csrfTokenManager->getDefaultTokenValue();
        $template->widget = $widget;

        return $template->parse();
    }

    private function getUsedCountries(): array
    {
        $queries = [];

        foreach ($this->supportedTables as $table) {
            $queries[] = "SELECT geoip_countries FROM $table WHERE geoip_visibility='show' OR geoip_visibility='hide'";
        }

        $countries = $this->connection->executeQuery(
            'SELECT GROUP_CONCAT(geoip_countries) FROM ('.implode(' UNION ', $queries).') AS result',
        )->fetchOne();

        if (!$countries) {
            return [];
        }

        return array_values(array_filter(array_unique(explode(',', (string) $countries))));
    }

    private function generateWidget(array $countries, string|null $current): Widget
    {
        $widget = new SelectMenu();
        $widget->id = 'country';
        $widget->name = 'country';
        $widget->label = $this->translator->trans('tl_maintenance.geoip2_country.0', [], 'contao_tl_maintenance');

        $countryNames = $this->countries->getCountries();
        $options = [];

        foreach ($countries as $country) {
            $options[] = [
                'value' => $country,
                'label' => $countryNames[$country],
                'default' => $country === $current,
            ];
        }

        usort(
            $options,
            static fn ($option1, $option2) => strcmp($option1['label'], $option2['label']),
        );

        array_unshift($options, ['value' => '', 'label' => '-', 'default' => null === $current]);

        $options[] = [
            'value' => 'XX',
            'label' => $this->translator->trans('tl_maintenance.geoip2_unknown', [], 'contao_tl_maintenance'),
            'default' => 'XX' === $current,
        ];

        $widget->options = $options;

        return $widget;
    }
}
