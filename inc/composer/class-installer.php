<?php
/**
 * Altis Core Installer.
 *
 * @package altis/core
 */

namespace Altis\Composer;

use Composer\Installers\Installer as BaseInstaller;
use Composer\Package\PackageInterface;

/**
 * Altis Core Composer Installer.
 */
class Installer extends BaseInstaller {

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
	 * @return string[]
	 */
	protected function getAllInstallOverrides() {
		$rm = $this->composer->getRepositoryManager();
		$repo = $rm->getLocalRepository();
		$packages = $repo->getPackages();

		$overridden = [];
		foreach ( $packages as $package ) {
			$extra = $package->getExtra();
			if ( ! isset( $extra['altis'] ) || ! isset( $extra['altis']['install-overrides'] ) ) {
				continue;
			}

			$overridden = array_merge( $overridden, $extra['altis']['install-overrides'] );
		}

		return $overridden;
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

		$overrides = $this->getAllInstallOverrides();
		$excluded_plugins = array_unique( array_merge( $legacy, $overrides ) );

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
