<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\Widget;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class FormCountrySelectListener
{
    private CountryProvider $countryProvider;
    private RequestStack $requestStack;

    public function __construct(CountryProvider $countryProvider, RequestStack $requestStack)
    {
        $this->countryProvider = $countryProvider;
        $this->requestStack = $requestStack;
    }

    public function __invoke(Widget $widget)
    {
        if ('countryselect' === $widget->type) {
            $widget->value = strtolower($this->countryProvider->getCountryCode($this->requestStack->getMasterRequest()));
        }

        return $widget;
    }
}
