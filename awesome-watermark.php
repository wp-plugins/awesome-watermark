<?php

/*
  Plugin Name: Awesome Watermark
  Plugin URI: http://scriptbaker.com
  Description: Apply text or image watermark to any image and thumbnails sizes.
  Version: 1.0
  Author: Tahir Yasin
  Author URI: http://scriptbaker.com
  Text Domain: awesome-watermark

  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

define('AWM_URL', plugin_dir_url(__FILE__));
define('AWM_PATH', untrailingslashit(dirname(__FILE__)));
define('AWM_PLUGIN_BASENAME', plugin_basename(__FILE__));

if (is_admin()) {

    require_once 'includes/AwesomeWaterMark.php';
    $AwesomeWaterMark = new AwesomeWaterMark;

    register_activation_hook(__FILE__, 'awm_default_settings');
    add_action('admin_init', 'awm_plugin_redirect');

    /**
     * Insert default configuration parameters to database
     */
    function awm_default_settings()
    {
        $defaults = array(
            'sizes' => array(
                'full size'
            ),
            'watermark_images' => array(
                'full size' => '',
            ),
            'watermark_type' => array(
                'full size' => 'text',
            ),
            'watermark_text' => array(
                'full size' => '© '.get_bloginfo('name'),
            ),
            'watermark_position' => array(
                'full size' => '5',
            ),
            'quality' => '90',
            'angle' => '45',
            'margin_leftright' => '0',
            'margin_topbottom' => '0',
            'tile_watermark' => 0,
            'backup' => 1,
            'scale_watermark' => 1,
            'watermark_text_font' => 'arial.ttf',
            'watermark_text_fontsize' => '12',
            'watermark_text_color' => '#eeee22',
        );

        add_option('awm_general_settings', $defaults);
        add_option('awm_do_activation_redirect', true);
    }

    /**
     * Redirect to Plugin Settings page after activation
     */
    function awm_plugin_redirect()
    {
        if (get_option('awm_do_activation_redirect', false)) {
            delete_option('awm_do_activation_redirect');
            wp_redirect(admin_url('options-general.php?page=awm_options'));
        }
    }
}
?>