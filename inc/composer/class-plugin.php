<?php

namespace Altis\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface {

	/**
	 * Called when the plugin is activated.
	 *
	 * @param Composer $composer
	 * @param IOInterface $io
	 */
	public function activate( Composer $composer, IOInterface $io ) {
		$this->composer = $composer;
		$this->io = $io;

		$installer = new Installer( $this->io, $this->composer );
		$this->composer->getInstallationManager()->addInstaller( $installer );
	}

	/**
	 * Get the events the plugin subscribed to.
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return [
			'init' => 'init',
		];
	}

	/**
	 * Register the installer on the `init` hook.
	 *
	 * We want to register later than the composer-installers installer
	 * as the last-added installer takes precedence. This event only runs
	 * on update (if this plugin is already present), so we have to add it
	 * in addition ot the $this->activate() method.
	 */
	public function init() {
		$installer = new Installer( $this->io, $this->composer );
		$this->composer->getInstallationManager()->addInstaller( $installer );
	}
}
