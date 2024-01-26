<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Terminal42\Geoip2CountryBundle\Routing\CountryRestrictionFilter;

class RouteFilterPass implements CompilerPassInterface
{
    public function __construct(private readonly string $serviceId)
    {
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition($this->serviceId)) {
            return;
        }

        $container->getDefinition($this->serviceId)->addMethodCall('addRouteFilter', [new Reference(CountryRestrictionFilter::class)]);
    }
}
