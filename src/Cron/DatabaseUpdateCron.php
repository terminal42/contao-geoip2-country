<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\Cron;

use Terminal42\Geoip2CountryBundle\DatabaseUpdater;

/**
 * CronJob interval is registered in the bundle extension.
 */
class DatabaseUpdateCron
{
    public function __construct(private readonly DatabaseUpdater $updater)
    {
    }

    public function __invoke(): void
    {
        $this->updater->update();
    }
}
