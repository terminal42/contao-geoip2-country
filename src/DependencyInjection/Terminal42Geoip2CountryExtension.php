<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class Terminal42Geoip2CountryExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.yml');

        $container->setParameter('terminal42_geoip2_country.fallback_country', $config['fallback_country']);
        $container->setParameter('terminal42_geoip2_country.dca_tables', $config['dca_tables']);
        $container->setParameter('terminal42_geoip2_country.database_path', $config['database_path']);

        if ($config['database_path']) {
            $definition = $container->findDefinition(CountryProvider::class);
            $definition->replaceArgument(0, $config['database_path']);
        }
    }
}
