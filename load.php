<?php
/**
 * Main entry point loader for the Core module.
 *
 * @package altis/core
 */

namespace Altis;

// Patch plugins URL for vendor directory.
add_filter( 'plugins_url', 'Altis\\fix_plugins_url', 10, 3 );

// Ensure WP_ENVIRONMENT_TYPE is set.
add_action( 'altis.loaded_autoloader', 'Altis\\set_wp_environment_type', -10 );

// Fire module init hook and load enabled modules.
add_action( 'altis.loaded_autoloader', function () {
	/**
	 * Modules should register themselves on this hook.
	 */
	do_action( 'altis.modules.init' );

	// Load modules.
	load_enabled_modules();
}, 0 );

// Register core module.
add_action( 'altis.modules.init', function () {
	register_module(
		'core',
		__DIR__,
		'Core',
		[
			'defaults' => [
				'enabled' => true,
			],
		],
		__NAMESPACE__ . '\\bootstrap'
	);
} );

// Load config entry point.
add_action( 'altis.loaded_autoloader', function () {
	if ( file_exists( ROOT_DIR . '/.config/load.php' ) ) {
		require_once ROOT_DIR . '/.config/load.php';
	}
} );

// Disable BrowseHappy requests.
add_filter( 'pre_http_request', function ( $preempt, $args, $url ) {

	if ( ! is_admin() ) {
		return $preempt;
	}

	if ( ! apply_filters( 'altis_disable_browsehappy', true ) ) {
		return $preempt;
	}

	$parts = \parse_url( $url );

	if ( is_array( $parts )
			&& ! empty( $parts['host'] )
			&& ! empty( $parts['path'] )
			&& \strcasecmp( $parts['host'], 'api.wordpress.org' ) === 0
			&& \strpos( $parts['path'], '/core/browse-happy/' ) === 0
		) {
			return new \WP_Error(
				'altis_disabled_browsehappy',
				'Altis disables BrowseHappy requests for privacy.'
			);
	}

	return $preempt;

}, 10, 3 );

// Remove BrowseHappy dashboard nag.
add_action( 'wp_dashboard_setup', function () {

	if ( ! apply_filters( 'altis_disable_browsehappy', true ) ) {
		return;
	}

	remove_meta_box( 'dashboard_browser_nag', 'dashboard', 'normal' );
	if ( has_filter( 'postbox_classes_dashboard_dashboard_browser_nag', 'dashboard_browser_nag_class' ) ) {
		remove_filter( 'postbox_classes_dashboard_dashboard_browser_nag', 'dashboard_browser_nag_class' );
	}

}, 999 );
