<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_page_geoip'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'backlink' => 'do=page',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'country' => 'unique',
                'published,country' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['country'],
            'panelLayout' => 'filter,limit',
        ],
        'label' => [
            'fields' => ['country', 'pages'],
            'showColumns' => true,
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'href' => 'act=toggle&amp;field=published',
                'icon' => 'visible.svg',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{routing_legend},country,pages;{publish_legend},published',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'country' => [
            'exclude' => true,
            'inputType' => 'select',
            'eval' => [
                'unique' => true,
                'chosen' => true,
                'tl_class' => 'clr w50',
            ],
            'sql' => ['type' => 'string', 'length' => 2, 'notnull' => false],
        ],
        'pages' => [
            'exclude' => true,
            'inputType' => 'checkboxWizard',
            'eval' => [
                'mandatory' => true,
                'multiple' => true,
                'csv' => ',',
                'tl_class' => 'clr',
            ],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'published' => [
            'toggle' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];
