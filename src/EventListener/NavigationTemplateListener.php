<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\Template;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class NavigationTemplateListener
{
    private CountryProvider $countryProvider;
    private RequestStack $requestStack;

    public function __construct(CountryProvider $countryProvider, RequestStack $requestStack)
    {
        $this->countryProvider = $countryProvider;
        $this->requestStack = $requestStack;
    }

    public function __invoke(Template $template): void
    {
        if ('nav_' !== substr($template->getName(), 0, 4) || !\is_array($items = $template->items)) {
            return;
        }

        $country = $this->countryProvider->getCountryCode($this->requestStack->getMasterRequest());

        foreach ($items as $k => $item) {
            $visibility = $item['geoip_visibility'] ?? null;
            $countries = $item['geoip_countries'] ?? '';

            if ('show' !== $visibility && 'hide' !== $visibility) {
                continue;
            }

            $countries = array_map('strtoupper', explode(',', $countries));

            if (\in_array($country, $countries, true) !== ('show' === $visibility)) {
                unset($items[$k]);
            }
        }

        $template->items = $items;
    }
}
