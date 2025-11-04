# Dusty Sun WP Settings API (v2.2)

A small, JSON-driven helper for building WordPress settings pages quickly. Register sections/fields, render a complete tabbed admin panel, or just output fields inside your own markup.

**Namespace:** `DustySun\WP_Settings_API\v2_2`  
**Primary class:** `SettingsBuilder`  
**Current file version:** `2.2.0`

---

## Features

- **Build a complete panel or only fields**  
  Use `build_settings_panel()` for a full, tabbed UI, or just register + render fields inside your own HTML.

- **JSON-first config**  
  Define sections, tabs, and fields in one JSON file. Optional PHP “views” let you drop in richer content per tab.

- **Per-field sanitization & validation**  
  Numbers, colors, arrays, protected (encrypted) fields, etc., with errors surfaced via `add_settings_error()`.

- **Post selector (new in 2.0.8)**  
  `type: "post_select"` to pick posts/CPTs (sorted by title by default, supports multi-select, IDs in label, and Select2).

- **Version auto-detection (new in 2.0.8)**  
  Pass your main plugin file and/or explicit version when constructing; the library fills `main_settings.version` for you if the JSON omits it.

- **Select2 support (bundled in admin)**  
  Select2 assets (4.1.0) are registered/enqueued on your settings page so `post_select` can be searchable.

- **Safe reset endpoint (admin-only)**  
  The AJAX reset action is registered only when `register_settings` is `true` and in the admin.

---

## Installation

1. Drop the library into your plugin or theme (e.g. `lib/dustysun-wp-settings-api/`).
2. Require the file from your code.
3. Create a JSON config file that describes your settings.
4. *(Optional)* Add PHP view files per tab in a `views/` directory.

```php
require_once plugin_dir_path(__FILE__) . 'lib/dustysun-wp-settings-api/ds_wp_settings_api.php';
```

---

## Usage

### Instantiate for runtime (no admin UI)

```php
use DustySun\WP_Settings_API\v2 as DSWP;

$settings = new DSWP\SettingsBuilder([
  'json_file'        => plugin_dir_path(__FILE__) . 'my-plugin-settings.json',
  'plugin_file'      => __FILE__,     // lets the library read your plugin header
  // 'plugin_version' => '1.2.3',     // optional fast path if you already have it
  'register_settings'=> false         // don't build admin UI here
]);

$current = $settings->get_current_settings(); // merged defaults + DB
$main    = $settings->get_main_settings();    // main_settings (+ info/unique_id)
```

### Instantiate for admin (with UI)

```php
if (is_admin()) {
  $settings_page = new DSWP\SettingsBuilder([
    'json_file'        => plugin_dir_path(__FILE__) . 'my-plugin-settings.json',
    'plugin_file'      => __FILE__,           // enables version auto-detection
    'register_settings'=> true,               // build UI
    'views_dir'        => plugin_dir_path(__FILE__) . 'admin/views'
  ]);

  add_action('admin_menu', function () use ($settings_page) {
    add_submenu_page(
      'options-general.php',
      __('My Plugin Settings', 'my_text_domain'),
      __('My Plugin Settings', 'my_text_domain'),
      'manage_options',
      'my-plugin-settings',
      function () use ($settings_page) {
        $settings_page->build_settings_panel();
      }
    );
  });
}
```

> It’s fine to have two instances—one “read-only” (front/global) and one admin-only that builds the UI. The library only binds the reset AJAX endpoint for the admin/UI instance.

---

## Constructor Options

Pass an associative array when creating `SettingsBuilder`:

| Key                | Type      | Required | Description |
|--------------------|-----------|----------|-------------|
| `json_file`        | string    | No*      | Absolute path to your JSON config. *If omitted, the library looks for a JSON file adjacent to this PHP file with the same base filename.* |
| `plugin_file`      | string    | No       | Absolute path to your main plugin file so the library can read header data (incl. `Version`). |
| `plugin_version`   | string    | No       | Directly supply a version (skips header reads). |
| `register_settings`| bool      | No       | If `true`, registers settings/sections/fields and loads admin assets. |
| `views_dir`        | string    | No       | Directory containing optional per-tab PHP view files. Defaults to `dirname(__FILE__).'/views'`. |

