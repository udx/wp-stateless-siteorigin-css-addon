<?php

namespace WPSL\SiteOriginCSS;

use wpCloud\StatelessMedia\Compatibility;

class SiteOriginCSS extends Compatibility {
  protected $id = 'so-css';
  protected $title = 'SiteOrigin CSS';
  protected $constant = 'WP_STATELESS_COMPATIBILITY_SOCSS';
  protected $description = 'Ensures compatibility with CSS files generated by SiteOrigin.';
  protected $plugin_file = 'so-css/so-css.php';

  /**
   * @param $sm
   */
  public function module_init($sm) {
    add_filter('set_url_scheme', array($this, 'set_url_scheme'), 20, 3);
    add_action('admin_menu', array($this, 'action_admin_menu'), 3);
  }

  /**
   * Change Upload BaseURL when CDN Used.
   * @param $url
   * @param $scheme
   * @param $orig_scheme
   * @return string
   */
  public function set_url_scheme($url, $scheme, $orig_scheme) {
    $position = strpos($url, 'so-css/');
    if ($position !== false) {
      $upload_data = wp_upload_dir();
      $name = substr($url, $position);
      // We need to get the absolute path before adding the bucket dir to name.
      $absolutePath = $upload_data['basedir'] . '/' . $name;
      $name = apply_filters('wp_stateless_file_name', $name, 0);
      do_action('sm:sync::syncFile', $name, $absolutePath);
      // echo "do_action( 'sm:sync::syncFile', $name, $absolutePath);\n";
      $url = ud_get_stateless_media()->get_gs_host() . '/' . $name;
    }
    return $url;
  }

  /**
   * Change Upload BaseURL when CDN Used.
   */
  public function action_admin_menu() {
    if (current_user_can('edit_theme_options') && isset($_POST['siteorigin_custom_css_save'])) {
      try {
        $prefix = apply_filters('wp_stateless_file_name', 'so-css', 0);
        do_action('sm:sync::deleteFiles', $prefix);
        // die();
        // $object_list = ud_get_stateless_media()->get_client()->list_objects("prefix=$prefix");
        // $files_array = $object_list->getItems();
        // foreach ($files_array as $file) {
        //     do_action( 'sm:sync::deleteFile', $file->name );
        // }
      } catch (Exception $e) {
      }
    }
  }
}