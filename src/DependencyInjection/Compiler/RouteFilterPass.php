<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RouteFilterPass implements CompilerPassInterface
{
    public function __construct(
        private readonly string $serviceId,
        private readonly string $className,
        private readonly int $priority = 0,
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition($this->serviceId)) {
            return;
        }

        $container->getDefinition($this->serviceId)->addMethodCall('addRouteFilter', [new Reference($this->className), $this->priority]);
    }
}
