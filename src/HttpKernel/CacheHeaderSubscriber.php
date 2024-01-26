<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\HttpKernel;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\Events;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheHeaderSubscriber implements EventSubscriberInterface
{
    final public const HEADER_NAME = 'GeoIP2-Country';

    private Reader|null $reader = null;

    private readonly string|null $databasePath;

    /**
     * @param Reader|string $databasePath
     */
    public function __construct($databasePath)
    {
        if ($databasePath instanceof Reader) {
            $this->reader = $databasePath;
            $this->databasePath = null;
            trigger_deprecation('terminal42/contao-geoip2-country', '1.3', 'Passing Reader to '.self::class.' constructor is deprecated, pass the database path instead.');

            return;
        }

        $this->databasePath = $databasePath;
    }

    public function __invoke(CacheEvent $event): void
    {
        $request = $event->getRequest();

        try {
            $country = $this->getReader()->country($request->getClientIp())->country->isoCode;

            if ($country) {
                $request->headers->set(
                    self::HEADER_NAME,
                    $country,
                    true,
                );
            }
        } catch (AddressNotFoundException) {
            // Ignore unknown address and do not set header
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => '__invoke',
        ];
    }

    private function getReader(): Reader
    {
        if ($this->reader) {
            return $this->reader;
        }

        return $this->reader = new Reader($this->databasePath);
    }
}
