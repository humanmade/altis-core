<?php

namespace Altis\Core\Consent;

use Altis;
use Altis\Consent\Settings;

/**
 * Kick it off.
 */
function bootstrap() {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\set_consent_options' );
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugins', 1 );

	add_filter( 'altis.analytics.noop', __NAMESPACE__ . '\\set_analytics_noop' );
}

/**
 * Save the defaults to the database if nothing has been set yet.
 */
function set_consent_options() {
	$config  = Altis\get_config()['modules']['core']['consent'];
	$fields  = Settings\get_cookie_consent_settings_fields();
	$options = [];

	// Bail if we've turned consent off explicitly.
	if ( empty( $config ) ) {
		return;
	}

	// Check if display-banner was configured.
	if ( ! empty( $config['display-banner'] ) ) {
		// Make sure display-banner is a boolean.
		if ( is_bool( $config['display-banner'] ) ) {
			$options['display_banner'] = $config['display-banner'];
			unset( $fields['display_banner'] );
		}
	}

	// Check if banner-options was configured.
	if ( ! empty( $config['banner_options'] ) ) {
		// Make sure any option set in the config is a valid option.
		if ( in_array( $config['banner-options'], Settings\get_cookie_banner_options(), true ) ) {
			$options['banner_options'] = $config['banner-options'];
			unset( $fields['banner_options'] );
		}
	}

	// Check if cookie expiration was configured.
	if ( ! empty( $config['cookie-expiration'] ) ) {
		// Make sure the value set in the config is numeric.
		if ( is_numeric( $config['cookie-expiration'] ) ) {
			$options['cookie_expiration'] = (int) $config['cookie_expiration'];
			unset( $fields['cookie_expiration'] );
		}
	}

	// If any options were set in the config, update those values in the database and remove the options on the settings page.
	if ( ! empty( $options ) ) {
		update_option( 'cookie_consent_option', $options );
		add_filter( 'altis.consent.consent_settings_fields', $fields );
	}
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
