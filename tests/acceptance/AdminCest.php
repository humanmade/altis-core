<?php // phpcs:disable
/**
 * Tests for module version checks
 */

/**
 * Test for module version checks
 */
class AdminCest {

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

	public function globalContentSiteExists( AcceptanceTester $I ) {
		$I->wantToTest( 'Global content repo site exists' );
		$I->loginAsAdmin();
		// Check if the database entry exists.
		$I->canSeeBlogInDatabase( [ 'blog_id' => 2, 'path' => '/repo/' ] );
		// Check the site is available in site list.
		$I->amOnAdminPage( 'network/sites.php' );
		$I->see( 'Global Content Repo' );
	}

}
