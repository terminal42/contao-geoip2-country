<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class UppercaseCountryMigration extends AbstractMigration
{
    /**
     * @param array<string> $supportedTables
     */
    public function __construct(
        private readonly Connection $connection,
        #[Autowire(param: 'terminal42_geoip2_country.dca_tables')] private readonly array $supportedTables,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->supportedTables as $table) {
            if (!$schemaManager->tableExists($table)) {
                continue;
            }

            if (!$schemaManager->introspectTableByUnquotedName($table)->hasColumn('geoip_countries')) {
                continue;
            }

            if ($this->connection->fetchOne("SELECT COUNT(*) FROM $table WHERE UPPER(geoip_countries) != BINARY(geoip_countries)") > 0) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->supportedTables as $table) {
            if (!$schemaManager->tableExists($table)) {
                continue;
            }

            if (!$schemaManager->introspectTableByUnquotedName($table)->hasColumn('geoip_countries')) {
                continue;
            }

            $this->connection->fetchOne("UPDATE $table SET geoip_countries = UPPER(geoip_countries)");
        }

        return $this->createResult(true);
    }
}
