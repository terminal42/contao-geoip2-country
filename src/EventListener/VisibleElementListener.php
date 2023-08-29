<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\Model;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class VisibleElementListener
{
    private CountryProvider $countryProvider;
    private RequestStack $requestStack;

    public function __construct(CountryProvider $countryProvider, RequestStack $requestStack)
    {
        $this->countryProvider = $countryProvider;
        $this->requestStack = $requestStack;
    }

    public function __invoke(Model $element, bool $hasAccess): bool
    {
        if ('show' !== $element->geoip_visibility && 'hide' !== $element->geoip_visibility) {
            return $hasAccess;
        }

        $countries = array_map('strtoupper', explode(',', $element->geoip_countries));
        $country = $this->countryProvider->getCountryCode($this->requestStack->getMainRequest());

        return \in_array($country, $countries, true) === ('show' === $element->geoip_visibility);
    }
}
