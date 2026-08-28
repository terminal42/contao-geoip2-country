<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Terminal42\Geoip2CountryBundle\Command\DatabaseUpdateCommand;
use Terminal42\Geoip2CountryBundle\Cron\DatabaseUpdateCron;
use Terminal42\Geoip2CountryBundle\DependencyInjection\RouteFilterPass;
use Terminal42\Geoip2CountryBundle\Routing\CountryRestrictionFilter;
use Terminal42\Geoip2CountryBundle\Routing\CountryRoutingFilter;

class Terminal42Geoip2CountryBundle extends AbstractBundle
{
    /**
     * @var array<string>
     */
    private static array $defaultTables = ['tl_content', 'tl_article', 'tl_module', 'tl_page'];

    public static function addDefaultTable(string $table): void
    {
        self::$defaultTables[] = $table;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $configurator
            ->services()
            ->load(__NAMESPACE__.'\\', '../src/')
            ->autoconfigure()
            ->autowire()
        ;

        $container->setParameter('terminal42_geoip2_country.fallback_country', $config['fallback_country']);
        $container->setParameter('terminal42_geoip2_country.dca_tables', $config['dca_tables']);
        $container->setParameter('terminal42_geoip2_country.database_path', $config['database_path']);

        if (!$container->resolveEnvPlaceholders($config['update_credentials'], true)) {
            $container->removeDefinition(DatabaseUpdater::class);
            $container->removeDefinition(DatabaseUpdateCommand::class);

            return;
        }

        $definition = $container->findDefinition(DatabaseUpdater::class);
        $definition->setArgument('$credentials', $config['update_credentials']);

        if ($config['update_interval']) {
            $cron = new Definition(DatabaseUpdateCron::class, [new Reference(DatabaseUpdater::class)]);
            $cron->addTag('contao.cronjob', ['interval' => $config['update_interval']]);
            $container->setDefinition(DatabaseUpdateCron::class, $cron);
        }
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RouteFilterPass('contao.routing.nested_matcher', CountryRestrictionFilter::class));
        $container->addCompilerPass(new RouteFilterPass('contao.routing.nested_matcher', CountryRoutingFilter::class, 1));
        $container->addCompilerPass(new RouteFilterPass('contao.routing.nested_404_matcher', CountryRestrictionFilter::class));
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition
            ->rootNode()
            ->children()
                ->scalarNode('database_path')
                ->defaultValue('%env(GEOIP2_DATABASE)%')
                ->info('Path to the GeoIP2 database (overrides the GEOIP2_DATABASE environment variable).')
            ->end()
            ->scalarNode('fallback_country')
                ->cannotBeEmpty()
                ->defaultValue('XX')
                ->info('Fallback country if IP lookup fails. Setting this to an actual country will allow unknown visitors to see that content.')
            ->end()
            ->arrayNode('dca_tables')
                ->scalarPrototype()->end()
                ->defaultValue(self::$defaultTables)
                ->info('List of tables the DCA options should be applied to. Be aware that this might require additional permission checks if modified.')
            ->end()
            ->scalarNode('update_credentials')
                ->defaultValue('%env(GEOIP2_AUTH)%')
                ->info('MaxMind account ID and license key for GeoIP2-Country update, separated by colon (e.g. "1235:my-key").')
            ->end()
            ->scalarNode('update_interval')
                ->defaultValue('weekly')
                ->info('How often the database should be updated automatically. Enter an Contao interval (e.g. daily/weekly) or crontab like "30 1 * * 6,3").')
            ->end()
        ;
    }
}
