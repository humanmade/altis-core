<?php // phpcs:disable
namespace GlobalContentRepo;

class GlobalContentRepoTest extends \Codeception\TestCase\WPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    public function testGlobalContentRepoExists()
    {
        $site_id = \Altis\Global_Content\get_site_id();
        $this->assertIsNumeric( $site_id, 'Global content repo site id is saved to database.' );

        $site = get_site( $site_id );
        $this->assertInstanceOf( \WP_Site::class, $site, 'Test global content repo site exists.' );
    }
}
