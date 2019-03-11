<?php
/**
 * The Module object provides a common interface for registering
 * modules and carrying out module related tasks.
 *
 * @package hm-platform
 */

namespace HM\Platform;

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
	 * Module settings at a minimum indicate the enabled status of the
	 * module but can contain any arbitrary values to use as feature flags
	 * or to modify the modules behaviour.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Module default settings. Core and Basic tier modules will need
	 * to be enabled by default specifically during registration.
	 *
	 * @var array
	 */
	protected static $default_settings = [
		'enabled' => false,
	];

	protected function __construct( string $slug, string $directory, string $title, ?array $settings = null ) {
		$this->slug = $slug;
		$this->directory = $directory;
		$this->title = $title;
		$this->settings = array_merge( self::$default_settings, $settings ?? [] );
	}

	/**
	 * Registers a module with the store.
	 *
	 * @param string $slug The string identifier for the module used for later reference.
	 * @param string $directory The root directory of the module.
	 * @param string $title Human readable module title.
	 * @param ?array $settings Optional default settings array.
	 * @param ?callable $loader Optional loader function to call module bootstrapping code.
	 * @return Module
	 */
	public static function register( string $slug, string $directory, string $title, ?array $settings = null, ?callable $loader = null ) : Module {
		$module = new Module( $slug, $directory, $title, $settings );

		// Store the module.
		self::$modules[ $slug ] = $module;

		// Add the loader to the module's loaded action.
		if ( is_callable( $loader ) ) {
			add_action( "hm-platform.modules.{$slug}.loaded", $loader );
		}

		return $module;
	}

	/**
	 * Retrieve an individual module by its slug.
	 *
	 * @param string $slug
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
	public function get_settings() : array {
		return $this->settings;
	}

	/**
	 * Get a module setting by name.
	 *
	 * @return mixed
	 */
	public function get_setting( $name ) {
		return $this->settings[ $name ] ?? false;
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
