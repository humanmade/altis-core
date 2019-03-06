<?php
/**
 * Core platform config loader.
 *
 * @package hm-platform-core
 */

namespace HM\Platform;

/**
 * Retrieve the configuration for HM Platform.
 *
 * The configuration is defined by merging the defaults set by modules
 * with any overrides present in composer.json.
 *
 * @return array Configuration data.
 */
function get_config() : array {
	static $config = [];

	if ( empty( $config ) ) {
		$config = get_merged_defaults_and_customizations();
	}

	/**
	 * Filter the entire platform config.
	 *
	 * @param array $config The full config array.
	 */
	$config = apply_filters( 'hm-platform.config', self::$config );

	return $config;
}

/**
 * Merge the defaults and the contents of the various configuration files into a single configuration.
 *
 * @return array Configuration data.
 */
function get_merged_defaults_and_customizations() : array {
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

	// Find composer file.
	$composer_file = ROOT_DIR . '/composer.json';

	// Look for a `composer.json` file.
	if ( is_readable( $composer_file ) ) {
		$config = get_merged_settings( $config, get_json_file_contents_as_array( $composer_file ) );
	} else {
		// phpcs:ignore
		trigger_error( 'A composer file could not be found at ' . $composer_file, E_USER_WARNING );
		return $config;
	}

	// Look for environment specific settings in the config and merge it in.
	$environment = get_environment_type();
	if ( isset( $config['environments'], $config['environments'][ $environment ] ) ) {
		$config = get_merged_settings( $config, $config['environments'][ $environment ] );
	}

	return $config;
}

/**
 * Merge settings in an existing configuration file.
 *
 * @param array $config    Existing configuration.
 * @param array $overrides Settings to merge in.
 *
 * @return array Configuration data.
 */
function get_merged_settings( array $config, array $overrides ) : array {
	$merged = $config;

	foreach ( $overrides as $key => $value ) {
		switch ( $key ) {
			// Merge module settings together.
			case 'modules':
				foreach ( $value as $module => $settings ) {
					$merged[ $key ][ $module ] = array_merge( $merged[ $key ][ $module ] ?? [], $settings );
				}
				break;
			// Replace property by default.
			default:
				$merged[ $key ] = $value;
				break;
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
function get_json_file_contents_as_array( $file ) : array {
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

	return $contents['extra']['platform'] ?? [];
}
