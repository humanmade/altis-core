<?php

namespace HM\Platform;

use Aws\Sdk;

/**
 * Get a globally configured instance of the AWS SDK.
 */
function get_aws_sdk() : Sdk {
	static $sdk;
	if ( $sdk ) {
		return $sdk;
	}
	$params = [
		'region'   => HM_ENV_REGION,
		'version'  => 'latest',
	];
	if ( defined( 'AWS_KEY' ) ) {
		$params['credentials'] = [
			'key'    => AWS_KEY,
			'secret' => AWS_SECRET,
		];
	}
	$sdk = new Sdk( $params );
	return $sdk;
}

/**
 * Get the application architecture for the current site.
 *
 * @return string
 */
function get_environment_architecture() : string {
	if ( defined( 'HM_ENV_ARCHITECTURE' ) ) {
		return HM_ENV_ARCHITECTURE;
	}
	return 'ec2';
}

/**
 * Get the name of the current environment.
 *
 * @return string
 */
function get_environment_name() : string {
	if ( defined( 'HM_ENV' ) ) {
		return HM_ENV;
	}
	return 'unknown';
}

/**
 * Get the type of the current environment.
 *
 * Can be "local", "development", "staging", "prodiction" etc.
 *
 * @return string
 */
function get_environment_type() : string {
	if ( defined( 'HM_ENV_TYPE' ) ) {
		return HM_ENV_TYPE;
	}
	return 'local';
}
