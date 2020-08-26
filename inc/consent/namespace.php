<?php

namespace Altis\Consent;

use Altis;

function bootstrap() {
	add_action( 'plugins_loaded', __NAMESPACE__ . '\\set_consent_defaults' );
}

function set_consent_defaults() {
	$config  = Altis\get_config()['modules']['core']['consent'];
	$options = get_option( 'cookie_consent_options' );

	// Bail if we've turned consent off explicitly. TODO: is there a way to deactivate the plugin if this is turned off? Or do we just hide the controls?
	if ( $config === false ) {
		return;
	}

	// Bail if options have been set.
	if ( is_array( $options ) ) {
		return;
	}

	update_option( 'cookie_consent_option', $config );
}