
# terminal42/contao-geoip2-country

This Contao extension finds the country from the client IP.
The website can then be customized based on this information.

This extension requires the [MaxMind GeoIP2 database](https://www.maxmind.com/en/geoip2-databases),
either the _GeoIP2 Country_ or _GeoLite2 Country_ database. Be aware that you might
need a commercial license of this product depending on your use case!


## Features

1. **Limit content based on the user country**<br>
   By default, visibility for pages, articles, content elements and front end modules can be set for the country.
   For each content, you can either show only to or hide it from a list of countries.


2. **Root page routing based on the user country**<br>
   GeoIP routing in the page tree allows you to define what root page a visitor will be redirected
   to based on their country.


3. **Symfony HTTP Reverse Proxy**<br>
   Integrated support for the Symfony HTTP Reverse Proxy allows a page to be cached
   for each country by using `Vary` headers. Without a supported reverse proxy, responses with
   country-specific content are automatically set to `Cache-Control: private`.

   This will be automatically configured for you in a Contao Managed Edition.


4. **Default country for members**<br>
   The detected country is set as the default country for new members, so the registration front end module
   already has the country pre-selected.


5. **Support for `terminal42/contao-countryselect`**<br>
   If the `countryselect` form field is added to a form, the default option is automatically
   set to the visitors country.


### Note on page visibility

If the visibility of a root page is configured, it also affects all its subpages. This means
if a root page is not available for a country, none of the pages in this tree will be available.

Enabling this on the fallback root page can lead to unwanted consequences, because the user will
not be redirected to **any** page if none of the preferred languages match the browser!


## Installation

Choose the installation method that matches your workflow!

### Installation via Contao Manager

Search for `terminal42/contao-geoip2-country` in the Contao Manager and add it to your installation. Finally, update the
packages.

### Manual installation

Add a composer dependency for this bundle. Therefore, change in the project root and run the following:

```bash
composer require terminal42/contao-geoip2-country
```

Depending on your environment, the command can differ, i.e. starting with `php composer.phar …` if you do not have
composer installed globally.

Then, update the database via the `contao:migrate` command or the Contao install tool.

#### HTTP Reverse Proxy

If you do not use the Contao Managed Edition, you can manually register the CacheHeaderSubscriber when using
the Symfony Reverse Proxy including `friendsofsymfony/http-cache`.


## Configuration

### MaxMind GeoIP2 database

Install the binary MMDB file and configure its path in the `GEOIP2_DATABASE` environment variable
(e.g. through your `.env`/`.env.local` file).

### Bundle configuration

**Default configuration:**
```yaml
terminal42_geoip2_country:
    database_path: %env(GEOIP2_DATABASE)%
    fallback_country: XX
    dca_tables: [tl_content, tl_article, tl_module, tl_page]
```

- **database_path:** Path to the MMDB file. Defaults to the `GEOIP2_DATABASE` environment variable.
    Be aware that this setting does not apply to the HTTP Reverse Proxy!

- **fallback_country:** The default country if a visitors IP cannot be detected (e.g. applies to localhost as well).
    _XX_ is the United Nations standard for _unknown country_, but by entering a valid
    [ISO 3166-1 alpha-2 code](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) unknown visitors will see a specific content.

- **dca_tables:** configures which elements allow the country restrictions. Changing this will add the DCA fields to the
    given table(s), but for any but the default tables you will need to implement your own visibility checks!


## Updating the MaxMind GeoIP2 database

Detecting the country from IP requires an up-to-date information source, as
IPs change all the time. We recommend to use [MaxMind's Automatic Update Support](https://dev.maxmind.com/geoip/geoipupdate/)
to keep your database up-to-date.


## GeoIP Routing

GeoIP Routing is a powerful feature to define which root page / language a visitor will see,
overriding the default language routing of Contao. This is mostly useful if you have
country-specific websites, but the default browser language matching is unreliable.

As an example, you might have root pages for "de", "en" and "de-CH". You want visitors from Switzerland
to prefer "de-CH" over "de", but otherwise keep the Contao routing.

Using _GeoIP2 Routing_, add a configuration for Switzerland, and select the "de-CH" root page. If you still
want to allow english browsers to receive the "en" page, also select that one. Order the pages so the fallback
(the one that will be serverd to e.g. french visitors) is at the top.

If you do not define any other rule, Swiss visitors will receive the configured page,
the rest of the world will receive one of the tree root pages depending on their browser.
If you want to prevent the rest of the world from receiving the "de-CH" page, add a _Fallback_ routing
for "de" and "en" only.


## Access the current user country

To retrieve the current country in your own code, inject the `Terminal42\Geoip2CountryBundle\CountryProvider` service
into your class. Then use the `getCurrentCountry` method and pass the request object to it.

**Example in a Contao `AbstractFrontendModule` controller:**

```php
<?php

namespace App\Controller;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\Geoip2CountryBundle\CountryProvider;

class FooController extends AbstractFrontendModuleController
{
    public function __construct(
        private readonly CountryProvider $countryProvider
    ) {
    }

    public function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        // Only show content to Switzerland
        if ('CH' !== $this->countryProvider->getCurrentCountry($request)) {
            return new Response();
        }

        return $template->getResponse();
    }
}
```


## License

This bundle is released under the [MIT](LICENSE)