---

## JSON Schema

Your JSON controls everything. Core sections:

- `main_settings` — panel metadata & behavior  
- `options` — one or more settings sections (tabs)  
- `about_sections` — optional static tabs

### `main_settings` keys

| Key              | Description                                                                  |
|------------------|------------------------------------------------------------------------------|
| `name`           | Friendly name shown at top of the panel                                      |
| `text_domain`    | Text domain for translations                                                 |
| `tabs`           | `"true"` (default) for tabbed UI; `"false"` for a single page                |
| `options_suffix` | Suffix appended to each section’s option key (default `""`)                  |
| `page_suffix`    | Suffix used to build per-tab pages (default `"_page"`)                       |
| `author`         | Author name                                                                  |
| `author_uri`     | Author URL                                                                   |
| `item_uri`       | Plugin home / docs                                                           |
| `support_uri`    | Support URL                                                                  |
| `support_email`  | Support email                                                                |
| `page_slug`      | Slug for the admin page                                                      |
| `item_slug`      | Item slug; defaults to `page_slug`                                           |
| `version`        | **Optional** (auto-filled from host plugin if omitted)                       |

> You can include extra keys; they’ll be available via `get_main_settings()`.

### `options` (sections/tabs)

Each section has a `title`, optional `tab_label`, and a `fields` array.

### `about_sections`

Optional additional tabs with static content. You can pair these with PHP view files (see **Views** below).

---

## Field Types

Built-in `type` values:

- `text` (supports `required`, `randomize`)
- `number` (`min`, `max`, `step`, `required`)
- `select` (`options` map)
- `checkbox` (`options` map; stores array)
- `radio` (`options` map)
- `radio_on_off` (two options only)
- `color_picker`
- `fontawesome_picker`
- `hidden`
- `password` *(input type=password)*
- `protected` *(stored encrypted)*
- `multifield_text` *(repeating text inputs)*
- **`post_select` (new in 2.0.8)**

### `post_select` (posts/CPTs picker)

- Queries posts of one or more `post_type`s.  
- Defaults: `post_status = publish`, `orderby = title`, `order = ASC`.  
- `multiple: true` → stores an **array of integers**; single stores an **integer**.  
- `select2: true` → adds a class for Select2 (assets are enqueued by the library on your settings page).  
- Labels display as `"{post_title} (ID: {id})"`.

**JSON example (single select, searchable)**

```json
{
  "id": "featured_post",
  "label": "Featured Post",
  "type": "post_select",
  "post_type": "post",
  "select2": true,
  "placeholder": "Choose a post…"
}
```

**JSON example (multi-select across CPTs)**

```json
{
  "id": "highlighted_items",
  "label": "Highlighted Items",
  "type": "post_select",
  "post_type": ["games", "web_activities"],
  "multiple": true,
  "select2": true,
  "posts_per_page": -1,
  "orderby": "title",
  "order": "ASC",
  "placeholder": "Start typing to search…"
}
```

**Sanitization**  
- For `post_select`: single values are saved as `absint`; multiple values are mapped to unique `absint` array.  
- Other types use the class’ appropriate validator (text, number, color, arrays, protected).

---

## Views

Supply a `views_dir` when constructing. The library will include (if present):

- `views/main_settings.php` → rendered beneath the panel title  
- `views/{option_section_key}.php` → rendered at top of that tab  
- `views/{about_section_key}.php` → rendered for about tabs

> Files are included and captured via output buffering. Escape output appropriately.

---

## Admin Assets

When the current screen matches your settings page, the library enqueues:

- Google Fonts: **Open Sans** and **Montserrat**
- WordPress **Color Picker** (`wp-color-picker`)
- **Font Awesome** v5.1.1 (for icon picker)
- Library CSS/JS (`ds-wp-settings-api-admin.css`, `ds-wp-settings-api-admin.js`)
- **Select2** v4.1.0 CSS/JS (for `post_select` when `select2: true`)

---

## AJAX: Reset All Settings

Use `get_reset_ajax_form()` to output a simple form that triggers a full reset (optionally deleting DB-stored values). The AJAX action is registered only when `register_settings` is `true` and in the admin:

