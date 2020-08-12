<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\System;

class DcaLoaderListener
{
    private array $supportedTables;

    public function __construct(array $supportedTables)
    {
        $this->supportedTables = $supportedTables;
    }

    public function __invoke(string $table): void
    {
        if (!\in_array($table, $this->supportedTables, true)) {
            return;
        }

        $this->addFieldsToDCA($table);
    }

    private function addFieldsToDCA(string $table): void
    {
        $pm = PaletteManipulator::create()
            ->addField('geoip_visibility', 'protected', PaletteManipulator::POSITION_AFTER, 'protected_legend')
        ;

        foreach ($GLOBALS['TL_DCA'][$table]['palettes'] as $k => $v) {
            if (\is_string($v)) {
                $pm->applyToPalette($k, $table);
            }
        }

        $GLOBALS['TL_DCA'][$table]['palettes']['__selector__'][] = 'geoip_visibility';
        $GLOBALS['TL_DCA'][$table]['subpalettes']['geoip_visibility_show'] = 'geoip_countries';
        $GLOBALS['TL_DCA'][$table]['subpalettes']['geoip_visibility_hide'] = 'geoip_countries';

        $GLOBALS['TL_DCA'][$table]['fields']['geoip_visibility'] = [
            'label' => &$GLOBALS['TL_LANG'][$table]['geoip_visibility'],
            'exclude' => true,
            'inputType' => 'radio',
            'options' => ['none', 'show', 'hide'],
            'reference' => &$GLOBALS['TL_LANG']['MSC']['geoip_visibility'],
            'eval' => [
                'submitOnChange' => true,
                'tl_class' => 'clr w50',
            ],
            'sql' => ['type' => 'string', 'length' => 8, 'default' => 'none'],
        ];

        $GLOBALS['TL_DCA'][$table]['fields']['geoip_countries'] = [
            'label' => &$GLOBALS['TL_LANG'][$table]['geoip_countries'],
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => static function () {
                return System::getCountries();
            },
            'eval' => [
                'includeBlankOption' => true,
                'mandatory' => true,
                'multiple' => true,
                'chosen' => true,
                'csv' => ',',
                'tl_class' => 'w50',
            ],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ];

        switch ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode']) {
            case 0:
            case 1:
            case 2:
            case 3:
                $this->overrideListView($table);
                break;

            case 4:
                $this->overrideParentViewLabel($table);
                break;

            case 5:
            case 6:
                $this->overrideTreeViewLabel($table);
                break;
        }
    }

    private function overrideListView(string $table): void
    {
        $previous = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'];

        $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'] = function (array $row) use ($previous) {
            $buffer = $this->callPrevious($previous, \func_get_args());

            if (\is_array($buffer)) {
                return $buffer;
            }

            return $buffer.$this->generateFlags($row);
        };
    }

    private function overrideParentViewLabel(string $table): void
    {
        $previous = $GLOBALS['TL_DCA'][$table]['list']['sorting']['child_record_callback'];

        $GLOBALS['TL_DCA'][$table]['list']['sorting']['child_record_callback'] = function (array $row) use ($previous) {
            $buffer = (string) $this->callPrevious($previous, \func_get_args());

            return $buffer.$this->generateFlags($row);
        };
    }

    private function overrideTreeViewLabel(string $table): void
    {
        $previous = $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'];

        $GLOBALS['TL_DCA'][$table]['list']['label']['label_callback'] = function (array $row) use ($previous) {
            $buffer = (string) $this->callPrevious($previous, \func_get_args());

            return $buffer.$this->generateFlags($row);
        };
    }

    private function callPrevious($previous, $arguments)
    {
        if (\is_array($previous)) {
            return System::importStatic($previous[0])->{$previous[1]}(...$arguments);
        }

        if (\is_callable($previous)) {
            return $previous(...$arguments);
        }

        return null;
    }

    private function generateFlags(array $row): string
    {
        if (!isset($row['geoip_visibility']) || ('show' !== $row['geoip_visibility'] && 'hide' !== $row['geoip_visibility'])) {
            return '';
        }

        $countries = explode(',', $row['geoip_countries']);
        $color = 'show' === $row['geoip_visibility'] ? '#b2f986' : '#ff89bf';

        $buffer = sprintf(
            '<span style="all:unset;display:inline-flex;padding:3px 2px;vertical-align:middle;background:%s;border-radius:2px" title="%s %s">',
            $color,
            'show' === $row['geoip_visibility'] ? 'Only visible for' : 'Hidden for',
            implode(', ', array_intersect_key(System::getCountries(), array_flip($countries)))
        );

        foreach ($countries as $country) {
            $buffer .= sprintf(
                '<img style="all:unset;display:block;height:14px;padding:0 2px" src="bundles/terminal42geoip2country/flags/%s.svg" alt="" height="14">',
                $country
            );
        }

        return $buffer.'</span>';
    }
}
