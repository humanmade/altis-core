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

/**
 * Disable BrowseHappy browser check
 *
 * For Privacy reasons we don't want to call this API (that doesn't actually work anymore).
 * To disable it we fake the cached API response so it never gets called.
 */
add_action( 'admin_init', function () {
	// Escape hatch: allow re-enabling if needed.
	if ( ! apply_filters( 'altis_disable_browsehappy', true ) ) {
		return;
	}

	// Generate the MD5 key based on user agent.
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		return;
	}

	$key = md5( $_SERVER['HTTP_USER_AGENT'] );
	add_filter( 'pre_site_transient_browser_' . $key, function ( $value ) {
		return [
			'upgrade' => '',
		];
	} );
} );
