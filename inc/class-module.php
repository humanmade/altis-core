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
	 * Settings array.
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Module loader callback function.
	 *
	 * @var callable
	 */
	protected $loader;

	protected function __construct( string $slug, string $directory, string $title, array $settings, callable $loader ) {
		$this->slug = $slug;
		$this->directory = $directory;
		$this->title = $title;
		$this->settings = $settings;
		$this->loader = $loader;
	}

	/**
	 * Registers a module with the store.
	 *
	 * @param string $slug
	 * @param string $directory
	 * @param string $title
	 * @param array $settings
	 * @param callable $loader
	 * @return Module
	 */
	public static function register( string $slug, string $directory, string $title, array $settings, callable $loader ) : Module {
		$module = new Module( $slug, $directory, $title, $settings, $loader );

		// Store the module.
		self::$modules[ $slug ] = $module;

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
	 * Calls the module loader function.
	 *
	 * @return void
	 */
	public function load() {
		if ( $this->settings['enabled'] ?? false ) {
			$this->loader( $this->settings );
		}
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
