<?php
/**
 * Altis Core Composer plugin class.
 *
 * @package altis/core
 */

namespace Altis\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\InstallerEvent;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;

/**
 * Altis core composer plugin.
 */
class Plugin implements PluginInterface, EventSubscriberInterface {
	protected $installer;

	/**
	 * Called when the plugin is activated.
	 *
	 * @param Composer $composer The composer class.
	 * @param IOInterface $io The composer disk interface.
	 */
	public function activate( Composer $composer, IOInterface $io ) {
		$this->composer = $composer;
		$this->io = $io;

		$this->installer = new Installer( $this->io, $this->composer );
		$this->composer->getInstallationManager()->addInstaller( $this->installer );
	}

	/**
	 * Get the events the plugin subscribed to.
	 *
	 * @return array
	 */
	public static function getSubscribedEvents() {
		return [
			'init' => 'init',
			'pre-operations-exec' => 'pre_operations_exec',
			'post-dependencies-solving' => 'post_dependencies_solving',
			'post-package-install' => 'post_package_install',
			'post-package-update' => 'post_package_install',
		];
	}

	/**
	 * Register the installer on the `init` hook.
	 *
	 * We want to register later than the composer-installers installer
	 * as the last-added installer takes precedence. This event only runs
	 * on update (if this plugin is already present), so we have to add it
	 * in addition to the $this->activate() method.
	 */
	public function init() {
		$this->installer = new Installer( $this->io, $this->composer );
		$this->composer->getInstallationManager()->addInstaller( $this->installer );
	}

	/**
	 * Update install overrides once dependencies have been resolved. (Composer v2)
	 *
	 * @param InstallerEvent $event
	 * @return void
	 */
	public function pre_operations_exec( InstallerEvent $event )  {
		$transaction = $event->getTransaction();
		$operations = $transaction->getOperations();
		if ( empty( $operations ) ) {
			return;
		}

		// Work out which packages we already have.
		$repo = $event->getComposer()->getLocker()->getLockedRepository( $event->isDevMode() );
		$packages = [];
		foreach ( $repo->getPackages() as $package ) {
			$packages[ $package->getName() ] = $package;
		}

		$overrides = $this->resolve_packages_composer_v2( $packages, $operations );
		$this->installer->setInstallOverrides( $overrides );
	}

	/**
	 * Resolve packages after operations for Composer v2
	 *
	 * @param array $packages Map of package name => package instance for already installed packages.
	 * @param OperationInterface[] $operations List of operations
	 * @return string[] List of packages to override.
	 */
	protected function resolve_packages_composer_v2( array $packages, array $operations ) {
		// Then, resolve the operations we're about to apply.
		// (In Composer v2, this is when running the initial install step.)
		foreach ( $operations as $operation ) {
			switch ( $operation::TYPE ) {
				case 'install':
				case 'markAliasInstalled':
					$package = $operation->getPackage();
					$packages[ $package->getName() ] = $package;
					break;

				case 'update':
					$package = $operation->getTargetPackage();
					$packages[ $package->getName() ] = $package;
					break;

				case 'uninstall':
				case 'markAliasUninstalled':
					$package = $operation->getPackage();
					unset( $packages[ $package->getName() ] );
					break;

				default:
					break;
			}
		}

		return $this->getAllInstallOverrides( $packages );
	}

	/**
	 * Update install overrides once dependencies have been resolved. (Composer v1)
	 *
	 * @param InstallerEvent $event
	 * @return void
	 */
	public function post_dependencies_solving( InstallerEvent $event )  {
		$operations = $event->getOperations();
		if ( empty( $operations ) ) {
			return;
		}

		// First, work out which packages we already have.
		$repo = $event->getInstalledRepo();
		$packages = [];
		foreach ( $repo->getPackages() as $package ) {
			$packages[ $package->getName() ] = $package;
		}

		// Then, resolve the operations we're about to apply.
		foreach ( $operations as $operation ) {
			switch ( $operation->getJobType() ) {
				case 'install':
				case 'markAliasInstalled':
					$package = $operation->getPackage();
					$packages[ $package->getName() ] = $package;
					break;

				case 'update':
					$package = $operation->getTargetPackage();
					$packages[ $package->getName() ] = $package;
					break;

				case 'uninstall':
				case 'markAliasUninstalled':
					$package = $operation->getPackage();
					unset( $packages[ $package->getName() ] );
					break;

				default:
					break;
			}
		}

		$overrides = $this->getAllInstallOverrides( $packages );
		$this->installer->setInstallOverrides( $overrides );
	}

	/**
	 * Handle first install of the altis/core package.
	 *
	 * @param PackageEvent Package installation event.
	 */
	public function post_package_install( PackageEvent $event ) {
		// See if we just got installed.
		$operation = $event->getOperation();
		if ( ! $operation instanceof InstallOperation && ! $operation instanceof UpdateOperation ) {
			return;
		}

		$package = $operation instanceof UpdateOperation ? $operation->getTargetPackage() : $operation->getPackage();
		if ( $package->getName() !== 'altis/core' ) {
			return;
		}

		// Just Installed! 👰🤵
		// This means we won't have caught the dependency resolution event,
		// so we need to do so now. PackageEvent subclasses InstallerEvent
		// in Composer v1, but not in v2.
		if ( $event instanceof InstallerEvent ) {
			// Composer v1
			$this->post_dependencies_solving( $event );
			return;
		}

		$operations = $event->getOperations();
		if ( empty( $operations ) ) {
			return;
		}

		// Work out which packages we already have.
		$repo = $event->getComposer()->getLocker()->getLockedRepository( $event->isDevMode() );
		$packages = [];
		foreach ( $repo->getPackages() as $package ) {
			$packages[ $package->getName() ] = $package;
		}

		$overrides = $this->resolve_packages_composer_v2( $packages, $operations );
		$this->installer->setInstallOverrides( $overrides );
	}

	/**
	 * Gets all overridden packages from all extra.altis.install-overrides entries
	 *
	 * @param PackageInterface[] $packages
	 * @return string[]
	 */
	protected function getAllInstallOverrides( $packages ) {
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
	 * {@inheritDoc}
	 *
	 * @param Composer $composer The composer class.
	 * @param IOInterface $io The composer disk interface.
	 */
	public function deactivate( Composer $composer, IOInterface $io ) {
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param Composer $composer The composer class.
	 * @param IOInterface $io The composer disk interface.
	 */
	public function uninstall( Composer $composer, IOInterface $io ) {
	}
}
