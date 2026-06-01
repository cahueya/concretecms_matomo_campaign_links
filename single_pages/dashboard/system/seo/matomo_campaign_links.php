<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<style>
    .mcl-dashboard-help {
        margin-bottom: 1.5rem;
    }

    .mcl-presets-table th,
    .mcl-presets-table td {
        vertical-align: middle;
    }

    .mcl-presets-table .mcl-active-cell {
        text-align: center;
        width: 72px;
    }

    .mcl-presets-table .mcl-remove-cell {
        text-align: right;
        width: 58px;
    }

    .mcl-presets-table input[type="text"] {
        min-width: 150px;
    }

    .mcl-table-scroll {
        overflow-x: auto;
    }

    .mcl-add-row-wrapper {
        margin-top: .75rem;
    }
</style>

<form method="post" id="mcl-presets-form">
    <?php $token->output('save_matomo_campaign_links'); ?>

    <div class="alert alert-info mcl-dashboard-help">
        <?= t('Define the parameter series shown in the frontend Campaign Links modal.') ?><br>
        <?= t('Campaign is generated automatically from the last URL segment of the current page. Medium is kept for Matomo reporting but is not shown in the frontend dialog.') ?>
    </div>

    <fieldset>
        <legend><?= t('Parameter series') ?></legend>

        <div class="mcl-table-scroll">
            <table class="table table-striped table-hover mcl-presets-table" id="mcl-presets-table">
                <thead>
                    <tr>
                        <th class="mcl-active-cell"><?= t('Active') ?></th>
                        <th><?= t('Source') ?></th>
                        <th><?= t('Medium') ?></th>
                        <th><?= t('Content') ?></th>
                        <th class="mcl-remove-cell"><span class="visually-hidden sr-only"><?= t('Actions') ?></span></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($presets as $index => $preset) { ?>
                    <tr class="mcl-preset-row">
                        <td class="mcl-active-cell">
                            <input type="hidden" name="presets[<?= (int) $index ?>][active]" value="0">
                            <input type="checkbox" name="presets[<?= (int) $index ?>][active]" value="1" <?= !empty($preset['active']) ? 'checked' : '' ?> aria-label="<?= t('Active') ?>">
                        </td>
                        <td>
                            <input type="text" name="presets[<?= (int) $index ?>][source]" value="<?= h($preset['source']) ?>" class="form-control">
                        </td>
                        <td>
                            <input type="text" name="presets[<?= (int) $index ?>][medium]" value="<?= h($preset['medium']) ?>" class="form-control">
                        </td>
                        <td>
                            <input type="text" name="presets[<?= (int) $index ?>][content]" value="<?= h($preset['content']) ?>" class="form-control">
                        </td>
                        <td class="mcl-remove-cell">
                            <button type="button" class="btn btn-sm btn-danger mcl-remove-row" aria-label="<?= t('Remove row') ?>">&minus;</button>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="mcl-add-row-wrapper">
            <button type="button" class="btn btn-secondary" id="mcl-add-row">+ <?= t('Add row') ?></button>
        </div>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button class="btn btn-primary pull-right float-end" type="submit"><?= t('Save') ?></button>
        </div>
    </div>
</form>

<template id="mcl-row-template">
    <tr class="mcl-preset-row">
        <td class="mcl-active-cell">
            <input type="hidden" data-name="active" value="0">
            <input type="checkbox" data-name="active" value="1" checked aria-label="<?= t('Active') ?>">
        </td>
        <td><input type="text" data-name="source" class="form-control"></td>
        <td><input type="text" data-name="medium" class="form-control"></td>
        <td><input type="text" data-name="content" class="form-control"></td>
        <td class="mcl-remove-cell"><button type="button" class="btn btn-sm btn-danger mcl-remove-row" aria-label="<?= t('Remove row') ?>">&minus;</button></td>
    </tr>
</template>

<script>
(function () {
    'use strict';

    var table = document.getElementById('mcl-presets-table');
    var tbody = table ? table.querySelector('tbody') : null;
    var addButton = document.getElementById('mcl-add-row');
    var template = document.getElementById('mcl-row-template');

    if (!tbody || !addButton || !template) {
        return;
    }

    function renumberRows() {
        Array.prototype.forEach.call(tbody.querySelectorAll('tr.mcl-preset-row'), function (row, index) {
            Array.prototype.forEach.call(row.querySelectorAll('[data-name], [name]'), function (field) {
                var fieldName = field.getAttribute('data-name');
                if (!fieldName && field.name) {
                    var match = field.name.match(/\[([^\]]+)\]$/);
                    fieldName = match ? match[1] : null;
                    if (fieldName) {
                        field.setAttribute('data-name', fieldName);
                    }
                }
                if (fieldName) {
                    field.name = 'presets[' + index + '][' + fieldName + ']';
                }
            });
        });
    }

    function addRow() {
        var fragment = template.content.cloneNode(true);
        tbody.appendChild(fragment);
        renumberRows();
    }

    addButton.addEventListener('click', addRow);

    tbody.addEventListener('click', function (event) {
        var removeButton = event.target.closest('.mcl-remove-row');
        if (!removeButton) {
            return;
        }

        var row = removeButton.closest('tr');
        if (row) {
            row.parentNode.removeChild(row);
        }

        if (!tbody.querySelector('tr.mcl-preset-row')) {
            addRow();
        } else {
            renumberRows();
        }
    });

    renumberRows();
})();
</script>
