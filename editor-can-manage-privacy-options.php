<?php
/**
 * Plugin Name: Editor Can Manage Privacy Options
 * Description: Grants WordPress Editors the ability to manage privacy settings and access privacy admin pages.
 * Version: 1.1.1
 * Author: Per Søderlind
 * Author URI: https://github.com/soderlind
 * Plugin URI: https://github.com/soderlind/editor-can-manage-privacy-options
 * Text Domain: editor-can-manage-privacy-options
 * Domain Path: /languages
 * Requires at least: 5.2
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * This plugin extends the WordPress Editor role to include privacy management capabilities,
 * which are typically reserved for Administrators only.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class to handle privacy options for editors
 */
final class Editor_Privacy_Manager {

	/**
	 * Base capability to map 'manage_privacy_options' to (filterable).
	 * Default is an Editor-level cap.
	 */
	const BASE_PRIVACY_CAP = 'edit_pages';

	/**
	 * Initialize the plugin
	 */
	public static function init() {
		// Load translations.
		add_action( 'plugins_loaded', [ __CLASS__, 'load_textdomain' ] );
		// Map privacy capability to editors
		add_filter( 'map_meta_cap', [ __CLASS__, 'grant_privacy_capability' ], 10, 4 );

		// Add privacy menu for editors
		add_action( 'admin_menu', [ __CLASS__, 'add_privacy_menu_for_editors' ] );

		// Grant temporary access to privacy pages
		add_action( 'admin_init', [ __CLASS__, 'allow_editor_access_to_privacy_pages' ] );

		// Inject CSS to hide duplicate Privacy submenu (when core + custom both appear)
		add_action( 'admin_head', [ __CLASS__, 'hide_duplicate_privacy_menu_css' ] );
		// Additional fallbacks in case admin_head timing conflicts or is stripped.
		add_action( 'admin_print_styles', [ __CLASS__, 'hide_duplicate_privacy_menu_css' ] );
		add_action( 'in_admin_footer', [ __CLASS__, 'hide_duplicate_privacy_menu_css' ] );

		// Late cleanup to physically remove duplicates if they still exist.
		add_action( 'admin_menu', [ __CLASS__, 'cleanup_duplicate_privacy_menu' ], 999 );
	}

	/**
	 * Map the 'manage_privacy_options' capability to 'edit_pages' (Editor level)
	 * instead of 'manage_options' (Administrator level)
	 *
	 * @param string[] $caps    Array of capabilities required
	 * @param string   $cap     The capability being checked
	 * @param int      $user_id The user ID being checked
	 * @param array    $args    Additional arguments
	 * @return string[] Modified array of required capabilities
	 */
	public static function grant_privacy_capability( $caps, $cap, $user_id, $args ) {
		if ( 'manage_privacy_options' === $cap ) {
			$mapped = apply_filters( 'epm_privacy_base_cap', self::BASE_PRIVACY_CAP );
			return [ $mapped ];
		}
		return $caps;
	}

