<?php

namespace WPSL\SiteOriginCSS;

use PHPUnit\Framework\TestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use wpCloud\StatelessMedia\WPStatelessStub;

/**
 * Class ClassSiteOriginCSSTest
 */

class ClassSiteOriginCSSTest extends TestCase {
  const TEST_URL = 'https://test.test';
  const UPLOADS_URL = self::TEST_URL . '/uploads';
  const TEST_FILE = 'so-css/style.css';
  const SRC_URL = self::TEST_URL . '/' . self::TEST_FILE;
  const DST_URL = WPStatelessStub::TEST_GS_HOST . '/' . self::TEST_FILE;
  const TEST_UPLOAD_DIR = [
    'baseurl' => self::UPLOADS_URL,
    'basedir' => '/var/www/uploads'
  ];

  // Adds Mockery expectations to the PHPUnit assertions count.
  use MockeryPHPUnitIntegration;

  public function setUp(): void {
		parent::setUp();
		Monkey\setUp();

    // WP mocks
    Functions\when('wp_upload_dir')->justReturn( self::TEST_UPLOAD_DIR );
        
    // WP_Stateless mocks
    Filters\expectApplied('wp_stateless_file_name')
      ->andReturn( self::TEST_FILE );

    Functions\when('ud_get_stateless_media')->justReturn( WPStatelessStub::instance() );
  }
	
  public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

  public function testShouldInitHooks() {
    $siteOriginCSS = new SiteOriginCSS();

    $siteOriginCSS->module_init([]);

    self::assertNotFalse( has_filter('set_url_scheme', [ $siteOriginCSS, 'set_url_scheme' ]) );
    self::assertNotFalse( has_action('admin_menu', [ $siteOriginCSS, 'action_admin_menu' ]) );
  }

  public function testShouldSaveFieldValue() {
    $siteOriginCSS = new SiteOriginCSS();

    Actions\expectDone('sm:sync::syncFile')->once();

    $this->assertEquals(
      self::DST_URL,
      $siteOriginCSS->set_url_scheme(self::SRC_URL, null, null) 
    );
  }

  public function testShouldDeleteCssFile() {
    $siteOriginCSS = new SiteOriginCSS();

    Functions\when('current_user_can')->justReturn( true );

    $_POST['siteorigin_custom_css_save'] = true;

    Actions\expectDone('sm:sync::deleteFiles')->once();

    $siteOriginCSS->action_admin_menu();

    self::assertTrue(true);
  }
}
