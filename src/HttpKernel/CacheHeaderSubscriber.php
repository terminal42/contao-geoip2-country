<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\HttpKernel;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\Events;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

#[Exclude]
class CacheHeaderSubscriber implements EventSubscriberInterface
{
    final public const string HEADER_NAME = 'GeoIP2-Country';

    private Reader|null $reader = null;

    public function __construct(private readonly string|null $databasePath)
    {
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
