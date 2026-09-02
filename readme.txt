=== Editor Can Manage Privacy Options ===
Contributors: PerS
Donate link: https://paypal.me/PerSoderlind
Tags: privacy, capabilities, editor, roles, permissions
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Grant Editors controlled access to WordPress Privacy Settings without giving them full administrator privileges.

== Description ==
By default only Administrators can manage Privacy Settings (select the Privacy Policy page, view the privacy policy guide, etc.). This plugin safely extends that capability to trusted Editors while avoiding broad elevation such as `manage_options`.

**Key Features**
* Remaps `manage_privacy_options` to an Editor-level capability (`edit_pages` by default)
* Adds the Privacy submenu under Settings only if not already exposed by core
* Guarantees a single Privacy menu entry via one idempotent registration
* Request-scoped temporary elevation only on privacy-related pages
* Heuristic detection to treat users with high-level caps as admin-equivalent

**Developer Friendly**
* Filter: `epm_privacy_base_cap` — change the base capability used for mapping
* Translation ready (`Text Domain: editor-can-manage-privacy-options`)

== Installation ==

1. **Quick Install**

   * Download [`editor-can-manage-privacy-options.zip`](https://github.com/soderlind/editor-can-manage-privacy-options/releases/latest/download/editor-can-manage-privacy-options.zip)
   * Upload via  Plugins > Add New > Upload Plugin
   * Activate the plugin.


2. **Updates**
   * Plugin updates are handled automatically via GitHub. No need to manually download and install updates.

== Frequently Asked Questions ==
= Does this allow Editors to change other site-wide options? =
No. Only the privacy-related settings/pages are made accessible.

= Can I change which role gets access? =
Yes. Use the `epm_privacy_base_cap` filter to return a different capability (e.g. `edit_others_posts`).

= Why might there be a duplicate Privacy menu? =
There isn't. The Privacy submenu is registered idempotently on a late admin_menu pass: if core already exposed it the plugin leaves it alone, otherwise it adds it — so there is always exactly one entry.

= Does this work in multisite? =
Yes, but users with network-level capabilities are treated as effectively admin and won't need the mapping.

= Is this secure? =
Yes; no permanent role modifications are stored. Adjustments are request-scoped and limited to privacy pages.

== Changelog ==
= 1.3.0 =
* Relicensed from MIT to GPLv2 or later.
* Refactored privacy logic into a testable policy module behind a WordPress environment seam.
* Consolidated duplicate Privacy menu prevention into one idempotent registration (removed admin CSS injection).
* Added an acceptance test suite (`composer test`).
* Documentation: automatic GitHub updates and correct Composer package name.

= 1.2.1 =
* Version bump only, no functional changes.

= 1.2.0 =
* Added WordPress.org `readme.txt`
* Updated compatibility (Tested up to 6.8, PHP 8.2)
* Raised minimum requirements (WP 6.5, PHP 8.2)
* Documentation refinements (README cleanup)

= 1.1.1 =
* Added defensive duplicate Privacy menu cleanup
* Added multiple CSS injection hooks for edge cases
* Improved heuristic for distinguishing Editors from admin-level users
* Updated documentation and inline comments

= 1.0.0 =
* Initial release adding Editor access to Privacy Settings via capability remap

== Upgrade Notice ==
= 1.3.0 =
Internal refactor with the same behavior, plus an acceptance test suite. Safe update.

= 1.2.1 =
No functional changes; safe to skip unless you need the normalized version reference.

= 1.2.0 =
Adds WordPress.org readme, updates compatibility and requirements. Update recommended.

= 1.1.1 =
Improved duplicate menu handling and refined capability checks. Update recommended.

== Filters ==
`epm_privacy_base_cap` — Change the base capability that `manage_privacy_options` is mapped to. Default: `edit_pages`.

Example:
```
add_filter( 'epm_privacy_base_cap', function( $default ) {
    return 'edit_others_posts';
} );
```

== License ==
GPLv2 or later — see LICENSE file.
