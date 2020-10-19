<?php
/**
 * The Module object provides a common interface for registering
 * modules and carrying out module related tasks.
 *
 * @package altis/core
 */

namespace Altis;

/**
 * Altis Module object.
 *
 * Manages the module settings and configuration.
 */
class Module {
	/**
	 * Store of all registered modules.
	 *
	 * @var array
	 */
	protected static $modules = [];

	/**
	 * The slug for the module, used to reference individual modules.
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * The directory where the module lives.
	 *
	 * @var string
	 */
	protected $directory;

	/**
	 * The human readable title of the module.
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Module default settings at a minimum indicate the enabled status of the
	 * module but can contain any arbitrary values to use as feature flags
	 * or to modify the modules behaviour.
	 *
	 * @var array
	 */
	protected $default_settings;

	/**
	 * Module constructor. Registers settings, defaults and module location.
	 *
	 * @param string $slug A string ID for the module.
	 * @param string $directory The directory the module is located in.
	 * @param string $title A human readable title for the module.
	 * @param array|null $default_settings Optional default settings for the module.
	 */
	protected function __construct( string $slug, string $directory, string $title, ?array $default_settings = null ) {
		$this->slug = $slug;
		$this->directory = $directory;
		$this->title = $title;
		$this->default_settings = array_merge( [
			'enabled' => false,
		], $default_settings ?? [] );
	}

	/**
	 * Registers a module with the store.
	 *
	 * @param string $slug The string identifier for the module used for later reference.
	 * @param string $directory The root directory of the module.
	 * @param string $title Human readable module title.
	 * @param array|null $default_settings Optional default settings array.
	 * @param callable|null $loader Optional loader function to call module bootstrapping code.
	 * @return Module
	 */
	public static function register( string $slug, string $directory, string $title, ?array $default_settings = null, ?callable $loader = null ) : Module {
		$module = new Module( $slug, $directory, $title, $default_settings );

		// Store the module.
		self::$modules[ $slug ] = $module;

		// Add the loader to the module's loaded action.
		if ( is_callable( $loader ) ) {
			add_action( "altis.modules.{$slug}.loaded", $loader, 1 );
		}

		// Add the module's default settings to the default config.
		add_filter( 'altis.config.default', function ( $config ) use ( $module ) {
			$config['modules'] = $config['modules'] ?? [];
			$config['modules'][ $module->get_slug() ] = $module->get_default_settings();
			return $config;
		} );

		return $module;
	}

	/**
	 * Retrieve an individual module by its slug.
	 *
	 * @param string $slug The module ID string.
	 * @return Module
	 */
	public static function get( string $slug ) : Module {
		return self::$modules[ $slug ];
	}

	/**
	 * Retrieve all registered modules.
	 *
	 * @return array
	 */
	public static function get_all() : array {
		return self::$modules;
	}

	/**
	 * Get the module slug.
	 *
	 * @return string
	 */
	public function get_slug() : string {
		return $this->slug;
	}

	/**
	 * Get the module title.
	 *
	 * @return string
	 */
	public function get_title() : string {
		return $this->title;
	}

	/**
	 * Get the module settings.
	 *
	 * @return array
	 */
	public function get_default_settings() : array {
		return $this->default_settings;
	}

	/**
	 * Get the modules settings.
	 *
	 * @return array
	 */
	public function get_settings() : array {
		if ( ! did_action( 'altis.modules.init' ) ) {
			trigger_error( 'Module get_settings() was called too early', E_USER_WARNING );
			return [];
		}

		$config = get_config();

		return $config['modules'][ $this->slug ] ?? [];
	}

	/**
	 * Get a module setting by name.
	 *
	 * @param string $name The setting name to retrieve.
	 * @return mixed
	 */
	public function get_setting( string $name ) {
		$settings = $this->get_settings();

		return $settings[ $name ] ?? null;
	}

	/**
	 * Get the module directory path.
	 *
	 * @return string
	 */
	public function get_directory() : string {
		return $this->directory;
	}
}
