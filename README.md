# GF Directory

> Turn Gravity Forms entries into a polished public directory with cards, filters, search, saves and a logged-in user dashboard. Single-file install. No external services. No code touching the Gravity Forms plugin.

GF Directory is a Gravity Forms add-on that takes any form's approved entries and displays them as a modern frontend directory: card grid in two styles, list view, hero search bar with up to four filter slots, single-entry detail pages with related listings, plus saved listings and submissions tabs in a logged-in user dashboard.

Originally built to scratch a real itch: turning a community submission form into a browsable directory without commissioning a custom theme. Released as open source after the same pattern came up on a third project.

## Demo in 30 seconds

1. Install Gravity Forms.
2. Install GF Directory and activate.
3. Visit **Forms → Directory → Install demo**.
4. The plugin creates a Properties form, six pre-approved sample listings, an archive page and a dashboard page. The success screen links straight to all three.

## Features

| | |
|---|---|
| Per-form configuration | Enable directory mode on any form. Map fields to semantic slots (title, image, price, badge, rating, features, stats, meta icons, CTA). |
| Two card styles | **Framed** (white card, image on top) or **Overlay** (image fills card with gradient text). |
| List view | Horizontal rows with image, title, rating stars, feature chips, price stack and CTA. |
| View toggle | Visitor toggles between card and list when both are enabled. Admin sets the default. |
| Hero search | Free text, up to four configurable filter dropdowns, optional date range, sort, clear. URL-driven so every state is shareable. |
| Single page | Hero gallery with thumb switcher, sticky sidebar card with price + meta + CTA + save/share, sections for description / highlights / stats / details, related listings. |
| Saves | Logged-in users save listings to a custom table. Heart syncs across views in real time. |
| User dashboard | Two tabs: **Saved** (using the same card renderer) and **My submissions** (with status pills and view links). |
| Theme overrides | Every template is overridable at `{theme}/gf-directory/<file>.php`. |
| Light or dark hero | Pick any color, helper colors auto-adapt via luminance check. |
| One-click demo | Imports form, entries and pages. Idempotent. Removable. |
| Gutenberg block | Server-rendered. Reuses the shortcode render path. |

## Requirements

- WordPress 6.0+
- Gravity Forms 2.5+
- PHP 7.4+

## Installation

```
wp-content/plugins/
└── gf-directory/
```

Drop the folder in `wp-content/plugins/`, activate, done. No build step, no Composer install required for production use (PSR-4 autoloader is shipped with the plugin).

## Usage

### Shortcode

```
[gf_directory form="3"]
```

Single attribute: `form` (required, integer). All other configuration lives under **Form Settings → Directory**.

### Block

Insert the **GF Directory** block from the inserter. Set the form ID in the block sidebar.

### Dashboard

```
[gf_directory_dashboard]
```

No attributes. Logged-in users see Saved + My Submissions tabs. Anonymous visitors see a login prompt.

### Single entry URL

```
{your-directory-page}/listing/{entry_id}/
```

Created via `add_rewrite_endpoint` so it works on any host page without slug configuration. Falls back to `?gfd_entry=N` on sites without permalinks. Visit **Settings → Permalinks → Save** once after activation to flush.

## Architecture

```
src/
├── Plugin.php                          singleton bootstrap, dependency wiring
├── Autoloader.php                      PSR-4
├── Addon/DirectoryAddOn.php            extends GFAddOn, owns the per-form tab
├── Settings/FormSettings.php           read-only typed accessor
├── Query/
│   ├── EntryQuery.php                  one GFAPI::get_entries call per render
│   ├── SearchParser.php                URL → criteria choke point
│   └── Cache.php                       version-bump transient invalidation
├── Render/
│   ├── ArchiveRenderer.php             top-level orchestration
│   ├── CardRenderer.php                framed / overlay
│   ├── ListRenderer.php
│   ├── SearchBarRenderer.php
│   ├── PaginationRenderer.php
│   ├── SingleRenderer.php
│   ├── CardData.php                    entry → display slots, blocklist applied
│   └── TemplateLoader.php              theme override resolver
├── Frontend/
│   ├── Shortcode.php                   [gf_directory]
│   ├── DashboardShortcode.php          [gf_directory_dashboard]
│   ├── Rewrite.php                     /listing/{id}/ endpoint
│   └── Assets.php                      page-aware enqueue
├── Saves/
│   ├── SavesTable.php                  schema + maybe_install
│   ├── SavesRepository.php             prepared-statement DB layer
│   └── RestController.php              POST /save, GET /saves
├── Admin/
│   ├── EntryActions.php                approve / unapprove
│   ├── EntryColumn.php                 directory column on entry list
│   └── DemoInstaller.php               one-click demo installer
└── Support/
    ├── FieldBlocklist.php              never-public field types
    └── Sanitizer.php
```

### Performance

- One `GFAPI::get_entries()` call per archive page render. Returns entries plus total count in a single round trip.
- Filter dropdowns built from form definition, not data: zero extra queries.
- One save-state lookup per page (per logged-in user, scoped to form).
- Transient cache versioned by global counter; `update_option` on entry change instead of scanning transients to expire.
- Page-aware asset enqueueing: directory CSS/JS load only on pages with a `[gf_directory]` shortcode or block.
- Vanilla JS, no jQuery. ~5 KB.

### Security

- All output escaped at point of output: `esc_html`, `esc_url`, `esc_attr`, `wp_kses_post` for descriptions.
- All input sanitized at point of entry; `SearchParser` is the single choke point for URL → criteria.
- Per-entry-scoped nonces on admin actions to prevent confused-deputy.
- REST endpoints require login + REST nonce. Domain checks (entry-belongs-to-form, entry-is-public) before writing.
- Hard-coded blocklist of field types that may not be displayed publicly: `creditcard`, `password`, `consent`. `adminOnly` fields filtered separately.

### No-injection guarantee

The plugin never writes to `wp-content/plugins/gravityforms/`. It extends only the documented public class `GFAddOn` and uses only documented hooks (`gform_*`) and `GFAPI::*` static methods. All meta keys, options, transients, shortcodes, post types and rewrite tags carry our `gfd_` / `gf_directory_` prefix.

## Theme overrides

Drop a file at any of these paths in your theme to override:

```
{theme}/gf-directory/archive.php
{theme}/gf-directory/search-bar.php
{theme}/gf-directory/card-framed.php
{theme}/gf-directory/card-overlay.php
{theme}/gf-directory/list-item.php
{theme}/gf-directory/single.php
{theme}/gf-directory/single-not-found.php
{theme}/gf-directory/dashboard.php
{theme}/gf-directory/login-prompt.php
{theme}/gf-directory/empty.php
```

The `TemplateLoader` checks child theme, then parent theme, then plugin defaults.

## Developer hooks

| Hook | Type | When |
|---|---|---|
| `gfd_entry_publication_changed` | action | After admin approves/unapproves an entry. Args: `(int $entry_id, bool $is_public)`. Fired before cache bust. |

REST routes:

| Route | Method | Auth |
|---|---|---|
| `gf-directory/v1/save` | POST | Login + REST nonce |
| `gf-directory/v1/saves` | GET | Login + REST nonce |

## License

GPL-2.0-or-later. See [LICENSE](./LICENSE).

## Credits

Author: Rafiz Sejim.
