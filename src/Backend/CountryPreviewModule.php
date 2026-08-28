<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\Backend;

use Contao\Controller;
use Contao\CoreBundle\Intl\Countries;
use Contao\Input;
use Contao\MaintenanceModuleInterface;
use Contao\SelectMenu;
use Contao\Widget;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\Geoip2CountryBundle\CountryProvider;
use Twig\Environment;

#[Autoconfigure(public: true)]
class CountryPreviewModule implements MaintenanceModuleInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly Countries $countries,
        private readonly Environment $twig,
    ) {
    }

    public function isActive(): bool
    {
        return false;
    }

    public function run(): string
    {
        $session = $this->requestStack->getSession();
        $currentCountry = $session->get(CountryProvider::SESSION_KEY);
        $widget = $this->generateWidget($currentCountry);

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

        return $this->twig->render('@Contao/backend/geoip2_switch.html.twig', [
            'widget' => $widget->parse(),
            'has_errors' => $widget->hasErrors(),
        ]);
    }

    private function generateWidget(string|null $current): Widget
    {
        $widget = new SelectMenu();
        $widget->id = 'country';
        $widget->name = 'country';
        $widget->label = $this->translator->trans('tl_maintenance.geoip2_country.0', [], 'contao_tl_maintenance');
        $widget->class = 'tl_chosen';

        $options = [
            ['value' => '', 'label' => '-', 'default' => null === $current],
            [
                'value' => 'XX',
                'label' => $this->translator->trans('tl_maintenance.geoip2_unknown', [], 'contao_tl_maintenance'),
                'default' => 'XX' === $current,
            ],
        ];

        foreach ($this->countries->getCountries() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => $label,
                'default' => $code === $current,
            ];
        }

        $widget->options = $options;

        return $widget;
    }
}
