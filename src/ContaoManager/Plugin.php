<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Terminal42\Geoip2CountryBundle\Terminal42Geoip2CountryBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            (new BundleConfig(Terminal42Geoip2CountryBundle::class))->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
