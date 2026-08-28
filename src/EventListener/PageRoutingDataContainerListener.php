<?php

declare(strict_types=1);

namespace Terminal42\Geoip2CountryBundle\EventListener;

use Contao\ArrayUtil;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Event\DataContainerRecordLabelEvent;
use Contao\CoreBundle\Intl\Countries;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
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

    #[AsEventListener]
    public function __invoke(DataContainerRecordLabelEvent $event): void
    {
        if ('contao.mod.page' === $event->getIdentifier() && 'tl_page_geoip' === Input::get('table')) {
            $event->setLabel($this->translator->trans('tl_page.geoip_routing.0', [], 'contao_tl_page'));

            return;
        }

        if (!str_starts_with($event->getIdentifier(), 'contao.db.')) {
            return;
        }

        [, , $table] = explode('.', $event->getIdentifier()) + [null, null, null, null];

        if ('tl_page_geoip' !== $table) {
            return;
        }

        $event->setLabel($this->getCountryOptions()[$event->getData()['country']] ?? '-');
    }

    #[AsCallback('tl_page', 'config.onload')]
    public function addGlobalOperation(): void
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
     * @return array<string, string>
     */
    #[AsCallback('tl_page_geoip', 'fields.country.options')]
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
    #[AsCallback('tl_page_geoip', 'fields.pages.options')]
    public function getPagesOptions(): array
    {
        return $this->getRootPages();
    }

    /**
     * @return array<int|string, string>
     */
    private function getRootPages(): array
    {
        $this->rootPages ??= $this->connection->fetchAllKeyValue("SELECT id, title FROM tl_page WHERE type='root' ORDER BY sorting");

        return $this->rootPages;
    }
}
