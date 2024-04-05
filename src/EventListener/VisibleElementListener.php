<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class VisibleElementListener
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(Model $element, bool $hasAccess): bool
    {
        if ($this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest() ?? Request::create(''))) {
            return $hasAccess;
        }

        if ('show' !== $element->geoip_visibility && 'hide' !== $element->geoip_visibility) {
            return $hasAccess;
        }

        $countries = explode(',', (string) $element->geoip_countries);
        $country = $this->countryProvider->getCountryCode($this->requestStack->getMainRequest());

        return \in_array($country, $countries, true) === ('show' === $element->geoip_visibility);
    }
}
