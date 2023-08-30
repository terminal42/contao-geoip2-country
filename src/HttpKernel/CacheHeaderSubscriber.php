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
    public const HEADER_NAME = 'GeoIP2-Country';

    private ?Reader $reader = null;
    private string $databasePath;

    /**
     * @param Reader|string $databasePath
     */
    public function __construct($databasePath)
    {
        if ($databasePath instanceof Reader) {
            $this->reader = $databasePath;
            trigger_deprecation('terminal42/contao-geoip2-country', '1.3', 'Passing Reader to '.__CLASS__.' constructor is deprecated, pass the database path instead.');

            return;
        }

        $this->databasePath = $databasePath;
    }

    public function __invoke(CacheEvent $event): void
    {
        $request = $event->getRequest();
        $this->initReader();

        try {
            $country = $this->reader->country($request->getClientIp())->country->isoCode;

            if ($country) {
                $request->headers->set(
                    self::HEADER_NAME,
                    $country,
                    true,
                );
            }
        } catch (AddressNotFoundException $exception) {
            // Ignore unknown address and do not set header
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::PRE_HANDLE => '__invoke',
        ];
    }

    private function initReader(): void
    {
        if ($this->reader) {
            return;
        }

        $this->reader = new Reader($this->databasePath);
    }
}
