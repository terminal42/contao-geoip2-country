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
        $country = $this->countryProvider->getCountryCode($request);

        foreach ($collection as $name => $route) {
            $pageModel = $route->getDefault('pageModel');

            if (!$pageModel instanceof PageModel) {
                continue;
            }

            if (!$this->isAvailable($pageModel, $country)) {
                $collection->remove($name);
            }

            // Disallow access to a page if its root page is not available for the current country
            if ('root' !== $pageModel->type) {
                $rootModel = PageModel::findById($pageModel->loadDetails()->rootId);

                if ($rootModel && !$this->isAvailable($rootModel, $country)) {
                    $collection->remove($name);
                }
            }
        }

        return $collection;
    }

    private function isAvailable(PageModel $pageModel, string $country): bool
    {
        if ('show' !== $pageModel->geoip_visibility && 'hide' !== $pageModel->geoip_visibility) {
            return true;
        }

        $countries = explode(',', (string) $pageModel->geoip_countries);

        return \in_array($country, $countries, true) === ('show' === $pageModel->geoip_visibility);
    }
}
