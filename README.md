# Matomo Campaign Links for concreteCMS 9

Adds a frontend toolbar button for editors. Clicking it opens a native concreteCMS dialog with generated Matomo campaign links for the current page, including source and content columns.

## Features

- Adds a concreteCMS page-header toolbar item for users who may edit the current page.
- Opens links in the native concreteCMS dialog.
- Uses the last URL segment as `mtm_campaign`.
- Uses configured rows for `mtm_source`, `mtm_medium`, and optional `mtm_content`.
- Shows only source and content in the dialog so editors can quickly identify the right channel/variant.
- Provides an icon-only copy button for every generated URL.
- Adds a dashboard page for editing parameter rows.

## Installation

Copy the folder `matomo_campaign_links` into your concreteCMS `packages/` directory and install it from:

`Dashboard > Extend Concrete > Add Functionality`

## Configuration

Go to:

`Dashboard > System & Settings > SEO & Statistics > Matomo Campaign Links`

Add one row per parameter series. Each row contains: active, source, medium and content. Medium is used in the generated Matomo URL for reporting, but it is not displayed in the frontend dialog.

## Storage

Presets are stored in the package config key `settings.presets`.
