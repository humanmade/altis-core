<?php

namespace Altis\Consent;

use Altis;
use Altis\Consent\Settings;

function bootstrap() {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\set_consent_defaults' );
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugins', 1 );
}

function set_consent_defaults() {
	$config  = Altis\get_config()['modules']['core']['consent'];
	$options = get_option( 'cookie_consent_options' );

	// Bail if we've turned consent off explicitly. TODO: is there a way to deactivate the plugin if this is turned off? Or do we just hide the controls?
	if ( $config === false ) {
		return;
	}

	// Bail if options have been set.
	if ( $options ) {
		return;
	}

	// If no banner text was set in the config, use the default banner message instead of an empty string.
	if ( $options['banner_text'] === '' ) {
		$options['banner_text'] = Settings\get_default_banner_message();
	}

	// If no privacy policy page was set in the config, but a privacy policy page exists, save that to our options. This will just select it in the dropdown.
	if ( $options['privacy_policy_page'] === '' && empty( get_privacy_policy_url() ) ) {
		$options['privacy_policy_page'] = (int) get_option( 'wp_page_for_privacy_policy' );
	}

	update_option( 'cookie_consent_option', $options );
}

/**
 * Load plugins that are part of the consent module.
 */
function load_plugins() {
	$config = Altis\get_config()['modules']['core']['consent'];

	// Unless the consent module has been deactivated, load the plugins.
	if ( $config ) {
		require_once Altis\ROOT_DIR . '/vendor/rlankhorst/wp-consent-level-api/wp-consent-level-api.php';
		require_once Altis\ROOT_DIR . '/vendor/altis/consent/plugin.php';
	}
}
