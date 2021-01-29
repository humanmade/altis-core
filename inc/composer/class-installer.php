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
class Installer extends BaseInstaller {
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
	 * @param string[] $overrides
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
		$legacy = [
			'10up/elasticpress',
			'altis/aws-analytics',
			'altis/browser-security',
			'altis/consent',
			'altis/consent-api',
			'altis/experiments',
			'darylldoyle/safe-svg',
			'humanmade/aws-rekognition',
			'humanmade/aws-ses-wp-mail',
			'humanmade/cavalcade',
			'humanmade/debug-bar-elasticpress',
			'humanmade/delegated-oauth',
			'humanmade/gaussholder',
			'humanmade/hm-gtm',
			'humanmade/hm-redirects',
			'humanmade/hm-limit-login-attempts',
			'humanmade/ludicrousdb',
			'humanmade/meta-tags',
			'humanmade/php-basic-auth',
			'humanmade/publication-checklist',
			'humanmade/post-cloner',
			'humanmade/require-login',
			'humanmade/s3-uploads',
			'humanmade/smart-media',
			'humanmade/stream',
			'humanmade/tachyon-plugin',
			'humanmade/two-factor',
			'humanmade/workflows',
			'humanmade/wp-redis',
			'humanmade/wp-seo',
			'humanmade/wp-simple-saml',
			'johnbillion/query-monitor',
			'stuttter/ludicrousdb',
			'stuttter/wp-user-signups',
		];

		$excluded_plugins = array_unique( array_merge( $legacy, $this->installOverrides ) );

		if ( ! in_array( $package->getType(), [ 'wordpress-plugin', 'wordpress-muplugin' ], true ) || ! in_array( $package->getName(), $excluded_plugins, true ) ) {
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
