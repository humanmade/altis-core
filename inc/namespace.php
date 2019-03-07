<?php
/**
 * Core platform functions.
 *
 * @package hm-platform
 */

namespace HM\Platform;

use Aws\Sdk;

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
		$config = get_merged_config();

		/**
		 * Filter the entire platform config.
		 *
		 * @param array $config The full config array.
		 */
		$config = apply_filters( 'hm-platform.config', $config );
	}

	return $config;
}

/**
 * Merge the defaults and the contents of the various configuration files into a single configuration.
 *
 * @return array Configuration data.
 */
function get_merged_config() : array {
	$composer_file = ROOT_DIR . '/composer.json';
	$composer_json = get_json_file_contents_as_array( $composer_file );
	$config = merge_config_settings( [], $composer_json['extra']['platform'] ?? [] );

	// Look for environment specific settings in the config and merge it in.
	$environment = get_environment_type();
	$config = merge_config_settings( $config, $config['environments'][ $environment ] ?? [] );

	return $config;
}

/**
 * Merge settings in an existing configuration file.
 *
 * @param array $config Existing configuration.
 * @param array $overrides Settings to merge in.
 *
 * @return array Configuration data.
 */
function merge_config_settings( array $config, array $overrides ) : array {
	foreach ( $overrides as $key => $value ) {
		switch ( $key ) {
			// Merge module settings together.
			case 'modules':
				foreach ( $value as $module => $settings ) {
					$config[ $key ][ $module ] = array_merge( $config[ $key ][ $module ] ?? [], $settings );
				}
				break;

			// Replace property by default.
			default:
				$config[ $key ] = $value;
				break;
		}
	}

	return $config;
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

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		// phpcs:ignore
		trigger_error( json_last_error_msg(), E_USER_WARNING );
		return [];
	}

	return $contents;
}

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
 * Can be "local", "development", "staging", "production" etc.
 *
 * @return string
 */
function get_environment_type() : string {
	if ( defined( 'HM_ENV_TYPE' ) ) {
		return HM_ENV_TYPE;
	}
	return 'local';
}

/**
 * Fix the plugins_url for files in the vendor directory
 *
 * @param string $url
 * @param string $path
 * @param string $plugin
 * @return string
 */
function fix_plugins_url( string $url, string $path, string $plugin ) : string {
	if ( strpos( $plugin, dirname( ABSPATH ) ) === false ) {
		return $url;
	}

	return str_replace( dirname( ABSPATH ), dirname( WP_CONTENT_URL ), dirname( $plugin ) ) . $path;
}

/**
 * Get all enabled modules.
 *
 * @return array
 */
function get_enabled_modules() : array {
	$modules = Module::get_all();
	$enabled = array_filter( $modules, function ( Module $module ) {
		return $module->get_setting( 'enabled' );
	} );

	return $enabled;
}

/**
 * Load all enabled plugins, along with their customisation files.
 */
function load_enabled_modules() {
	foreach ( get_enabled_modules() as $name => $module ) {

		// Load module.
		$module->load();

		/**
		 * Runs after the module has been loaded.
		 *
		 * @param Module $module The module object.
		 */
		do_action( "hm-platform.modules.{$name}.loaded", $module );
	}

	/**
	 * Runs after all modules have loaded.
	 */
	do_action( 'hm-platform.modules.loaded' );
}
