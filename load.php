<?php
/**
 * Main entry point loader for the Core module.
 *
 * @package altis/core
 */

namespace Altis;

// Patch plugins URL for vendor directory.
add_filter( 'plugins_url', 'Altis\\fix_plugins_url', 10, 3 );

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
			'enabled' => true,
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
