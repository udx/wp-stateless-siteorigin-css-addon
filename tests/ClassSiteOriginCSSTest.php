<?php

namespace SLCA\SiteOriginCSS;

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
    Functions\when('check_admin_referer')->justReturn( true );
        
    // WP_Stateless mocks
    Functions\when('ud_get_stateless_media')->justReturn( WPStatelessStub::instance() );
  }
	
  public function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

  public function testShouldInitHooks() {
    $siteOriginCSS = new SiteOriginCSS();

    $siteOriginCSS->module_init([]);

    self::assertNotFalse( has_filter('siteorigin_custom_css_file', [ $siteOriginCSS, 'get_custom_css_file' ]) );
    self::assertNotFalse( has_filter('set_url_scheme', [ $siteOriginCSS, 'set_url_scheme' ]) );
    self::assertNotFalse( has_filter('sm:sync::syncArgs', [ $siteOriginCSS, 'sync_args' ]) );
    self::assertNotFalse( has_filter('sm:sync::nonMediaFiles', [ $siteOriginCSS, 'get_sync_files' ]) );
    self::assertNotFalse( has_filter('set_url_scheme', [ $siteOriginCSS, 'set_url_scheme' ]) );
    self::assertNotFalse( has_action('admin_menu', [ $siteOriginCSS, 'action_admin_menu' ]) );
  }

  public function testShouldRewriteURL() {
    $siteOriginCSS = new SiteOriginCSS();

    Actions\expectDone('sm:sync::syncFile')->once();
    Filters\expectApplied('wp_stateless_addon_files_root')->once();
    Filters\expectApplied('wp_stateless_file_name')->once();

    $siteOriginCSS->set_url_scheme(self::SRC_URL, null, null);
  }

  public function testShouldNotRewriteURL() {
    $siteOriginCSS = new SiteOriginCSS();

    $siteOriginCSS->set_url_scheme(self::TEST_URL, null, null);

    $this->assertSame( 0, did_action('sm:sync::syncFile') );
    $this->assertSame( 0, Filters\applied('wp_stateless_addon_files_root') );
    $this->assertSame( 0, Filters\applied('wp_stateless_file_name') );
  }

  public function testShouldDeleteCssFile() {
    $siteOriginCSS = new SiteOriginCSS();

    Functions\when('current_user_can')->justReturn( true );

    $_POST['siteorigin_custom_css_save'] = true;

    Actions\expectDone('sm:sync::deleteFiles')->once();
    Filters\expectApplied('wp_stateless_file_name')->once();

    $siteOriginCSS->action_admin_menu();

    self::assertTrue(true);
  }

  public function testShouldUpdateArgs() {
    $siteOriginCSS = new SiteOriginCSS();

    $args = $siteOriginCSS->sync_args([], self::TEST_FILE, '', false);

    self::assertTrue( isset( $args['source'] ) );
    self::assertTrue( isset( $args['source_version'] ) );
    self::assertEquals( 'SiteOrigin CSS', $args['source'] );
    self::assertFalse( isset( $args['name_with_root'] ) );
  }

  public function testShouldUpdateArgsStateless() {
    $siteOriginCSS = new SiteOriginCSS();

    ud_get_stateless_media()->set('sm.mode', 'stateless');

    $args = $siteOriginCSS->sync_args([], self::TEST_FILE, '', false);

    self::assertTrue( isset( $args['source'] ) );
    self::assertTrue( isset( $args['source_version'] ) );
    self::assertEquals( 'SiteOrigin CSS', $args['source'] );
    self::assertTrue( isset( $args['name_with_root'] ) );
  }

  public function testShouldNotUpdateArgs() {
    $siteOriginCSS = new SiteOriginCSS();

    self::assertEquals(
      0,
      count( $siteOriginCSS->sync_args([], self::TEST_URL, '', false) )
    );
  }
}
