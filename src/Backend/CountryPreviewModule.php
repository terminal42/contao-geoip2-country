<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Terminal42\Geoip2CountryBundle\Backend;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\Input;
use Contao\SelectMenu;
use Contao\System;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class CountryPreviewModule implements \executable
{
    private Connection $connection;
    private Session $session;
    private TranslatorInterface $translator;
    private array $supportedTables;

    public function __construct(Connection $connection, Session $session, TranslatorInterface $translator, array $supportedTables)
    {
        $this->connection = $connection;
        $this->session = $session;
        $this->translator = $translator;
        $this->supportedTables = $supportedTables;
    }

    public function isActive(): bool
    {
        return false;
    }

    public function run(): string
    {
        $countries = $this->getUsedCountries();

        if (empty($countries)) {
            return '';
        }

        $currentCountry = $this->session->get(CountryProvider::SESSION_KEY);
        $widget = $this->generateWidget($countries, $currentCountry ? strtolower($currentCountry) : null);

        if ('geoip2_switch' === Input::post('FORM_SUBMIT')) {
            $widget->validate();

            if (!$widget->hasErrors()) {
                if (empty($widget->value)) {
                    $this->session->remove(CountryProvider::SESSION_KEY);
                } else {
                    $this->session->set(CountryProvider::SESSION_KEY, strtoupper($widget->value));
                }
                Controller::reload();
            }
        }

        $template = new BackendTemplate('be_geoip2_switch');
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
            'SELECT GROUP_CONCAT(geoip_countries) FROM ('.implode(' UNION ', $queries).') AS result'
        )->fetchOne();

        return array_values(array_filter(array_unique(explode(',', $countries))));
    }

    private function generateWidget(array $countries, ?string $current): Widget
    {
        $widget = new SelectMenu();
        $widget->id = 'country';
        $widget->name = 'country';
        $widget->label = $this->translator->trans('tl_maintenance.geoip2_country.0', [], 'contao_tl_maintenance');

        $countryNames = System::getCountries();
        $options = [];

        foreach ($countries as $country) {
            $options[] = [
                'value' => $country,
                'label' => $countryNames[$country],
                'default' => $country === $current,
            ];
        }

        usort($options, static function ($option1, $option2) {
            return strcmp($option1['label'], $option2['label']);
        });

        array_unshift($options, ['value' => '', 'label' => '-', 'default' => null === $current]);

        $options[] = [
            'value' => 'xx',
            'label' => $this->translator->trans('tl_maintenance.geoip2_unknown', [], 'contao_tl_maintenance'),
            'default' => 'xx' === $current,
        ];

        $widget->options = $options;

        return $widget;
    }
}
