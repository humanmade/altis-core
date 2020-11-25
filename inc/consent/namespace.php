<?php

namespace Altis\Core\Consent;

use Altis;
use Altis\Consent\Settings;

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
		require_once Altis\ROOT_DIR . '/vendor/altis/consent-api/wp-consent-api.php';
		require_once Altis\ROOT_DIR . '/vendor/altis/consent/plugin.php';
	}
}

/**
 * Set the analytics script to no-op if no consent to statistics has been granted.
 *
 * @param bool $noop No-op value to prevent analytics events from being sent to Pinpoint. Defaults to false.
 *
 * @return bool      The updated noop value.
 */
function set_analytics_noop( bool $noop ) : bool {
	// Override no-op if we don't have consent for statistics.
	if ( ! wp_has_consent( 'statistics' ) ) {
		return true;
	}

	return $noop;
}

/**
 * Update the Endpoint/User data if we don't have consent for statistics.
 * Overrides with an empty array if we don't have consent.
 *
 * @param array $data An array of analytics data to send to Pinpoint.
 *
 * @return array      The filtered analytics variable data.
 */
function set_analytics_data( array $data ) : array {
	if ( ! wp_has_consent( 'statistics' ) ) {
		$data['Endpoint']->User = [];
	}

	return $data;
}
