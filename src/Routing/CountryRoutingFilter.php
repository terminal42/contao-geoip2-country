<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\Routing;

use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class CountryRoutingFilter implements RouteFilterInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CountryProvider $countryProvider,
    ) {
    }

    public function filter(RouteCollection $collection, Request $request): RouteCollection
    {
        $pages = $this->getPagesForCountry($this->countryProvider->getCountryCode($request));

        if (!$pages) {
            return $collection;
        }

        foreach ($collection as $name => $route) {
            $pageModel = $route->getDefault('pageModel');

            if (!$pageModel instanceof PageModel || !$this->isRootRoute($name, $route)) {
                continue;
            }

            $pageModel->rootIsFallback = $pages[0] === (int) $pageModel->id;

            if (!\in_array((int) $pageModel->rootId, $pages, true)) {
                $collection->remove($name);
            }
        }

        return $collection;
    }

    /**
     * @return array<int>|null
     */
    private function getPagesForCountry(string $country): array|null
    {
        $result = $this->connection->fetchOne(
            "SELECT pages FROM tl_page_geoip WHERE published='1' AND (country=? OR country='XX') ORDER BY country='XX' LIMIT 1",
            [$country],
        );

        return $result ? array_map('intval', explode(',', (string) $result)) : null;
    }

    private function isRootRoute(string $name, Route $route): bool
    {
        if (str_ends_with($name, '.fallback')) {
            return true;
        }

        if (str_ends_with($name, '.root') && '/' === $route->getPath()) {
            return true;
        }

        return false;
    }
}
