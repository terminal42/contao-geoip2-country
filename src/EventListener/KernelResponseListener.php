<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class KernelResponseListener
{
    public function __construct(private readonly CountryProvider $countryProvider)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $this->countryProvider->updateResponse(
            $event->getRequest(),
            $event->getResponse(),
        );
    }
}
