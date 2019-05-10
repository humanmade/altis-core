<?php

namespace Altis\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface {

	/**
	 * Called when the plugin is activated.
	 *
	 * @param Composer $composer
	 * @param IOInterface $io
	 */
	public function activate( Composer $composer, IOInterface $io ) {
		$current_installer = $composer->getInstallationManager()->getInstaller( 'wordpress-plugin' );
		if ( $current_installer ) {
			$composer->getInstallationManager()->removeInstaller( $current_installer );
		}

		$installer = new Installer( $io, $composer );
		$composer->getInstallationManager()->addInstaller( $installer );

	}
}
