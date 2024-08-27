<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @var array<string>
     */
    private static array $defaultTables = ['tl_content', 'tl_article', 'tl_module', 'tl_page'];

    public static function addDefaultTable(string $table): void
    {
        self::$defaultTables[] = $table;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('terminal42_geoip2_country');
        $treeBuilder
            ->getRootNode()
            ->children()
                ->scalarNode('database_path')
                    ->defaultNull()
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
            ->end()
        ;

        return $treeBuilder;
    }
}
