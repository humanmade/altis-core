<?php

namespace Altis\Core\Consent;

use Altis;

/**
 * Kick it off.
 */
function bootstrap() {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugins', 1 );
}


/**
 * Load plugins that are part of the consent module.
 */
function load_plugins() {
	$config = Altis\get_config()['modules']['core']['consent'];

	// Unless the consent module has been deactivated, load the plugins.
	if ( $config ) {
		require_once Altis\ROOT_DIR . '/vendor/altis/consent-api/plugin.php';
		require_once Altis\ROOT_DIR . '/vendor/altis/consent/plugin.php';
	}
}
