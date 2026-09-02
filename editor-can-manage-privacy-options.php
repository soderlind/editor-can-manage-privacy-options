<?php
/**
 * Plugin Name: Editor Can Manage Privacy Options
 * Description: Grants WordPress Editors the ability to manage privacy settings and access privacy admin pages.
 * Version: 1.3.1
 * Author: Per Søderlind
 * Author URI: https://github.com/soderlind
 * Plugin URI: https://github.com/soderlind/editor-can-manage-privacy-options
 * Text Domain: editor-can-manage-privacy-options
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 7.1
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * This plugin extends the WordPress Editor role to include privacy management capabilities,
 * which are typically reserved for Administrators only.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Soderlind\EditorPrivacy\Privacy_Access_Policy;
use Soderlind\EditorPrivacy\WP_Environment;
use Soderlind\EditorPrivacy\WordPress_Environment;

// Define plugin constants
define( 'EDITOR_PRIVACY_MANAGER_VERSION', '1.3.1' );
define( 'EDITOR_PRIVACY_MANAGER_URL', plugin_dir_url( __FILE__ ) );
define( 'EDITOR_PRIVACY_MANAGER_PATH', plugin_dir_path( __FILE__ ) );

require_once EDITOR_PRIVACY_MANAGER_PATH . 'vendor/autoload.php';

// Include the generic updater class
if ( ! class_exists( 'Soderlind\WordPress\GitHub_Plugin_Updater' ) ) {
	require_once EDITOR_PRIVACY_MANAGER_PATH . 'class-github-plugin-updater.php';
}

// Privacy access policy and its WordPress environment seam.
require_once EDITOR_PRIVACY_MANAGER_PATH . 'class-privacy-environment.php';
require_once EDITOR_PRIVACY_MANAGER_PATH . 'class-privacy-access-policy.php';
// Initialize the updater with configuration.
$editor_privacy_manager_updater = \Soderlind\WordPress\GitHub_Plugin_Updater::create_with_assets(
	'https://github.com/soderlind/editor-can-manage-privacy-options',
	EDITOR_PRIVACY_MANAGER_PATH . 'editor-can-manage-privacy-options.php',
	'editor-can-manage-privacy-options',
	'/editor-can-manage-privacy-options\.zip/',
	'main'
);

/**
 * Wires WordPress hooks to the Privacy Access Policy.
 *
 * This class is the adapter layer: each method is a thin translation between a
 * WordPress hook and a policy decision. All decisions live in
 * {@see \Soderlind\EditorPrivacy\Privacy_Access_Policy}.
 */
final class Editor_Privacy_Manager {

	/**
	 * Base capability to map 'manage_privacy_options' to (filterable).
	 * Default is an Editor-level cap.
	 */
	const BASE_PRIVACY_CAP = 'edit_pages';

	/**
	 * @var Privacy_Access_Policy|null
	 */
	private static $policy = null;

	/**
	 * @var WP_Environment|null
	 */
	private static $env = null;

	/**
	 * Initialize the plugin.
	 *
	 * @param WP_Environment|null $env Optional environment override (tests inject a fake).
	 */
	public static function init( ?WP_Environment $env = null ) {
		self::$env = $env ?? new WordPress_Environment();

		add_action( 'plugins_loaded', [ __CLASS__, 'load_textdomain' ] );

		// Remap the privacy meta capability to an editor-level capability.
		add_filter( 'map_meta_cap', [ __CLASS__, 'map_meta_cap' ], 10, 2 );

		// Grant temporary manage_options access while on a privacy page.
		add_action( 'admin_init', [ __CLASS__, 'maybe_elevate_on_privacy_page' ] );

		// Guarantee exactly one Privacy entry under Settings (runs after core/other plugins).
		add_action( 'admin_menu', [ __CLASS__, 'ensure_single_privacy_menu' ], 999 );

		// Hide WordPress's render-time duplicate Privacy item (array dedup can't touch it).
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_css' ] );
	}

	/**
	 * Lazily build the policy so the base-cap filter reflects late registrations.
	 */
	private static function policy(): Privacy_Access_Policy {
		if ( null === self::$policy ) {
			self::$policy = new Privacy_Access_Policy( self::$env ?? new WordPress_Environment() );
		}
		return self::$policy;
	}

	/**
	 * Resolve the (filterable) editor-level base capability.
	 */
	private static function base_cap(): string {
		return (string) apply_filters( 'epm_privacy_base_cap', self::BASE_PRIVACY_CAP );
	}

	/**
	 * map_meta_cap adapter: delegate the remap decision to the policy.
	 *
	 * @param string[] $caps Array of capabilities required.
	 * @param string   $cap  The capability being checked.
	 * @return string[] Modified array of required capabilities.
	 */
	public static function map_meta_cap( $caps, $cap ) {
		$mapped = self::policy()->map_privacy_meta_cap( $cap, self::base_cap() );
		return null === $mapped ? $caps : $mapped;
	}

	/**
	 * Load plugin text domain for translations.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'editor-can-manage-privacy-options', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * admin_init adapter: attach the temporary elevation only when the policy allows it.
	 */
	public static function maybe_elevate_on_privacy_page() {
		if ( self::policy()->should_elevate_on_privacy_page() ) {
			add_filter( 'user_has_cap', [ __CLASS__, 'grant_manage_options' ], 10, 2 );
		}
	}

	/**
	 * user_has_cap adapter: grant manage_options for this request when required.
	 *
	 * @param array $allcaps All capabilities of the user.
	 * @param array $caps    Required capabilities being checked.
	 * @return array Modified capabilities array.
	 */
	public static function grant_manage_options( $allcaps, $caps ) {
		if ( self::policy()->requires_manage_options( $caps ) ) {
			$allcaps[ 'manage_options' ] = true;
		}
		return $allcaps;
	}

	/**
	 * admin_menu adapter: execute the policy's plan so Settings holds exactly one Privacy entry.
	 */
	public static function ensure_single_privacy_menu() {
		if ( ! self::policy()->is_eligible_editor() ) {
			return;
		}

		global $submenu;
		$items = isset( $submenu[ 'options-general.php' ] ) ? $submenu[ 'options-general.php' ] : [];
		$plan  = self::policy()->privacy_menu_plan( $items );

		if ( ! empty( $plan[ 'remove_indexes' ] ) ) {
			foreach ( $plan[ 'remove_indexes' ] as $index ) {
				unset( $submenu[ 'options-general.php' ][ $index ] );
			}
			$submenu[ 'options-general.php' ] = array_values( $submenu[ 'options-general.php' ] );
		}

		if ( $plan[ 'add' ] ) {
			add_options_page(
				__( 'Privacy Settings', 'editor-can-manage-privacy-options' ),
				__( 'Privacy', 'editor-can-manage-privacy-options' ),
				self::base_cap(),
				'options-privacy.php'
			);
		}
	}

	/**
	 * When Privacy is the only Settings submenu an editor can reach, WordPress
	 * renders it twice (the wp-first-item clone plus the real item). Enqueue a
	 * stylesheet that hides the duplicate — a render-time artifact the $submenu
	 * array dedup cannot address.
	 */
	public static function enqueue_admin_css() {
		if ( ! self::policy()->is_eligible_editor() ) {
			return;
		}
		wp_enqueue_style(
			'epm-hide-duplicate-privacy',
			EDITOR_PRIVACY_MANAGER_URL . 'assets/css/hide-duplicate-privacy.css',
			[],
			EDITOR_PRIVACY_MANAGER_VERSION
		);
	}
}

// Initialize the plugin
Editor_Privacy_Manager::init();

