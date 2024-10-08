<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Terminal42\Geoip2CountryBundle\DatabaseUpdater;

#[AsCommand('geoip2:database-update', 'Update the GeoIP2-Country database.')]
class DatabaseUpdateCommand extends Command
{
    public function __construct(private readonly DatabaseUpdater $updater)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->updater->update();

        return Command::SUCCESS;
    }
}
