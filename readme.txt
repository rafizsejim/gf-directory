=== GF Directory ===
Contributors: rafizsejim
Tags: gravity forms, directory, listings, frontend, gravity forms addon
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turn Gravity Forms entries into a public directory with card and list views, search, filters, saves and a user dashboard.

== Description ==

GF Directory takes any Gravity Forms form's approved entries and displays them as a modern frontend directory.

Two card styles (Framed and Overlay), a list view, a hero search bar with up to four filter slots, single-entry detail pages with related listings, saved listings, and a logged-in user dashboard with Saved and My Submissions tabs.

Built as a clean Gravity Forms add-on. No code modifies the Gravity Forms plugin.

== Quick start ==

1. Install Gravity Forms (required).
2. Install and activate GF Directory.
3. Visit **Forms → Directory** in WP admin, click **Install demo** for a one-click sample directory with six pre-approved listings.

Or configure manually:

1. Form Settings → Directory tab on any form. Toggle "Enable directory."
2. Map fields to slots (title, image, price, etc.).
3. Approve entries via the Directory meta box on each entry.
4. Add `[gf_directory form="<id>"]` to a page.

== Frequently Asked Questions ==

= Does it modify the Gravity Forms plugin? =
No. It uses the public Gravity Forms add-on framework and the GFAPI. Nothing is written to the gravityforms plugin folder.

= Does it support custom templates? =
Yes. Every visible template can be overridden by placing a file at `{theme}/gf-directory/<template>.php`.

= How do I customize the cards? =
Map any form field to a semantic slot in the Directory tab on Form Settings: title, image, badge, price, rating, description, features, meta icons (4 slots), stats (3 slots), CTA URL. Reorder the visual layout by overriding templates.

= Where are saved listings stored? =
A custom table `wp_gfd_saves` keyed on `(user_id, entry_id)`. Custom table over user_meta because user_meta does not scale for indexed paginated lookups.

= Does it work with caching plugins? =
Yes. The plugin uses a version-bump transient strategy that invalidates on entry submit, edit, delete, and approval changes. No URLs are personalized via cache-buster, so page caches work correctly for anonymous visitors.

== Changelog ==

= 0.1.0 =
* Initial release.
