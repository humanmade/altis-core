<?php

namespace Altis\Composer;

use Composer\Installers\Installer as BaseInstaller;
use Composer\Package\PackageInterface;

class Installer extends BaseInstaller {

	/**
	 * Check if the installer supports a given type.
	 *
	 * @param string $type
	 * @return bool
	 */
	public function supports( $type ) {
		return in_array( $type, [ 'wordpress-plugin', 'wordpress-muplugin' ], true );
	}

	public function getInstallPath( PackageInterface $package, $framework_type = '' ) {
		/**
		 * Allow specific wordpress-plugin packages to skip the wordpress-plugin
		 * installer. We use this to stop plugins that are bundled with modules are
		 * not installed to the wp-content/plugins path.
		 */
		$excluded_plugins = [
			'10up/elasticpress',
			'altis/aws-analytics',
			'altis/experiments',
			'humanmade/amp',
			'humanmade/aws-rekognition',
			'humanmade/cavalcade',
			'humanmade/delegated-oauth',
			'humanmade/facebook-instant-articles-wp',
			'humanmade/gaussholder',
			'humanmade/hm-gtm',
			'humanmade/hm-redirects',
			'humanmade/meta-tags',
			'humanmade/msm-sitemap',
			'humanmade/publication-checklist',
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
			'stuttter/wp-user-signups',
		];

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
