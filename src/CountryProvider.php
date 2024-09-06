<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle;

use Contao\CoreBundle\EventListener\MakeResponsePrivateListener;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ResetInterface;
use Terminal42\Geoip2CountryBundle\HttpKernel\CacheHeaderSubscriber;

class CountryProvider implements ResetInterface
{
    final public const SESSION_KEY = 'geoip2_country';

    private Reader|null $reader = null;

    private string|null $databasePath;

    /**
     * @var array<string, string>
     */
    private array $requestCountries = [];

    public function __construct(
        Reader|string|null $databasePath = null,
        private readonly string $fallbackCountry = 'XX',
    ) {
        if ($databasePath instanceof Reader) {
            $this->reader = $databasePath;
            $this->databasePath = null;
            trigger_deprecation('terminal42/contao-geoip2-country', '1.3', 'Passing Reader to '.self::class.' constructor is deprecated, pass the database path instead.');

            return;
        }

        $this->databasePath = $databasePath ?? $_SERVER['GEOIP2_DATABASE'] ?? null;
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    public function getCountryCode(Request $request, bool $trackRequest = true): string
    {
        if (
            $request->hasPreviousSession()
            && null !== ($sessionCountry = $request->getSession()->get(self::SESSION_KEY))
        ) {
            return $sessionCountry;
        }

        $hash = spl_object_hash($request);

        if ($trackRequest && isset($this->requestCountries[$hash])) {
            return $this->requestCountries[$hash];
        }

        $countryCode = $this->findCountryCode($request);

        if ($trackRequest) {
            $this->requestCountries[$hash] = $countryCode;
        }

        return $countryCode;
    }

    public function updateResponse(Request $request, Response $response): void
    {
        $hash = spl_object_hash($request);

        // Country was never looked up for this request, do not modify response
        if (!isset($this->requestCountries[$hash])) {
            return;
        }

        if ($request->isFromTrustedProxy() && $request->headers->has(CacheHeaderSubscriber::HEADER_NAME)) {
            $response->setVary([CacheHeaderSubscriber::HEADER_NAME], false);

            return;
        }

        $response->setPrivate();
        $response->headers->set(MakeResponsePrivateListener::DEBUG_HEADER, CacheHeaderSubscriber::HEADER_NAME.'='.$this->requestCountries[$hash]);
    }

    public function reset(): void
    {
        if ($this->reader && null !== $this->databasePath) {
            $this->reader->close();
            $this->reader = null;
        }
    }

    /**
     * @throws AddressNotFoundException
     * @throws InvalidDatabaseException
     */
    private function findCountryCode(Request $request): string
    {
        if ($request->isFromTrustedProxy() && $request->headers->has(CacheHeaderSubscriber::HEADER_NAME)) {
            return $request->headers->get(CacheHeaderSubscriber::HEADER_NAME);
        }

        $this->initReader();

        if (null === $this->reader) {
            return $this->fallbackCountry;
        }

        try {
            return $this->reader->country($request->getClientIp())->country->isoCode ?: $this->fallbackCountry;
        } catch (AddressNotFoundException) {
            return $this->fallbackCountry;
        }
    }

    private function initReader(): void
    {
        if ($this->reader || !$this->databasePath) {
            return;
        }

        $this->reader = new Reader($this->databasePath);
    }
}
