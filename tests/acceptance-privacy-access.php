<?php
/**
 * Acceptance tests for the Privacy Access Policy.
 *
 * Zero-dependency runner: drives {@see Privacy_Access_Policy} through a fake
 * WP_Environment and asserts the plugin's documented, user-facing behaviour.
 *
 * Run:  php tests/acceptance-privacy-access.php
 *
 * @package Soderlind\EditorPrivacy
 */

declare(strict_types=1);

namespace Soderlind\EditorPrivacy;

// The class files guard on ABSPATH; define it so they load under CLI.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/class-privacy-environment.php';
require_once dirname( __DIR__ ) . '/class-privacy-access-policy.php';

/**
 * In-memory environment: configurable capabilities and current page.
 */
final class Fake_WP_Environment implements WP_Environment {

	/** @var array<string,bool> */
	private array $caps;
	private string $page;

	/**
	 * @param array<int,string> $granted_caps Capabilities the user has.
	 */
	public function __construct( array $granted_caps = [], string $page = '' ) {
		$this->caps = array_fill_keys( $granted_caps, true );
		$this->page = $page;
	}

	public function current_user_can( string $cap ): bool {
		return ! empty( $this->caps[ $cap ] );
	}

	public function current_page(): string {
		return $this->page;
	}
}

/* -------------------------------------------------------------------------- */
/* Tiny assertion harness                                                     */
/* -------------------------------------------------------------------------- */

$tests_run    = 0;
$tests_failed = 0;

/**
 * @param mixed $expected
 * @param mixed $actual
 */
function check( string $scenario, $expected, $actual ): void {
	global $tests_run, $tests_failed;
	$tests_run++;
	if ( $expected === $actual ) {
		printf( "  \033[32mPASS\033[0m  %s\n", $scenario );
		return;
	}
	$tests_failed++;
	printf(
		"  \033[31mFAIL\033[0m  %s\n        expected: %s\n        actual:   %s\n",
		$scenario,
		var_export( $expected, true ),
		var_export( $actual, true )
	);
}

/** Editor-level user: edits others' content, no admin-like capabilities. */
$editor_caps     = [ 'edit_others_posts', 'edit_others_pages', 'edit_pages' ];
$admin_caps      = [ 'activate_plugins', 'install_plugins', 'manage_options', 'edit_others_posts' ];
$network_caps    = [ 'manage_network', 'edit_others_posts' ];
$subscriber_caps = [ 'read' ];

echo "\nEligible-editor detection\n";
check(
	'Editor is eligible',
	true,
	( new Privacy_Access_Policy( new Fake_WP_Environment( $editor_caps ) ) )->is_eligible_editor()
);
check(
	'Administrator is not eligible (has admin-like caps)',
	false,
	( new Privacy_Access_Policy( new Fake_WP_Environment( $admin_caps ) ) )->is_eligible_editor()
);
check(
	'Network admin is treated as admin-equivalent',
	false,
	( new Privacy_Access_Policy( new Fake_WP_Environment( $network_caps ) ) )->is_eligible_editor()
);
check(
	'Subscriber is not eligible',
	false,
	( new Privacy_Access_Policy( new Fake_WP_Environment( $subscriber_caps ) ) )->is_eligible_editor()
);

echo "\nCapability remap (map_meta_cap)\n";
$policy = new Privacy_Access_Policy( new Fake_WP_Environment( $editor_caps ) );
check(
	'manage_privacy_options remaps to the base capability',
	[ 'edit_pages' ],
	$policy->map_privacy_meta_cap( 'manage_privacy_options', 'edit_pages' )
);
check(
	'Custom base capability is honoured (epm_privacy_base_cap filter)',
	[ 'edit_others_posts' ],
	$policy->map_privacy_meta_cap( 'manage_privacy_options', 'edit_others_posts' )
);
check(
	'Unrelated capabilities are left unchanged',
	null,
	$policy->map_privacy_meta_cap( 'manage_options', 'edit_pages' )
);

echo "\nRequest-scoped elevation on privacy pages\n";
$editor_on_privacy = new Privacy_Access_Policy( new Fake_WP_Environment( $editor_caps, 'options-privacy.php' ) );
$editor_on_guide   = new Privacy_Access_Policy( new Fake_WP_Environment( $editor_caps, 'privacy-policy-guide.php' ) );
$editor_on_posts   = new Privacy_Access_Policy( new Fake_WP_Environment( $editor_caps, 'edit.php' ) );
$admin_on_privacy  = new Privacy_Access_Policy( new Fake_WP_Environment( $admin_caps, 'options-privacy.php' ) );

check( 'Editor is elevated on options-privacy.php', true, $editor_on_privacy->should_elevate_on_privacy_page() );
check( 'Editor is elevated on privacy-policy-guide.php', true, $editor_on_guide->should_elevate_on_privacy_page() );
check( 'Editor is NOT elevated off privacy pages', false, $editor_on_posts->should_elevate_on_privacy_page() );
check( 'Administrator elevation is a no-op (already capable)', false, $admin_on_privacy->should_elevate_on_privacy_page() );
check(
	'Elevation only answers manage_options checks',
	true,
	$editor_on_privacy->requires_manage_options( [ 'manage_options' ] )
);
check(
	'Elevation ignores unrelated capability checks',
	false,
	$editor_on_privacy->requires_manage_options( [ 'edit_theme_options' ] )
);

echo "\nExactly one Privacy submenu entry\n";
$menu_policy = new Privacy_Access_Policy( new Fake_WP_Environment( $editor_caps ) );

$no_privacy = [
	[ 'General', 'manage_options', 'options-general.php' ],
	[ 'Writing', 'manage_options', 'options-writing.php' ],
];
check(
	'Adds Privacy when core did not expose it',
	[ 'add' => true, 'remove_indexes' => [] ],
	$menu_policy->privacy_menu_plan( $no_privacy )
);

$one_privacy = [
	[ 'General', 'manage_options', 'options-general.php' ],
	[ 'Privacy', 'manage_privacy_options', 'options-privacy.php' ],
];
check(
	'Does nothing when core already exposed Privacy',
	[ 'add' => false, 'remove_indexes' => [] ],
	$menu_policy->privacy_menu_plan( $one_privacy )
);

$two_privacy = [
	[ 'General', 'manage_options', 'options-general.php' ],
	[ 'Privacy', 'manage_privacy_options', 'options-privacy.php' ],
	[ 'Writing', 'manage_options', 'options-writing.php' ],
	[ 'Privacy', 'edit_pages', 'options-privacy.php' ],
];
check(
	'Removes duplicate Privacy entries, keeping the first',
	[ 'add' => false, 'remove_indexes' => [ 3 ] ],
	$menu_policy->privacy_menu_plan( $two_privacy )
);

/* -------------------------------------------------------------------------- */

echo "\n";
printf( "%d run, %d failed\n\n", $tests_run, $tests_failed );
exit( $tests_failed > 0 ? 1 : 0 );
