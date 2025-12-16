<?php
/**
 * Tests for media upload functionality.
 *
 * phpcs:disable WordPress.Files, WordPress.NamingConventions, PSR1.Classes.ClassDeclaration.MissingNamespace, HM.Functions.NamespacedFunctions
 */

/**
 * Test media upload via browser.
 */
class MediaImportCest {

	/**
	 * Test that media upload works correctly via the admin interface.
	 *
	 * This test verifies that media upload functionality is working,
	 * which depends on correct package versions being installed.
	 *
	 * @param AcceptanceTester $I Tester
	 *
	 * @return void
	 */
	public function mediaUploadWorks( AcceptanceTester $I ) {
		$I->wantToTest( 'Media can be uploaded via the admin interface.' );

		// Login as admin.
		$I->loginAsAdmin();

		// Go to Media > Add New page.
		$I->amOnAdminPage( 'media-new.php' );

		// Verify we're on the upload page.
		$I->see( 'Upload New Media' );

		// Attach the test image file to the file input.
		// The file path is relative to the Codeception _data directory or absolute.
		$I->attachFile( 'input[type="file"]', 'wp-logo.png' );

		// Sleep to allow any JS processing to complete (e.g., generating thumbnails).
		$I->wait( 3 );

		// Wait for the upload to complete and appear in the media list.
		$I->waitForElement( '.media-item', 30 );
		$I->waitForElement( 'img.pinkynail', 30 );

		// Verify the upload was successful by checking for the filename in the uploaded item.
		$I->see( 'wp-logo' ); // on multiple runs this may be 'wp-logo-1', 'wp-logo-2', etc. so just check for 'wp-logo'.
	}
}
