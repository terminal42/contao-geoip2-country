<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\Routing;

use Contao\PageModel;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class CountryRestrictionFilter implements RouteFilterInterface
{
    public function __construct(private readonly CountryProvider $countryProvider)
    {
    }

    public function filter(RouteCollection $collection, Request $request): RouteCollection
    {
        foreach ($collection as $name => $route) {
            $pageModel = $route->getDefault('pageModel');

            if (!$pageModel instanceof PageModel) {
                continue;
            }

            if ('show' !== $pageModel->geoip_visibility && 'hide' !== $pageModel->geoip_visibility) {
                continue;
            }

            $countries = explode(',', (string) $pageModel->geoip_countries);
            $country = $this->countryProvider->getCountryCode($request);

            if (\in_array($country, $countries, true) !== ('show' === $pageModel->geoip_visibility)) {
                $collection->remove($name);
            }
        }

        return $collection;
    }
}
