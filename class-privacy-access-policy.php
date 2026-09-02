<?php
/**
 * Privacy Access Policy: every decision about whether an eligible editor may
 * reach the privacy settings, and how the privacy menu should be shaped.
 *
 * Pure decisions given a WP_Environment. No WordPress side effects live here.
 *
 * @package Soderlind\EditorPrivacy
 */

namespace Soderlind\EditorPrivacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Privacy_Access_Policy {

	/** Capabilities that mark a user as effectively administrator-level. */
	const ADMIN_LIKE_CAPS = [ 'activate_plugins', 'install_plugins', 'update_core', 'delete_users', 'promote_users', 'manage_network', 'manage_network_options' ];

	/** Capabilities that infer an editor-level user. */
	const EDITOR_CAPS = [ 'edit_others_posts', 'edit_others_pages' ];

	/** The meta capability WordPress checks for privacy management. */
	const PRIVACY_META_CAP = 'manage_privacy_options';

	/** Admin pages that count as privacy pages. */
	const PRIVACY_PAGES = [ 'options-privacy.php', 'privacy-policy-guide.php' ];

	/** Submenu slug of the core Privacy page. */
	const PRIVACY_MENU_SLUG = 'options-privacy.php';

	private WP_Environment $env;

	public function __construct( WP_Environment $env ) {
		$this->env = $env;
	}

	/**
	 * True when the current user is editor-level but not administrator-level.
	 */
	public function is_eligible_editor(): bool {
		foreach ( self::ADMIN_LIKE_CAPS as $cap ) {
			if ( $this->env->current_user_can( $cap ) ) {
				return false;
			}
		}
		foreach ( self::EDITOR_CAPS as $cap ) {
			if ( $this->env->current_user_can( $cap ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * True when the current admin page is a privacy page.
	 */
	public function is_privacy_page(): bool {
		return in_array( $this->env->current_page(), self::PRIVACY_PAGES, true );
	}

	/**
	 * True when an eligible editor should be elevated for this privacy-page request.
	 */
	public function should_elevate_on_privacy_page(): bool {
		return $this->is_privacy_page() && $this->is_eligible_editor();
	}

	/**
	 * True when the capabilities being checked include manage_options.
	 *
	 * @param array<int,string> $required_caps Capabilities WordPress is testing.
	 */
	public function requires_manage_options( array $required_caps ): bool {
		return in_array( 'manage_options', $required_caps, true );
	}

	/**
	 * Remap the privacy meta capability to the editor-level base capability.
	 *
	 * @return string[]|null The mapped capabilities, or null to leave unchanged.
	 */
	public function map_privacy_meta_cap( string $cap, string $base_cap ): ?array {
		return self::PRIVACY_META_CAP === $cap ? [ $base_cap ] : null;
	}

	/**
	 * Decide how to make the Settings submenu hold exactly one Privacy entry.
	 *
	 * @param array<int,array<int,string>> $submenu_items Rows under options-general.php.
	 * @return array{add:bool,remove_indexes:int[]} add: append a Privacy entry; remove_indexes: drop these duplicates.
	 */
	public function privacy_menu_plan( array $submenu_items ): array {
		$privacy_indexes = [];
		foreach ( $submenu_items as $index => $item ) {
			if ( isset( $item[ 2 ] ) && self::PRIVACY_MENU_SLUG === $item[ 2 ] ) {
				$privacy_indexes[] = $index;
			}
		}

		if ( empty( $privacy_indexes ) ) {
			return [ 'add' => true, 'remove_indexes' => [] ];
		}

		array_shift( $privacy_indexes ); // Keep the first entry.
		return [ 'add' => false, 'remove_indexes' => $privacy_indexes ];
	}
}
