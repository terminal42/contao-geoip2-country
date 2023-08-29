<?php

use Contao\PurgeData;
use Terminal42\Geoip2CountryBundle\Backend\CountryPreviewModule;

// Insert before purgeData
if (false !== ($pos = array_search(PurgeData::class, array_values($GLOBALS['TL_MAINTENANCE']), true))) {
    array_splice($GLOBALS['TL_MAINTENANCE'], $pos, 0, [CountryPreviewModule::class]);
} else {
    $GLOBALS['TL_MAINTENANCE'][] = CountryPreviewModule::class;
}
