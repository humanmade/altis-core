<?php
/**
 * Core platform functions.
 *
 * @package altis/core
 */

namespace Altis;

use Aws\Sdk;

/**
 * Bootstrap any core functions as necessary.
 */
function bootstrap() {
	About\bootstrap();
}

/**
 * Retrieve the configuration for Altis.
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
		 * Filter the entire altis config.
		 *
		 * @param array $config The full config array.
		 */
		$config = apply_filters( 'altis.config', $config );
	}

	return $config;
}

/**
 * Merge the defaults and the contents of the various configuration files into a single configuration.
 *
 * @return array Configuration data.
 */
function get_merged_config() : array {
	/**
	 * Use this filter to build up the default configuration.
	 *
	 * @param array $default_config
	 */
	$default_config = apply_filters( 'altis.config.default', [] );

	// Get custom config overrides.
	$composer_file = ROOT_DIR . '/composer.json';
	$composer_json = get_json_file_contents_as_array( $composer_file );

	$config = merge_config_settings( $default_config, $composer_json['extra']['altis'] ?? [] );

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

					// Check settings syntax is valid.
					if ( ! is_array( $settings ) && ! is_bool( $settings ) ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						trigger_error( "Settings for the module '{$module}' specified in the composer.json are incorrect. It should be either an object or a boolean.", E_USER_WARNING );
						continue;
					}

					// Convert bool value into an array with `enabled` setting.
					if ( is_bool( $settings ) ) {
						$settings = [
							'enabled' => $settings,
						];
					}

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

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
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
		'version'  => 'latest',
	];

	$config = get_config();
	if ( isset( $config['modules']['core']['aws'] ) ) {
		$params = array_merge( $params, $config['modules']['core']['aws'] );
	}

	if ( defined( 'HM_ENV_REGION' ) ) {
		$params['region'] = HM_ENV_REGION;
	}
	if ( defined( 'AWS_KEY' ) ) {
		$params['credentials'] = [
			'key'    => AWS_KEY,
			'secret' => AWS_SECRET,
		];
	}

	/**
	 * Filter params before new instance of Sdk is created.
	 *
	 * See the AWS SDK docs for all available options:
	 * https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.AwsClient.html#___construct
	 *
	 * @param array $params SDK params specified in config and via constants.
	 */
	$params = apply_filters( 'altis.aws_sdk.params', $params );

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
 * Get the AWS region of the current environment.
 *
 * Can be "us-east-1", "ap-northeast-1", "eu-central-1" etc
 *
 * @return string|null Region if set, null otherwise.
 */
function get_environment_region() : ?string {
	if ( defined( 'HM_ENV_REGION' ) ) {
		return HM_ENV_REGION;
	}
	return null;
}

/**
 * Get the current revision of the codebase deployed to the current environment.
 *
 * @return ?string
 */
function get_environment_codebase_revision() : ?string {
	// This is the constant that is defined in wp-config-production.php
	// in the EC2 architecture in Altis Cloud.
	if ( defined( 'HM_APPLICATION_REVISION' ) ) {
		return HM_APPLICATION_REVISION;
	}

	// This is the constant that is defined in wp-config-production.php
	// in the ECS architecture in Altis Cloud.
	if ( defined( 'HM_DEPLOYMENT_REVISION' ) ) {
		return HM_DEPLOYMENT_REVISION;
	}

	return null;
}

/**
 * Fix the plugins_url for files in the vendor directory
 *
 * @param string $url The current plugin URL.
 * @param string $path The relative path to a file in the plugin folder.
 * @param string $plugin The absolute path to the plugin file.
 * @return string
 */
function fix_plugins_url( string $url, string $path, string $plugin ) : string {
	if ( strpos( $plugin, dirname( ABSPATH ) ) === false ) {
		return $url;
	}

	if ( ! empty( $path ) ) {
		$path = '/' . ltrim( $path, '/' );
	}

	return str_replace( dirname( ABSPATH ), dirname( WP_CONTENT_URL ), dirname( $plugin ) ) . $path;
}

/**
 * Registers a module with the store.
 *
 * @param string $slug The string identifier for the module used for later reference.
 * @param string $directory The root directory of the module.
 * @param string $title Human readable module title.
 * @param ?array $default_settings Optional default settings array.
 * @param ?callable $loader Optional loader function to call module bootstrapping code.
 * @return Module
 */
function register_module( string $slug, string $directory, string $title, ?array $default_settings = null, ?callable $loader = null ) : Module {
	return Module::register( $slug, $directory, $title, $default_settings, $loader );
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
	foreach ( get_enabled_modules() as $slug => $module ) {
		/**
		 * Runs after the module has been loaded.
		 *
		 * @param Module $module The module object.
		 */
		do_action( "altis.modules.{$slug}.loaded", $module );
	}

	/**
	 * Runs after all modules have loaded.
	 */
	do_action( 'altis.modules.loaded' );
}

/**
 * Get raw data from Composer's installed.json
 *
 * This returns the raw data that Composer generates. installed.json is
 * equivalent to composer.lock, but is stored within the vendor directory, and
 * is a more accurate data store.
 *
 * @return array Map of package name => package data.
 */
function get_composer_data() : array {
	static $data = null;

	if ( empty( $data ) ) {
		$composer_file = ROOT_DIR . '/vendor/composer/installed.json';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw_data = json_decode( file_get_contents( $composer_file ) );

		// Re-index by package slug.
		$data = [];
		foreach ( $raw_data as $package ) {
			$data[ $package->name ] = $package;
		}
	}

	return $data;
}

/**
 * Register a class path to be autoloaded.
 *
 * Registers a namespace to be autoloaded from a given path, using the
 * WordPress/HM-style filenames (`class-{name}.php`).
 *
 * @link https://engineering.hmn.md/standards/style/php/#file-naming
 *
 * @param string $prefix Prefix to autoload from.
 * @param string $path Path to validate.
 */
function register_class_path( string $prefix, string $path ) {
	$loader = new Autoloader( $prefix, $path );
	spl_autoload_register( [ $loader, 'load' ] );
}
