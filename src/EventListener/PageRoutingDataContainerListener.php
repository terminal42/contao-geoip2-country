<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\ArrayUtil;
use Contao\CoreBundle\Intl\Countries;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageRoutingDataContainerListener
{
    /**
     * @var array<int|string, string>|null
     */
    private array|null $rootPages = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly Countries $countries,
        private readonly TranslatorInterface $translator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function addGlobalOperation(DataContainer $dc): void
    {
        if (!$this->authorizationChecker->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELDS_OF_TABLE, 'tl_page_geoip')) {
            return;
        }

        ArrayUtil::arrayInsert($GLOBALS['TL_DCA']['tl_page']['list']['global_operations'], 0, [
            'geoip_routing' => [
                'label' => [
                    $this->translator->trans('tl_page.geoip_routing.0', [], 'contao_tl_page'),
                    $this->translator->trans('tl_page.geoip_routing.1', [], 'contao_tl_page'),
                ],
                'icon' => '/bundles/terminal42geoip2country/crosshair.svg',
                'href' => 'table=tl_page_geoip',
            ],
        ]);
    }

    /**
     * @param array<string, string> $row
     * @param array<int, string>    $args
     *
     * @return array<int, string>
     */
    public function generateLabel(array $row, string $value, DC_Table $dc, array $args): array
    {
        foreach ($GLOBALS['TL_DCA']['tl_page_geoip']['list']['label']['fields'] as $k => $field) {
            switch ($field) {
                case 'country':
                    $args[$k] = $this->getCountryOptions()[$args[$k]] ?? '-';
                    break;

                case 'pages':
                    $args[$k] = implode('<br>', array_map(fn (int|string $id) => $this->getPagesOptions()[$id], explode(',', (string) $args[$k])));
                    break;
            }
        }

        return $args;
    }

    /**
     * @return array<string, string>
     */
    public function getCountryOptions(): array
    {
        return array_merge(
            ['XX' => $this->translator->trans('tl_page_geoip.country.2', [], 'contao_tl_page_geoip')],
            $this->countries->getCountries(),
        );
    }

    /**
     * @return array<int|string, string>
     */
    public function getPagesOptions(): array
    {
        return $this->getRootPages();
    }

    /**
     * @return array<int|string, string>
     */
    private function getRootPages(): array
    {
        if (null === $this->rootPages) {
            $this->rootPages = $this->connection->fetchAllKeyValue("SELECT id, title FROM tl_page WHERE type='root' ORDER BY sorting");
        }

        return $this->rootPages;
    }
}
