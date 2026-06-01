<?php
namespace Concrete\Package\MatomoCampaignLinks\Controller\Dialog;

use Concrete\Core\Controller\Controller;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker;
use Concrete\Core\Support\Facade\Url;
use Symfony\Component\HttpFoundation\Response;

class CampaignLinks extends Controller
{
    public function view()
    {
        $request = $this->request;
        $token = Application::getFacadeApplication()->make('token');

        if (!$token->validate('matomo_campaign_links', $request->query->get('ccm_token'))) {
            return new Response($this->renderError(t('Invalid security token.')), 403);
        }

        $cID = (int) $request->query->get('cID');
        $page = Page::getByID($cID);
        if (!$page || $page->isError() || $page->isAdminArea()) {
            return new Response($this->renderError(t('Invalid page.')), 404);
        }

        $permissions = new Checker($page);
        if (!$permissions->canEditPageContents() && !$permissions->canEditPageProperties()) {
            return new Response($this->renderError(t('Access denied.')), 403);
        }

        $pkg = \Package::getByHandle('matomo_campaign_links');
        $presetsJson = $pkg->getConfig()->get('settings.presets') ?: '[]';
        $presets = json_decode($presetsJson, true);
        if (!is_array($presets)) {
            $presets = [];
        }

        $baseUrl = (string) Url::to($page);
        $campaign = $this->getCampaignFromPagePath($page);
        $links = [];

        foreach ($presets as $preset) {
            if (!is_array($preset) || empty($preset['active'])) {
                continue;
            }

            $params = [
                'mtm_campaign' => $campaign,
                'mtm_source' => $preset['source'] ?? '',
                'mtm_medium' => $preset['medium'] ?? '',
            ];

            if (!empty($preset['content'])) {
                $params['mtm_content'] = $preset['content'];
            }

            $links[] = [
                'label' => $preset['label'] ?? $this->generateLabel((string) ($preset['medium'] ?? ''), (string) ($preset['content'] ?? '')),
                'url' => $this->appendQuery($baseUrl, array_filter($params, static function ($value) {
                    return $value !== null && $value !== '';
                })),
            ];
        }

        return new Response($this->renderDialog($page->getCollectionName(), $campaign, $links));
    }

    private function renderDialog(string $pageName, string $campaign, array $links): string
    {
        $html = '<div class="ccm-ui mcl-dialog-content">';
        $html .= '<div class="alert alert-info mcl-page-info">';
        $html .= '<strong>' . $this->h($pageName) . '</strong><br>';
        $html .= t('Campaign') . ': <code>' . $this->h($campaign) . '</code>';
        $html .= '</div>';

        if (!$links) {
            $html .= '<p>' . t('No active presets found.') . '</p>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped table-hover mcl-links-table">';
        $html .= '<tbody>';
        foreach ($links as $link) {
            $url = (string) ($link['url'] ?? '');
            $label = (string) ($link['label'] ?? '');
            $html .= '<tr>';
            $html .= '<th scope="row" class="mcl-link-label">' . $this->h($label) . '</th>';
            $html .= '<td><input type="text" readonly class="form-control mcl-link-input" value="' . $this->h($url) . '"></td>';
            $html .= '<td class="mcl-copy-cell"><button type="button" class="btn btn-secondary btn-sm mcl-copy" data-url="' . $this->h($url) . '" title="' . t('Copy link') . '" aria-label="' . t('Copy link') . '"><i class="fas fa-copy" aria-hidden="true"></i></button></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div></div>';

        return $html;
    }

    private function renderError(string $message): string
    {
        return '<div class="ccm-ui"><div class="alert alert-danger">' . $this->h($message) . '</div></div>';
    }

    private function getCampaignFromPagePath(Page $page): string
    {
        $path = trim((string) $page->getCollectionPath(), '/');
        if ($path === '') {
            return 'home';
        }

        $segments = explode('/', $path);
        return (string) end($segments);
    }

    private function appendQuery(string $url, array $params): string
    {
        $separator = strpos($url, '?') === false ? '?' : '&';
        return $url . $separator . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private function generateLabel(string $medium, string $content): string
    {
        $medium = trim($medium);
        $content = trim($content);

        if ($medium !== '' && $content !== '') {
            return $medium . ', ' . $content;
        }

        return $medium !== '' ? $medium : $content;
    }

    private function h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, APP_CHARSET ?: 'UTF-8');
    }
}
