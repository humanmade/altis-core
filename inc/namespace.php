<?php
/**
 * Utility functions to retrieve and parse config files.
 *
 * @package altis-core
 */

namespace Altis\Core;

/**
 * Retrieve the configuration for HM Platform.
 *
 * The configuration is defined by merging the defaults with the various files that allow to customise a particular
 * installation.
 *
 * @return array Configuration data.
 */
function get_config() {
	static $config;

	if ( $config ) {
		return $config;
	}

	$config = get_merged_defaults_and_customisations();

	return $config;
}

/**
 * Return the value for a particular setting from the configuration,
 *
 * @param string $key Settings key to retrieve the value from.
 *
 * @return mixed Settings value.
 */
function get_config_value( string $key ) {
	$config = get_config();

	if ( ! array_key_exists( $key, $config ) ) {
		// phpcs:ignore
		trigger_error( 'Could not find the ' . $key . ' setting in the configuration.', E_USER_WARNING );
		return null;
	}

	return $config[ $key ];
}

/**
 * Merge the defaults and the contents of the various configuration files into a single configuration.
 *
 * @return array Configuration data.
 */
function get_merged_defaults_and_customisations() : array {
	// Default config.
	$config = [
		'modules' => [],
		'environments' => [
			'local' => [],
			'development' => [],
			'staging' => [],
			'production' => [],
		],
	];

	// @todo get registered modules & their defaults here.

	// Find composer file.
	$composer_file = dirname( ABSPATH ) . '/composer.json';

	// Look for a `composer.json` file.
	if ( is_readable( $composer_file ) ) {
		$config = get_merged_settings( $config, get_json_file_contents_as_array( $composer_file ) );
	} else {
		// phpcs:ignore
		trigger_error( 'A composer file could not be found at ' . $composer_file, E_USER_WARNING );
		return $config;
	}

	// Look for environment specific settings in the config and merge.
	if ( defined( 'HM_ENV_TYPE' ) && isset( $config['environments'], $config['environments'][ HM_ENV_TYPE ] ) ) {
		$config = get_merged_settings( $config, $config['environments'][ HM_ENV_TYPE ] );
	}

	return $config;
}

/**
 * Override settings in an existing configuration file.
 *
 * Merge customisations into a configuration file. Existing settings will be overwritten.
 *
 * @param array $config    Existing configuration.
 * @param array $overrides Settings to merge in.
 *
 * @return array Configuration data.
 */
function get_merged_settings( array $config, array $overrides ) : array {
	$merged = $config;

	foreach ( $overrides as $key => &$value ) {
		if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
			$merged[ $key ] = get_merged_settings( $merged[ $key ], $value );
		} elseif ( is_numeric( $key ) ) {
			if ( ! in_array( $value, $merged, true ) ) {
				$merged[] = $value;
			}
		} else {
			$merged[ $key ] = $value;
		}
	}

	return $merged;
}

/**
 * Get the contents of a JSON file, decode it, and return as an array.
 *
 * @param string $file Path to the JSON file.
 *
 * @return array Decoded data in array form, empty array if JSON data could not read.
 */
function get_json_file_contents_as_array( $file ) {
	if ( ! strpos( $file, '.json' ) ) {
		// phpcs:ignore
		trigger_error( $file . ' is not a JSON file.', E_USER_WARNING );
		return [];
	}

	if ( ! is_readable( $file ) ) {
		// phpcs:ignore
		trigger_error( 'Could not read ' . $file . ' file.', E_USER_WARNING );
		return [];
	}

	$contents = json_decode( file_get_contents( $file ), true );

	if ( ! is_array( $contents ) ) {
		// phpcs:ignore
		trigger_error( 'Decoding the JSON in ' . $file . ' .', E_USER_WARNING );
		return [];
	}

	// Get the .extra.altis settings.
	if ( ! isset( $contents['extra'], $contents['extra']['altis'] ) ) {
		// phpcs:ignore
		trigger_error( 'No altis configuration found in composer.json under extra.altis', E_USER_WARNING );
		return [];
	}

	return $contents['extra']['altis'];
}
