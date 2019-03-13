<?php
/**
 * Main entry point loader for the Core module.
 */

namespace HM\Platform;

// Get module functions.
require_once __DIR__ . '/inc/namespace.php';

// Patch plugins URL for vendor directory.
add_filter( 'plugins_url', 'HM\\Platform\\fix_plugins_url', 10, 3 );

// Fire module init hook and load enabled modules.
add_action( 'hm-platform.loaded_autoloader', function () {
	/**
	 * Modules should register themselves on this hook.
	 */
	do_action( 'hm-platform.modules.init' );

	// Load modules.
	load_enabled_modules();
}, 0 );

// Register core module.
add_action( 'hm-platform.modules.init', function () {
	register_module( 'core', __DIR__, 'Core', [
		'enabled' => true,
	] );
} );
