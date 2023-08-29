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

    private Reader $reader;

    public function __construct(Reader $geoip2Reader)
    {
        $this->reader = $geoip2Reader;
    }

    public function __invoke(CacheEvent $event): void
    {
        $request = $event->getRequest();

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
}
