<?php
namespace Concrete\Package\MatomoCampaignLinks\Controller\SinglePage\Dashboard\System\Seo;

use Concrete\Core\Page\Controller\DashboardPageController;

class MatomoCampaignLinks extends DashboardPageController
{
    public function view()
    {
        if ($this->request->isMethod('POST')) {
            return $this->save();
        }

        $this->set('presets', $this->getPresets());
    }

    public function save()
    {
        if (!$this->token->validate('save_matomo_campaign_links')) {
            $this->error->add($this->token->getErrorMessage());
            $this->view();
            return;
        }

        $input = $this->request->request->get('presets', []);
        if (!is_array($input)) {
            $input = [];
        }

        $presets = $this->parsePresetRows($input);

        if ($this->error->has()) {
            $this->set('presets', $this->normalizeRowsForView($input));
            return;
        }

        $pkg = \Package::getByHandle('matomo_campaign_links');
        $pkg->getConfig()->save('settings.presets', json_encode($presets, JSON_UNESCAPED_UNICODE));
        $this->flash('success', t('The campaign link presets have been saved.'));
        $this->redirect('/dashboard/system/seo/matomo_campaign_links');
    }

    private function getPresets(): array
    {
        $pkg = \Package::getByHandle('matomo_campaign_links');
        $json = $pkg->getConfig()->get('settings.presets') ?: '[]';
        $presets = json_decode($json, true);
        if (!is_array($presets)) {
            $presets = [];
        }

        $rows = [];
        foreach ($presets as $preset) {
            if (!is_array($preset)) {
                continue;
            }
            $rows[] = [
                'active' => !empty($preset['active']) ? '1' : '0',
                'source' => (string) ($preset['source'] ?? ''),
                'medium' => (string) ($preset['medium'] ?? ''),
                'content' => (string) ($preset['content'] ?? ''),
            ];
        }

        if (!$rows) {
            $rows[] = $this->emptyRow();
        }

        return $rows;
    }

    private function normalizeRowsForView(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized[] = [
                'active' => !empty($row['active']) ? '1' : '0',
                'source' => (string) ($row['source'] ?? ''),
                'medium' => (string) ($row['medium'] ?? ''),
                'content' => (string) ($row['content'] ?? ''),
            ];
        }

        if (!$normalized) {
            $normalized[] = $this->emptyRow();
        }

        return $normalized;
    }

    private function emptyRow(): array
    {
        return [
            'active' => '1',
            'source' => '',
            'medium' => '',
            'content' => '',
        ];
    }

    private function generateKey(string $source, string $content, int $rowNumber): string
    {
        $base = trim($source . ' ' . $content);
        if ($base === '') {
            $base = 'preset_' . $rowNumber;
        }

        $base = strtolower($base);
        $base = preg_replace('/[^a-z0-9]+/', '_', $base);
        $base = trim((string) $base, '_');

        return $base !== '' ? $base : 'preset_' . $rowNumber;
    }

    private function generateLabel(string $source, string $content): string
    {
        $source = trim($source);
        $content = trim($content);

        if ($source !== '' && $content !== '') {
            return $source . ', ' . $content;
        }

        return $source !== '' ? $source : $content;
    }

    private function parsePresetRows(array $rows): array
    {
        $presets = [];
        $seenKeys = [];
        $rowNumber = 0;

        foreach ($rows as $row) {
            ++$rowNumber;
            if (!is_array($row)) {
                continue;
            }

            $active = !empty($row['active']);
            $source = trim((string) ($row['source'] ?? ''));
            $medium = trim((string) ($row['medium'] ?? ''));
            $content = trim((string) ($row['content'] ?? ''));

            // Completely empty rows are ignored. This lets editors add a row and save later.
            if ($source === '' && $medium === '' && $content === '') {
                continue;
            }

            if ($source === '') {
                $this->error->add(t('Row %s: source must not be empty.', $rowNumber));
                continue;
            }

            if ($medium === '') {
                $this->error->add(t('Row %s: medium must not be empty.', $rowNumber));
                continue;
            }

            $key = $this->generateKey($source, $content, $rowNumber);
            $uniqueKey = $key;
            $duplicateIndex = 2;
            while (isset($seenKeys[$uniqueKey])) {
                $uniqueKey = $key . '_' . $duplicateIndex;
                ++$duplicateIndex;
            }
            $seenKeys[$uniqueKey] = true;

            $presets[] = [
                'active' => $active,
                'key' => $uniqueKey,
                'label' => $this->generateLabel($source, $content),
                'source' => $source,
                'medium' => $medium,
                'content' => $content,
            ];
        }

        return $presets;
    }
}
