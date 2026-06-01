<?php
namespace Concrete\Package\MatomoCampaignLinks;

use Concrete\Core\Package\Package;
use Concrete\Core\Page\Single as SinglePage;
use Concrete\Core\Support\Facade\Route;
use Concrete\Core\Support\Facade\Events;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\View\View;
use Symfony\Component\HttpFoundation\Request;

class Controller extends Package
{
    protected $pkgHandle = 'matomo_campaign_links';
    protected $appVersionRequired = '9.0.0';
    protected $pkgVersion = '0.2.0';
    protected $pkgAutoloaderRegistries = [
        'controllers' => '\\Concrete\\Package\\MatomoCampaignLinks\\Controller',
    ];

    public function getPackageName()
    {
        return t('Matomo Campaign Links');
    }

    public function getPackageDescription()
    {
        return t('Adds a frontend toolbar button that shows Matomo campaign links for the current page.');
    }

    public function install()
    {
        $pkg = parent::install();

        $existing = Page::getByPath('/dashboard/system/seo/matomo_campaign_links');
        if (!$existing || $existing->isError()) {
            $sp = SinglePage::add('/dashboard/system/seo/matomo_campaign_links', $pkg);
            if (is_object($sp)) {
                $sp->update(['cName' => t('Matomo Campaign Links')]);
            }
        }

        return $pkg;
    }

    public function upgrade()
    {
        parent::upgrade();
        $this->ensureDashboardPage();
    }

    private function ensureDashboardPage(): void
    {
        $existing = Page::getByPath('/dashboard/system/seo/matomo_campaign_links');
        if (!$existing || $existing->isError()) {
            $sp = SinglePage::add('/dashboard/system/seo/matomo_campaign_links', $this);
            if (is_object($sp)) {
                $sp->update(['cName' => t('Matomo Campaign Links')]);
            }
        }
    }

    public function on_start()
    {
        Route::register('/ccm/matomo_campaign_links/dialog', '\\Concrete\\Package\\MatomoCampaignLinks\\Controller\\Dialog\\CampaignLinks::view');

        Events::addListener('on_page_view', function ($event) {
            $app = Application::getFacadeApplication();
            /** @var Request $request */
            $request = $app->make('request');
            if ($request->isXmlHttpRequest()) {
                return;
            }

            $page = $event->getPageObject();
            if (!$page || $page->isError()) {
                return;
            }

            // Keep this off Dashboard/system pages and only show it to users who may edit the page.
            if ($page->isAdminArea()) {
                return;
            }

            $permissions = new Checker($page);
            if (!$permissions->canEditPageContents() && !$permissions->canEditPageProperties()) {
                return;
            }

            $token = $app->make('token')->generate('matomo_campaign_links');
            $jsUrl = $this->getRelativePath() . '/js/toolbar.js';
            $cssUrl = $this->getRelativePath() . '/css/toolbar.css';

            $config = json_encode([
                'dialogEndpoint' => (string) Url::to('/ccm/matomo_campaign_links/dialog'),
                'cID' => (int) $page->getCollectionID(),
                'token' => $token,
                'buttonLabel' => t('Campaign Links'),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            /** @var \Concrete\Core\Application\Service\UserInterface\Menu $menuHelper */
            $menuHelper = $app->make('helper/concrete/ui/menu');
            $menuHelper->addPageHeaderMenuItem('matomo_campaign_links', $this->pkgHandle, [
                'icon' => 'share',
                'label' => t('Campaign Links'),
                'position' => 'right',
                'href' => '#',
                'linkAttributes' => [
                    'id' => 'mcl-toolbar-button',
                    'data-mcl-button' => '1',
                    'role' => 'button',
                ],
            ]);

            $view = View::getInstance();
            $view->addHeaderItem('<link rel="stylesheet" href="' . h($cssUrl) . '">');
            $view->addFooterItem('<script>window.MatomoCampaignLinks = ' . $config . ';</script>');
            $view->addFooterItem('<script src="' . h($jsUrl) . '"></script>');
        });
    }

}
