<?php
/**
 * Altis Core Installer.
 *
 * @package altis/core
 */

namespace Altis\Composer;

use Composer\Installers\Installer as BaseInstaller;
use Composer\Package\PackageInterface;

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Snake case to match Composer.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase -- Snake case to match Composer.

/**
 * Altis Core Composer Installer.
 */
class Override_Installer extends BaseInstaller {
	/**
	 * Overridden packages.
	 *
	 * Set in setInstallOverrides by the plugin hooks.
	 *
	 * @var string[]
	 */
	protected $installOverrides = [];

	/**
	 * Check if the installer supports a given type.
	 *
	 * @param string $type Package type string.
	 * @return bool
	 */
	public function supports( $type ) {
		return in_array( $type, [ 'wordpress-plugin', 'wordpress-muplugin' ], true );
	}

	/**
	 * Gets all overridden packages from all extra.altis.install-overrides entries
	 *
	 * @param string[] $overrides Overridden package names.
	 */
	public function setInstallOverrides( $overrides ) : void {
		$this->installOverrides = $overrides;
	}

	/**
	 * Modifies the install path for Altis module dependencies.
	 *
	 * @param PackageInterface $package Composer package manager interface.
	 * @param string $framework_type The type of framework for deriving the install path.
	 * @return string
	 */
	public function getInstallPath( PackageInterface $package, $framework_type = '' ) {
		/**
		 * Allow specific wordpress-plugin packages to skip the wordpress-plugin
		 * installer. We use this to stop plugins that are bundled with modules are
		 * not installed to the wp-content/plugins path.
		 */
		if ( ! in_array( $package->getType(), [ 'wordpress-plugin', 'wordpress-muplugin' ], true ) || ! in_array( $package->getName(), $this->installOverrides, true ) ) {
			return parent::getInstallPath( $package, $framework_type );
		}

		$this->initializeVendorDir();

		// @codingStandardsIgnoreLine
		$vendor_dir = $this->vendorDir;

		$base_path = ( $vendor_dir ? $vendor_dir . '/' : '' ) . $package->getPrettyName();
		$target_dir = $package->getTargetDir();

		return $base_path . ( $target_dir ? '/' . $target_dir : '' );
	}
}
