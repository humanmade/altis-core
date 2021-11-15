<?php
/**
 * Test global content repo functionality.
 *
 * phpcs:disable WordPress.Files, HM.Files, HM.Functions.NamespacedFunctions, WordPress.NamingConventions
 */

namespace GlobalContentRepo;

/**
 * Test gobal content repo functionality.
 */
class GlobalContentRepoTest extends \Codeception\TestCase\WPTestCase {
	/**
	 * Tester
	 *
	 * @var \IntegrationTester
	 */
	protected $tester;

	/**
	 * Test global content repo site exists.
	 *
	 * @return void
	 */
	public function testGlobalContentRepoExists() {
		$site_id = \Altis\Global_Content\get_site_id();
		$this->assertIsNumeric( $site_id, 'Global content repo site id is saved to database.' );

		$site = get_site( $site_id );
		$this->assertInstanceOf( \WP_Site::class, $site, 'Test global content repo site exists.' );
	}
}
