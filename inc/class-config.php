<?php
/**
 * Core platform config loader.
 *
 * @package hm-platform-core
 */

namespace HM\Platform;

class Config {

	/**
	 * Primary config array.
	 *
	 * @var array
	 */
	protected static $config = [];

	/**
	 * Retrieve the configuration for HM Platform.
	 *
	 * The configuration is defined by merging the defaults set by modules
	 * with any overrides present in composer.json.
	 *
	 * @return array Configuration data.
	 */
	static function get() : array {
		if ( self::$config ) {
			return self::$config;
		}

		self::$config = self::get_merged_defaults_and_customizations();

		return self::$config;
	}

	/**
	 * The environment type is used to override the config
	 * as well as allow for checks within a project codebase.
	 *
	 * Defaults to local, this constant will be set at the stack level.
	 *
	 * @return string The environment type string.
	 */
	static function get_environment_type() : string {
		if ( ! defined( 'HM_ENV_TYPE' ) ) {
			define( 'HM_ENV_TYPE', 'local' );
		}

		return HM_ENV_TYPE;
	}

	/**
	 * Merge the defaults and the contents of the various configuration files into a single configuration.
	 *
	 * @return array Configuration data.
	 */
	protected static function get_merged_defaults_and_customizations() : array {
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
			$config = self::get_merged_settings( $config, self::get_json_file_contents_as_array( $composer_file ) );
		} else {
			// phpcs:ignore
			trigger_error( 'A composer file could not be found at ' . $composer_file, E_USER_WARNING );
			return $config;
		}

		// Look for environment specific settings in the config and merge it in.
		$environment = self::get_environment_type();
		if ( isset( $config['environments'], $config['environments'][ $environment ] ) ) {
			$config = self::get_merged_settings( $config, $config['environments'][ $environment ] );
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
	protected static function get_merged_settings( array $config, array $overrides ) : array {
		$merged = $config;

		foreach ( $overrides as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = self::get_merged_settings( $merged[ $key ], $value );
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
	protected static function get_json_file_contents_as_array( $file ) {
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

		// Get the .extra.platform settings.
		if ( ! isset( $contents['extra'], $contents['extra']['platform'] ) ) {
			return [];
		}

		return $contents['extra']['platform'];
	}

}
