<?php
/**
 * Tests for core module's admin features.
 *
 * phpcs:disable WordPress.Files, WordPress.NamingConventions, PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions
 */

use Codeception\Util\Locator;

/**
 * Test core module admin features.
 */
class AdminCest {

	/**
	 * Test module versions are displayed correctly on about page.
	 *
	 * @param AcceptanceTester $I Tester
	 *
	 * @throws \Exception If Composer's installed.json file could not be parsed.
	 *
	 * @return void
	 */
	public function moduleVersionsDisplayed( AcceptanceTester $I ) {
		$I->wantToTest( 'About page shows correct module versions.' );
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'about.php' );
		$I->see( 'Current Module Versions' );

		$composer = json_decode( file_get_contents( 'vendor/composer/installed.json' ) );

		if ( isset( $composer->packages ) ) {
			$packages = $composer->packages;
		} elseif ( is_array( $composer ) ) {
			$packages = $composer;
		} else {
			throw new \Exception( 'Unable to parse Composer\'s installed.json' );
		}

		$modules = [
			'altis/cms' => [ 'name' => 'CMS' ],
			'altis/cloud' => [ 'name' => 'Cloud' ],
			'altis/core' => [ 'name' => 'Core' ],
			'altis/dev-tools' => [ 'name' => 'Developer Tools' ],
			'altis/documentation' => [ 'name' => 'Documentation' ],
			'altis/enhanced-search' => [ 'name' => 'Search' ],
			'altis/local-server' => [ 'name' => 'Local Server' ],
			'altis/media' => [ 'name' => 'Media' ],
			'altis/privacy' => [ 'name' => 'Privacy' ],
			'altis/security' => [ 'name' => 'Security' ],
			'altis/seo' => [ 'name' => 'SEO' ],
			'altis/sso' => [ 'name' => 'SSO' ],
		];

		foreach ( $packages as $package ) {
			if ( false === strpos( $package->name, 'altis/' ) || ! in_array( $package->name, array_keys( $modules ), true ) ) {
				continue;
			}
			$modules[ $package->name ]['version'] = $package->version;
			$modules[ $package->name ]['hash'] = $package->dist->reference;
		}

		foreach ( $modules as $module ) {
			$tr = Locator::contains( 'tr', $module['name'] );
			$I->see( $module['version'], $tr );
			$I->see( $module['hash'], $tr );
		}
	}

}
