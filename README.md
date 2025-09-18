# Editor Can Manage Privacy Options# Editor Can Manage Privacy Options



A lightweight WordPress plugin that grants the **Editor** role access to manage site Privacy Settings — capabilities normally restricted to Administrators.A WordPress plugin that allows Editors to manage privacy settings and access privacy admin pages, a capability normally reserved for Administrators.



## Why This Plugin?## Features

By default, only Administrators can configure the site's privacy policy settings (e.g., selecting the Privacy Policy page and accessing the Privacy Policy guide). This plugin safely extends that access to trusted Editors without broadly elevating their administrative capabilities.- Editors can access and modify privacy settings

- Adds Privacy menu under Settings for Editors

## Features- Handles duplicate menu entries gracefully

- Maps the core `manage_privacy_options` meta capability to the Editor-level capability `edit_pages` (filterable)- Secure: Only grants access to privacy pages, not full admin rights

- Adds the Privacy submenu under **Settings** for Editors (only if WordPress core hasn’t already exposed it)

- Prevents duplicate “Privacy” menu entries (CSS + defensive late cleanup)## How It Works

- Temporary elevation (request scoped) only on privacy-related pages as needed- Maps the `manage_privacy_options` capability to Editors (default: `edit_pages`)

- Avoids granting unrelated high-risk capabilities like `manage_options`- Adds the Privacy menu for Editors if not already present

- Heuristic admin detection (treats users with high-level caps as admins)- Temporarily grants required capabilities for privacy pages

- Cleans up duplicate menu entries and injects CSS for UI consistency

## How It Works (Technical)

The plugin hooks into:## Installation

- `map_meta_cap` → Remaps `manage_privacy_options` to a safer base capability (default: `edit_pages`)1. Upload the plugin folder to your `/wp-content/plugins/` directory.

- `admin_menu` → Adds the Privacy menu if not already present; also performs late duplicate cleanup2. Activate the plugin through the 'Plugins' menu in WordPress.

- `admin_init` → Enables a temporary capability grant for the current request if on a privacy page

- `user_has_cap` → Injects `manage_options` only when WordPress core checks it on privacy pages## Usage

- `admin_head`, `admin_print_styles`, `in_admin_footer` → Injects CSS to hide duplicate Privacy submenu itemsOnce activated, Editors will see and be able to use the Privacy settings under Settings > Privacy.



### Capability Mapping Filter## License

Developers can customize the base capability via the `epm_privacy_base_cap` filter:MIT

```php

add_filter( 'epm_privacy_base_cap', function( $default ) {## Author

    return 'edit_others_posts'; // or any appropriate capabilityPer Søderlind

});
```

## Installation
1. Upload the plugin folder to `wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Log in as an Editor — navigate to **Settings → Privacy**

## Requirements
- WordPress 5.2+ (earlier versions untested)
- PHP 7.4+ recommended

## Security Notes
- The plugin limits scope to privacy-related pages only
- No persistent role modification — all adjustments are dynamic
- Defensive checks prevent privilege creep into unrelated admin areas

## FAQ
**Does this let Editors change other site-wide admin options?**  
No. Only privacy-related access is facilitated.

**Can I change which role gets access?**  
Yes, by mapping to a different capability using the `epm_privacy_base_cap` filter.

**Why inject CSS for duplicates?**  
Rare timing edge cases can temporarily produce duplicate menu entries. CSS + late cleanup ensures a clean UI.

**Does it work in multisite?**  
It should, but multisite-specific elevated caps (e.g., network caps) are treated as admin-level and excluded.

## Development
Pull requests and issues welcome.

## License
MIT — see `LICENSE` file.

## Author
[Per Søderlind](https://github.com/soderlind)
