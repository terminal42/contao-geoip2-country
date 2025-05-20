<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DatabaseUpdater
{
    private const DATABASE_URL = 'https://download.maxmind.com/geoip/databases/GeoLite2-Country/download?suffix=tar.gz';

    private const SHA256_URL = 'https://download.maxmind.com/geoip/databases/GeoLite2-Country/download?suffix=tar.gz.sha256';

    private readonly string|null $databasePath;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Filesystem $filesystem,
        #[\SensitiveParameter] private readonly string $credentials,
        string|null $databasePath = null,
        private readonly LoggerInterface|null $logger = null,
    ) {
        $this->databasePath = $databasePath ?? $_SERVER['GEOIP2_DATABASE'] ?? null;
    }

    public function update(): void
    {
        if (!$this->databasePath || !$this->needsUpdate()) {
            return;
        }

        $response = $this->httpClient->request('GET', self::DATABASE_URL, ['auth_basic' => $this->credentials]);
        $lastModified = new \DateTime($response->getHeaders()['last-modified'][0]);

        $temp = $this->filesystem->tempnam(sys_get_temp_dir(), 'geoip', '.tar.gz');
        $this->filesystem->dumpFile($temp, $response->getContent());

        [$sha256] = explode(' ', $this->httpClient->request('GET', self::SHA256_URL, ['auth_basic' => $this->credentials])->getContent());

        if (hash_file('sha256', $temp) !== $sha256) {
            throw new \RuntimeException('SHA256 does not match.');
        }

        // Restore the Phar stream wrapper that is disabled by Contao for security reasons
        // (see https://github.com/contao/contao/pull/105)
        if (!\in_array('phar', stream_get_wrappers(), true)) {
            stream_wrapper_restore('phar');
        }

        $phar = new \PharData($temp);
        $phar->extractTo(\dirname($this->databasePath), null, true);

        $extractFolder = Path::join(\dirname($this->databasePath), $phar->getFilename());
        $finder = Finder::create()->files()->name('*.mmdb')->in($extractFolder);

        foreach ($finder as $file) {
            $this->filesystem->rename($file->getPathname(), $this->databasePath, true);
        }

        $this->filesystem->touch($this->databasePath, (int) $lastModified->format('U'));
        $this->filesystem->remove($extractFolder);

        $this->logger?->info(basename($this->databasePath).' has been updated to version '.$lastModified->format('Y-m-d'));
    }

    private function needsUpdate(): bool
    {
        if (!$this->filesystem->exists($this->databasePath)) {
            return true;
        }

        $response = $this->httpClient->request('HEAD', self::DATABASE_URL, ['auth_basic' => $this->credentials]);

        $lastModified = new \DateTime($response->getHeaders()['last-modified'][0]);

        return filemtime($this->databasePath) !== $lastModified->format('U');
    }
}
