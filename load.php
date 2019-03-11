<?php
/**
 * Main entry point loader for the Core module.
 */

// Get module functions.
require_once __DIR__ . '/inc/namespace.php';

// Patch plugins URL for vendor directory.
add_filter( 'plugins_url', 'HM\\Platform\\fix_plugins_url', 10, 3 );

// Load modules after autoloader.
add_action( 'hm-platform.loaded_autoloader', 'HM\\Platform\\load_enabled_modules' );
