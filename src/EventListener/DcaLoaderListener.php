<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class DcaLoaderListener
{
    private Connection $connection;
    private TranslatorInterface $translator;
    private array $supportedTables;

    public function __construct(Connection $connection, TranslatorInterface $translator, array $supportedTables)
    {
        $this->connection = $connection;
        $this->translator = $translator;
        $this->supportedTables = $supportedTables;
    }

    public function __invoke(string $table): void
    {
        if (\in_array($table, $this->supportedTables, true)) {
            $this->addFieldsToDCA($table);

            // New palettes might be added by other onload_callback (which run after the loadDataContainer hook)
            $GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = static function () use ($table) {
                $pm = PaletteManipulator::create()
                    ->addField('geoip_visibility', 'protected', PaletteManipulator::POSITION_AFTER, 'protected_legend')
                ;

                foreach ($GLOBALS['TL_DCA'][$table]['palettes'] as $k => $v) {
                    if (\is_string($v)) {
                        $pm->applyToPalette($k, $table);
                    }
                }
            };
        }

        if (
            4 === (int) ($GLOBALS['TL_DCA'][$table]['list']['sorting']['mode'] ?? 0)
            && \in_array($GLOBALS['TL_DCA'][$table]['config']['ptable'] ?? '', $this->supportedTables, true)
        ) {
            $this->addHeaderInformation($table);
        }
    }

    private function addFieldsToDCA(string $table): void
    {
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
                $this->addFlagsToListView($table);
                break;

            case 4:
                $this->addFlagsToParentView($table);
                break;

            case 5:
            case 6:
                $this->addFlagsToTreeView($table);
                break;
        }
    }

    private function addHeaderInformation(string $table): void
    {
        $previous = $GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'] ?? null;

        $GLOBALS['TL_DCA'][$table]['list']['sorting']['header_callback'] = function (array $header) use ($previous, $table): array {
            $act = (string) Input::get('act');
            $ptable = $GLOBALS['TL_DCA'][$table]['config']['ptable'];

            if ('' === $act || 'select' === $act || ('paste' === $act && 'create' === Input::get('mode'))) {
                $parent = $this->connection->fetchAssociative("SELECT * FROM $ptable WHERE id=?", [(int) Input::get('id')]);
            } elseif ('paste' === $act) {
                $parent = $this->connection->fetchAssociative("SELECT * FROM $ptable WHERE id=(SELECT pid FROM $table WHERE id=?)", [(int) Input::get('id')]);
            }

            if (!$parent) {
                return $header;
            }

            if (!$this->hasVisibility($parent)) {
                return $header;
            }

            $data = $this->callPrevious($previous, \func_get_args());

            if (\is_array($data)) {
                $header = $data;
            }

            $label = $this->translator->trans($ptable.'.geoip_visibility.0', [], 'contao_'.$ptable);
            $countries = explode(',', $parent['geoip_countries']);
            $header[$label] = '<span style="color:#C00;font-weight:bold">'.$this->getLabelForCountries($parent['geoip_visibility'], $countries).'</span>';

            return $header;
        };
    }

    private function addFlagsToListView(string $table): void
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

    private function addFlagsToParentView(string $table): void
    {
        $previous = $GLOBALS['TL_DCA'][$table]['list']['sorting']['child_record_callback'];

        $GLOBALS['TL_DCA'][$table]['list']['sorting']['child_record_callback'] = function (array $row) use ($previous) {
            $buffer = (string) $this->callPrevious($previous, \func_get_args());

            return $buffer.$this->generateFlags($row);
        };
    }

    private function addFlagsToTreeView(string $table): void
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
        if (!$this->hasVisibility($row)) {
            return '';
        }

        $countries = explode(',', $row['geoip_countries']);
        $color = 'show' === $row['geoip_visibility'] ? '#b2f986' : '#ff89bf';

        $buffer = sprintf(
            '<span style="all:unset;display:inline-flex;margin-left:5px;padding:3px 2px;vertical-align:middle;background:%s;border-radius:2px" title="%s">',
            $color,
            $this->getLabelForCountries($row['geoip_visibility'], $countries)
        );

        foreach ($countries as $country) {
            $buffer .= sprintf(
                '<img style="all:unset;display:block;height:14px;padding:0 2px" src="bundles/terminal42geoip2country/flags/%s.svg" alt="" height="14">',
                $country
            );
        }

        return $buffer.'</span>';
    }

    private function hasVisibility(array $row): bool
    {
        return isset($row['geoip_visibility']) && ('show' === $row['geoip_visibility'] || 'hide' === $row['geoip_visibility']);
    }

    private function getLabelForCountries(string $visibility, array $countries): string
    {
        return $this->translator->trans(
            'MSC.geoip_visibility.'.$visibility.'_for',
            [implode(', ', array_intersect_key(System::getCountries(), array_flip($countries)))],
            'contao_default'
        );
    }
}
