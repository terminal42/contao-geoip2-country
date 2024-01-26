<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\Widget;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class FormCountrySelectListener
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(Widget $widget): Widget
    {
        $request = $this->requestStack->getMainRequest();

        if ('countryselect' === $widget->type && null !== $request && $request->isMethodCacheable()) {
            $widget->value = $this->countryProvider->getCountryCode($request);
        }

        return $widget;
    }
}
