<?php
/**
 * Main entry point loader for the Core module.
 */

// Get module functions.
include './inc/namespace.php';

// Patch plugins URL for vendor directory.
add_filter( 'plugins_url', 'HM\\Platform\\fix_plugins_url', 10, 3 );

// Load modules.
add_action( 'hm-platform.loaded_autoloader', function () {
	HM\Platform\load_enabled_modules();
} );
