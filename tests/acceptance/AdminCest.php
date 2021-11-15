<?php
/**
 * Tests for core module's admin features.
 *
 * phpcs:disable WordPress.Files, WordPress.NamingConventions, PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions
 */

/**
 * Test core module admin features.
 */
class AdminCest {

	/**
	 * Test module versions are displayed correctly on about page.
	 *
	 * @param AcceptanceTester $I Tester
	 *
	 * @return void
	 */
	public function moduleVersionsDisplayed( AcceptanceTester $I ) {
		$I->wantToTest( 'About page shows module versions.' );
		$I->loginAsAdmin();
		$I->amOnAdminPage( 'about.php' );
		$I->see( 'Current Module Versions' );
		$modules = [
			'CMS',
			'Analytics',
			'Cloud',
			'Core',
			'Developer Tools',
			'Documentation',
			'Search',
			'Local Chassis',
			'Local Server',
			'Media',
			'Multilingual',
			'Privacy',
			'Security',
			'SEO',
			'SSO',
			'Workflow',
		];
		foreach ( $modules as $module ) {
			$I->see( $module );
		}
	}

}
