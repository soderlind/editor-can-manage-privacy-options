<?php
/**
 * WordPress environment seam for the Privacy Access Policy.
 *
 * @package Soderlind\EditorPrivacy
 */

namespace Soderlind\EditorPrivacy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The WordPress facts the Privacy Access Policy needs to reach a decision.
 *
 * Production reads the live globals; tests pass a fake.
 */
interface WP_Environment {
	/**
	 * @param string $cap Capability being tested for the current user.
	 */
	public function current_user_can( string $cap ): bool;

	/**
	 * The admin page currently being served (WordPress $pagenow).
	 */
	public function current_page(): string;
}

/**
 * Live adapter over WordPress globals.
 */
final class WordPress_Environment implements WP_Environment {

	public function current_user_can( string $cap ): bool {
		return \current_user_can( $cap );
	}

	public function current_page(): string {
		global $pagenow;
		return is_string( $pagenow ) ? $pagenow : '';
	}
}
