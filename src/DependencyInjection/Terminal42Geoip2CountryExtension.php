<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Terminal42\Geoip2CountryBundle\Command\DatabaseUpdateCommand;
use Terminal42\Geoip2CountryBundle\CountryProvider;
use Terminal42\Geoip2CountryBundle\Cron\DatabaseUpdateCron;
use Terminal42\Geoip2CountryBundle\DatabaseUpdater;

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
            $container->findDefinition(CountryProvider::class)->replaceArgument(0, $config['database_path']);
            $container->findDefinition(DatabaseUpdater::class)->replaceArgument(3, $config['database_path']);
        }

        if (!$container->resolveEnvPlaceholders($config['update_credentials'], true)) {
            $container->removeDefinition(DatabaseUpdater::class);
            $container->removeDefinition(DatabaseUpdateCommand::class);

            return;
        }

        $definition = $container->findDefinition(DatabaseUpdater::class);
        $definition->replaceArgument(2, $config['update_credentials']);

        if ($config['update_interval']) {
            $cron = new Definition(DatabaseUpdateCron::class, [new Reference(DatabaseUpdater::class)]);
            $cron->addTag('contao.cronjob', ['interval' => $config['update_interval']]);
            $container->setDefinition(DatabaseUpdateCron::class, $cron);
        }
    }
}
