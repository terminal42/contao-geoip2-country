<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Terminal42\Geoip2CountryBundle\DependencyInjection\Compiler\RouteFilterPass;

class Terminal42Geoip2CountryBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new RouteFilterPass('contao.routing.nested_matcher'));
        $container->addCompilerPass(new RouteFilterPass('contao.routing.nested_404_matcher'));
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
