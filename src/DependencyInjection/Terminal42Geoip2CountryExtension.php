<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Terminal42\Geoip2CountryBundle\CountryProvider;
use Terminal42\Geoip2CountryBundle\EventListener\DcaLoaderListener;

class Terminal42Geoip2CountryExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.yml');

        $container->getDefinition(CountryProvider::class)->setArgument(1, $config['fallback_country']);
        $container->getDefinition(DcaLoaderListener::class)->setArgument(0, $config['dca_tables']);
        $container->setParameter('terminal42_geoip2_country.database_path', $config['database_path']);
    }
}