```
ds_wp_api_reset_settings-{item_slug}
```

> **Security tip:** If you expose this action via a button in your own UI, wrap it with a capability check and a nonce.

--- 

## Quick Retrieval

1. Read-only, anywhere (no UI instance needed):
- use DustySun\WP_Settings_API\v2\Options;
- $val = Options::get('my_text_domain', 'my_section', 'my_field', 'default');

2. From the UI/SettingsBuilder instance you already created:
- $val = $settings_page->get_field('my_section', 'my_field', 'default');

---

## Public Methods

- `get_main_settings()` → array of `main_settings` (plus `info`, `unique_id`)  
- `get_current_settings()` → merged defaults + DB values per section  
- `set_main_settings($update_db = false, $reset_defaults = false)` → seeds/updates the `{text_domain}_main_settings` option; `$reset_defaults` deletes first  
- `build_settings_panel($title = null, $header_content = null)` → renders the full admin UI  
- `get_reset_ajax_form()` → returns HTML for the reset form

---

## What Happens Under the Hood

- **JSON loading**: reads your JSON (or auto-locates a `*.json` next to the library).  
- **Defaults & version**: merges `main_settings`; if `version` is empty, resolves from `plugin_version` or the header of `plugin_file` (via `get_plugin_data` with a `get_file_data` fallback).  
- **Unique ID**: generates/persists `{text_domain}_uid` for encryption helper.  
- **Admin assets**: enqueued only when the current admin screen matches your settings page hook/slug.

---

## Changelog
### 2.1.0 - 2025-10-31
* Added support for a URL type field.
* Added new version 2_2.

### 2.1.0 - 2025-09-09
* Added a textarea type.

### 2.0.9 - 2025-08-16
- **Host plugin version resolution**: pass `plugin_file` and/or `plugin_version`; JSON `version` now optional.
- **New field:** `post_select` (sorted by title; supports `multiple`, `select2`, placeholders; labels include ID).
- **Select2** (4.1.0) registered/enqueued on the settings page.
- **Reset AJAX** is only bound for the admin/UI instance.

### 2.0.6 — 2020-12-03
- Fixed spacing of select dropdowns.

### 2.0.4 — 2019-08-20
- Added password field type and incomplete decrypt functions.

### 2.0.3 — 2019-02-28
- Fixed info/view pages not showing for option tabs.
- Added `tab_order`, toggle classes, and anchor tab behavior.

### 2.0.2 — 2019-01-16
- Fixed page-slug checks for loading styles with submenu prefixes.

### 2.0.1 — 2018-09-13
- Added ability to skip an option or about tab (`skip: true`).

### 1.0.7 — 2018-07-11
- Added `checkbox` field and `multifield_text`.

#### 1.0.6 - 2018-05-21
- Adjustments to appearance of certain elements in the CSS.

#### 1.0.5 - 2018-02-21
- Changed error display for inputs - it still shows the message beneath each input that has an error but also outputs all the errors at the top of the screen.

#### 1.0.4 - 2018-02-11
- Added function to create an Ajax form that allows a reset/deletion of all options in the database.

#### 1.0.3 - 2018-02-07
- Tab pages did not have a correct URL. Added a page_slug option to the JSON file which will fill in the URL to the tabs correctly.
- Fixed a bug where the first save of a new option array would not save because the hidden option key name was not being set in the validate function. (Actually the validate function is running twice in WP for some reason with this first save, stripping the hidden value.) Worked around this issue by making sure that value is set and if not, it pulls it from the POST data which should have it.

#### 1.0.2 - 2018-02-03
- Changed method of instantiation - now must pass an array containing 'json_file' with a path to the json file and 'register_settings' set to true or false. If any of these items are not passed the plugin will not register settings and will look for a JSON file in the same directory as the php file to load.

#### 1.0.1 - 2018-01-27
- Fixed an issue with the settings library to allow having hidden options.
- Fixed issue with the current settings function being called too many times.

---

## License

GPLv2 (or later).

---

## Links

- GitHub: https://github.com/srtalley/dustysun-wp-settings-api  
- Author: Steve Talley — https://dustysun.com/