	/**
	 * Add Privacy menu item under Settings for Editors
	 * This ensures editors can see and access the privacy settings page
	 */
	public static function add_privacy_menu_for_editors() {
		// Only add menu for users who are editors but not administrators
		if ( ! self::is_editor_not_admin() ) {
			return;
		}

		// If core already exposed the Privacy page (due to capability remap) do not add a duplicate.
		global $submenu;
		if ( isset( $submenu[ 'options-general.php' ] ) ) {
			foreach ( $submenu[ 'options-general.php' ] as $item ) {
				// $item structure: [0] => Title, [1] => Capability, [2] => Slug
				if ( isset( $item[ 2 ] ) && 'options-privacy.php' === $item[ 2 ] ) {
					return; // Already present, skip adding second entry
				}
			}
		}

		// Add privacy submenu under Settings
		add_options_page(
			__( 'Privacy Settings', 'editor-can-manage-privacy-options' ), // Page title
			__( 'Privacy', 'editor-can-manage-privacy-options' ),          // Menu title
			self::BASE_PRIVACY_CAP,                                        // Required capability (mapped capability)
			'options-privacy.php'                                         // Menu slug (links to core privacy page)
		);
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'editor-can-manage-privacy-options', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Grant temporary access to privacy-related admin pages for editors
	 * This handles the actual page access when editors navigate to privacy settings
	 */
	public static function allow_editor_access_to_privacy_pages() {
		// Check if we're on a privacy-related page
		if ( ! self::is_privacy_page() ) {
			return;
		}

		// Check if current user is an editor (but not admin)
		if ( ! self::is_editor_not_admin() ) {
			return;
		}

		// Temporarily grant manage_options capability for this request only
		add_filter( 'user_has_cap', [ __CLASS__, 'temporarily_grant_admin_cap' ], 10, 4 );
	}

	/**
	 * Temporarily grant manage_options capability to editors on privacy pages
	 *
	 * @param array  $allcaps All capabilities of the user
	 * @param array  $caps    Required capabilities being checked
	 * @param array  $args    Additional arguments
	 * @param object $user    The user object
	 * @return array Modified capabilities array
	 */
	public static function temporarily_grant_admin_cap( $allcaps, $caps, $args, $user ) {
		// Only grant if checking for manage_options capability
		if ( in_array( 'manage_options', $caps, true ) ) {
			$allcaps[ 'manage_options' ] = true;
		}

		return $allcaps;
	}

	/**
	 * Check if current user is an editor but not an administrator (legacy heuristic)
	 *
	 * @return bool True if user is editor-level but not admin
	 */
	private static function is_editor_not_admin() {

		// Treat as admin if user has any high-level capability (avoid relying on manage_options).
		$admin_like_caps = [ 'activate_plugins', 'install_plugins', 'update_core', 'delete_users', 'promote_users', 'manage_network', 'manage_network_options' ];
		foreach ( $admin_like_caps as $cap_name ) {
			if ( current_user_can( $cap_name ) ) {
				return false; // User is effectively admin-level.
			}
		}
		// Editor-level inference: can edit others' content.
		return ( current_user_can( 'edit_others_posts' ) || current_user_can( 'edit_others_pages' ) );
	}

	/**
	 * Check if we're currently on a privacy-related admin page
	 *
	 * @return bool True if on privacy page
	 */
	private static function is_privacy_page() {
		global $pagenow;
		return in_array( $pagenow, [ 'options-privacy.php', 'privacy-policy-guide.php' ], true );
	}

	/**
	 * Output CSS that hides duplicate Privacy submenu entries (those without the wp-first-item class).
	 */
	public static function hide_duplicate_privacy_menu_css() {
		if ( ! self::is_editor_not_admin() ) {
			return;
		}
		?>
		<style id="editor-privacy-manager-css" data-epm="1">
			/* Duplicate Privacy submenu handling:
											 * Modern: hide entire LI containing the Privacy link and not first. Fallback hides anchor only.
											 * :has() support: Chrome 105+, Safari 15.4+, Firefox (flagged) – fallback keeps UX acceptable.
											 */
			#adminmenu .wp-submenu li:not(.wp-first-item):has(> a[href$="options-privacy.php"]) {
				display: none !important;
			}

			#adminmenu .wp-submenu li:not(.wp-first-item) a[href$="options-privacy.php"] {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Late physical duplicate removal (defensive). If more than one options-privacy.php entry exists, keep first.
	 */
	public static function cleanup_duplicate_privacy_menu() {
		if ( ! self::is_editor_not_admin() ) {
			return;
		}
		global $submenu;
		if ( empty( $submenu[ 'options-general.php' ] ) ) {
			return;
		}
		$found = false;
		foreach ( $submenu[ 'options-general.php' ] as $index => $item ) {
			if ( isset( $item[ 2 ] ) && 'options-privacy.php' === $item[ 2 ] ) {
				if ( ! $found ) {
					$found = true; // Keep first occurrence
					continue;
				}
				unset( $submenu[ 'options-general.php' ][ $index ] );
			}
		}
		// Reindex to avoid gaps.
		if ( $found ) {
			$submenu[ 'options-general.php' ] = array_values( $submenu[ 'options-general.php' ] );
		}
	}
}

// Initialize the plugin
Editor_Privacy_Manager::init();

