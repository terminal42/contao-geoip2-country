<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class MemberCountryListener
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(string $table): void
    {
        if ('tl_member' !== $table) {
            return;
        }

        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_member']['fields']['country']['default'] = $this->countryProvider->getCountryCode($request);
    }
}
