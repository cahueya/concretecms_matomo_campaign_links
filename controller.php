<?php
namespace Concrete\Package\MatomoCampaignLinks;

defined('C5_EXECUTE') or die('Access Denied.');

use Concrete\Core\Asset\AssetList;
use Concrete\Core\Entity\Package as PackageEntity;
use Concrete\Core\Http\ResponseAssetGroup;
use Concrete\Core\Package\Package;
use Concrete\Core\Page\Page;
use Concrete\Core\Page\Single as SinglePage;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Events;
use Concrete\Core\Support\Facade\Route;
use Concrete\Core\Support\Facade\Url;

class Controller extends Package
{
    protected $pkgHandle = 'matomo_campaign_links';
    protected $appVersionRequired = '9.0.0';
    protected $pkgVersion = '0.3.3';
    protected $pkgAutoloaderRegistries = [
        'controllers' => '\\Concrete\\Package\\MatomoCampaignLinks\\Controller',
    ];

    public function getPackageName()
    {
        return t('Matomo Campaign Links');
    }

    public function getPackageDescription()
    {
        return t('Adds a frontend toolbar button that generates Matomo campaign links for the current page.');
    }

    public function install()
    {
        $pkg = parent::install();
        $this->ensureDashboardPage($pkg);

        return $pkg;
    }

    public function upgrade()
    {
        parent::upgrade();
        $pkg = $this->getPackageEntity();
        if ($pkg) {
            $this->ensureDashboardPage($pkg);
        }
    }

    public function on_start()
    {
        $assetList = AssetList::getInstance();
        $assetList->register(
            'javascript',
            'matomo_campaign_links/toolbar',
            'js/toolbar.js',
            ['version' => $this->pkgVersion],
            $this
        );
        $assetList->register(
            'javascript',
            'matomo_campaign_links/dashboard',
            'js/dashboard.js',
            ['version' => $this->pkgVersion],
            $this
        );

        Route::register(
            '/ccm/matomo_campaign_links/dialog',
            '\\Concrete\\Package\\MatomoCampaignLinks\\Controller\\Dialog\\CampaignLinks::view'
        );

        Events::addListener('on_page_view', function ($event) {
            $app = Application::getFacadeApplication();

            $request = $app->make('request');
            if ($request->isXmlHttpRequest()) {
                return;
            }

            $page = $event->getPageObject();
            if (!$page || $page->isError() || $page->isAdminArea()) {
                return;
            }

            $permissions = new Checker($page);
            if (!$permissions->canEditPageContents() && !$permissions->canEditPageProperties()) {
                return;
            }

            $token = $app->make('token')->generate('matomo_campaign_links');
            $dialogUrl = (string) Url::to('/ccm/matomo_campaign_links/dialog');
            $dialogUrl .= '?cID=' . (int) $page->getCollectionID();
            $dialogUrl .= '&ccm_token=' . rawurlencode($token);

            /** @var \Concrete\Core\Application\Service\UserInterface\Menu $menuHelper */
            $menuHelper = $app->make('helper/concrete/ui/menu');
            $menuHelper->addPageHeaderMenuItem('matomo_campaign_links', $this->pkgHandle, [
                'icon' => 'share',
                'label' => t('Campaign Links'),
                'position' => 'right',
                'href' => $dialogUrl,
                'linkAttributes' => [
                    'class' => 'dialog-launch',
                    'dialog-title' => t('Campaign Links'),
                    'dialog-width' => '980',
                    'dialog-height' => '520',
                    'dialog-modal' => 'false',
                    'aria-haspopup' => 'dialog',
                ],
            ]);

            ResponseAssetGroup::get()->requireAsset('javascript', 'matomo_campaign_links/toolbar');
        });
    }

    private function ensureDashboardPage(PackageEntity $pkg): void
    {
        $existing = Page::getByPath('/dashboard/system/seo/matomo_campaign_links');
        if ($existing && !$existing->isError()) {
            return;
        }

        $page = SinglePage::add('/dashboard/system/seo/matomo_campaign_links', $pkg);
        if (is_object($page)) {
            $page->update(['cName' => t('Matomo Campaign Links')]);
        }
    }
}
