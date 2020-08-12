<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42Geoip2CountryBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
