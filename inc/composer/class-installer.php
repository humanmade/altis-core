<?php

namespace HM\Platform\Composer;

use Composer\Installers\Installer as BaseInstaller;
use Composer\Package\PackageInterface;

class Installer extends BaseInstaller {
	public function getInstallPath( PackageInterface $package, $framework_type = '' ) {

		/**
		 * Allow specific wordpress-plugin packages to skip the wordpress-plugin
		 * installer. We use this to stop plugins that are bundled with modules are
		 * not installed to the wp-content/plugins path.
		 */
		$excluded_plugins = [
			'humanmade/smart-media',
			'humanmade/tachyon-plugin',
			'humanmade/s3-uploads',
			'humanmade/gaussholder',
			'humanmade/aws-rekognition',
			'humanmade/two-factor',
			'humanmade/require-login',
			'humanmade/stream',
			'humanmade/wp-simple-saml',
			'humanmade/delegated-oauth',
			'10up/elasticpress',
			'humanmade/hm-redirects',
			'humanmade/msm-sitemap',
			'humanmade/wp-seo',
			'humanmade/amp',
			'humanmade/facebook-instant-articles-wp',
			'humanmade/meta-tags',
			'johnbillion/query-monitor',
			'humanmade/workflows',
		];

		if ( ! in_array( $package->getType(), [ 'wordpress-plugin' ], true ) || ! in_array( $package->getName(), $excluded_plugins, true ) ) {
			return parent::getInstallPath( $package, $framework_type );
		}

		$this->initializeVendorDir();

		$base_path = ( $this->vendorDir ? $this->vendorDir . '/' : '' ) . $package->getPrettyName();
		$target_dir = $package->getTargetDir();

		return $base_path . ( $target_dir ? '/' . $target_dir : '' );
	}
}
