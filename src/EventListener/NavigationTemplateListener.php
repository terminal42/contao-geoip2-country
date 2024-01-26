<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\Template;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class NavigationTemplateListener
{
    public function __construct(
        private readonly CountryProvider $countryProvider,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(Template $template): void
    {
        if (!str_starts_with($template->getName(), 'nav_') || !\is_array($items = $template->items)) {
            return;
        }

        $country = null;

        foreach ($items as $k => $item) {
            $visibility = $item['geoip_visibility'] ?? null;

            if ('show' !== $visibility && 'hide' !== $visibility) {
                continue;
            }

            if (null === $country) {
                $country = $this->countryProvider->getCountryCode($this->requestStack->getMainRequest());
            }

            $countries = explode(',', (string) ($item['geoip_countries'] ?? ''));

            if (\in_array($country, $countries, true) !== ('show' === $visibility)) {
                unset($items[$k]);
            }
        }

        $template->items = $items;
    }
}
