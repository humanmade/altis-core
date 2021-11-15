<?php // phpcs:disable

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

}
