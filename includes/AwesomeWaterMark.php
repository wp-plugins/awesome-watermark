<?php
require_once 'AwesomeWaterMarker.php';
require_once 'AWM_Settings.php';

/**
 * Class AwesomeWaterMark
 */
class AwesomeWaterMark extends AWM_Settings
{

    /**
     * Main constructor
     */
    function __construct()
    {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array(&$this, 'register_general_settings'));
        add_action('admin_init', array(&$this, 'register_watermark_preview'));
        add_action("admin_enqueue_scripts", array($this, 'admin_head_script'));
        add_action('admin_init', array($this, 'customize_media_thickbox'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        $watermarker = new AwesomeWaterMarker('live');
        add_filter('wp_generate_attachment_metadata', array(&$watermarker, 'add_watermark'));
        add_filter('plugin_action_links_' . AWM_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        parent::__construct();
    }

    /**
     * Register General Settings tab
     */
    function register_general_settings()
    {
        $this->settings_tabs[$this->general_settings_key] = 'General';
        register_setting($this->general_settings_key, $this->general_settings_key, array($this, 'awm_options_validate'));
        add_settings_section('awm_general_section', 'General Settings', array($this, 'general_section_description'), $this->general_settings_key);
        add_settings_field('image_sizes', 'Target Image Sizes', array($this, 'image_size_field'), $this->general_settings_key, 'awm_general_section');
        add_settings_field('watermarks', 'Watermarks', array($this, 'watermark_field'), $this->general_settings_key, 'awm_general_section');
        add_settings_field('watermark_tile', 'Watermark Repeat', array($this, 'watermark_tile_field'), $this->general_settings_key, 'awm_general_section');
        add_settings_field('watermark_quality', 'Image Quality', array($this, 'watermark_quality_field'), $this->general_settings_key, 'awm_general_section');
        add_settings_field('watermark_angle', 'Watermark Angle', array($this, 'watermark_angle_field'), $this->general_settings_key, 'awm_general_section');
        add_settings_field('watermark_margins', 'Watermark Margins', array($this, 'watermark_margins_field'), $this->general_settings_key, 'awm_general_section');
        add_settings_field('watermark_scale', 'Watermark Scaling', array($this, 'watermark_scale_field'), $this->general_settings_key, 'awm_general_section');
        add_settings_field('watermark_backup', 'Backup', array($this, 'watermark_backup_field'), $this->general_settings_key, 'awm_general_section');
        add_settings_section('text_watermark_settings', 'Text Settings', array($this, 'general_section_description'), $this->general_settings_key);
        add_settings_field('watermark_text_font', 'Font', array($this, 'watermark_text_font_field'), $this->general_settings_key, 'text_watermark_settings');
        add_settings_field('watermark_text_fontsize', 'Font size', array($this, 'watermark_text_fontsize_field'), $this->general_settings_key, 'text_watermark_settings');
        add_settings_field('watermark_text_color', 'Text Color', array($this, 'watermark_color_field'), $this->general_settings_key, 'text_watermark_settings');
    }

    /**
     * Register Preview Tab
     */
    function register_watermark_preview()
    {
        $this->settings_tabs[$this->preview_section_slug] = 'Preview Watermarks';
        $this->settings_tabs[$this->help_section_slug] = 'Help';
        add_settings_section('awm_preview_section', 'Preview Watermarks', array($this, 'watermark_preview_section'), $this->preview_section_slug);
        add_settings_section('awm_help_section', '', array($this, 'help_section'), $this->help_section_slug);
    }

    /**
     *
     */
    function general_section_description()
    {

    }

    /**
     * Available Target image sizes
     */
    function image_size_field()
    {
        $html = '';
        $sizes = get_intermediate_image_sizes();
        $sizes[] = 'full size';
        $options = $this->configuration;
        $checked = 'chk';
        if (isset($options['sizes'])) {
            $options = $options['sizes'];
            $checked = '';
        }
        sort($sizes);
        foreach ($sizes as $s) {
            if ($checked != 'chk') {
                if (in_array($s, $options))
                    $checked = 'checked';
                else
                    $checked = '';
            }
            $html .= "<label><input value='" . $s . "' name='" . $this->general_settings_key . "[sizes][]' type='checkbox' " . $checked . "/> " . ucwords(str_replace(array('_', '-'), array(' ', ' '), $s)) . "</label><br />";
        }
        echo "<div id='image_sizes'>";
        echo $html;
        echo '<p class="description">Watermark will be applied to selected image sizes.</p>';
        echo '</div>';
    }


    /**
     * Watermark Selection field
     */
    function watermark_field()
    {
        $options = $this->general_settings;
        echo "<div class='metabox-holder' id='watermarks'>";
        if (!empty($options['sizes'])) {

            foreach ($options['sizes'] as $size) {
                if (isset($options['watermark_type'][$size])) {
                    $size_id = str_replace(' ', '_', $size);
                    $checkedType = !empty($options['watermark_type'][$size]) ? $options['watermark_type'][$size] : $this->general_settings['watermark_type']['default_watermark'];
                    ?>
                    <div id="watermark-<?php echo $size_id; ?>" class="postbox awm-postbox " style="">
                        <h3 class="hndle">
                            <span><?php echo ucwords(str_replace(array('_', '-'), array(' ', ' '), $size)); ?></span>
                        </h3>

                        <div class="inside">
                            <div class="main">
                                <label for="image_url-<?php echo $size_id ?>"><input title="Image Watermark"
                                                                                     id="type-image-<?php echo $size_id ?>"
                                                                                     type="radio"
                                                                                     name="<?php echo $this->general_settings_key; ?>[watermark_type][<?php echo $size; ?>]"
                                                                                     value="image" <?php checked($checkedType, 'image') ?> />
                                </label>
                                <input placeholder="Enter watermark image URL here" type="text"
                                       id="image_url-<?php echo $size_id ?>"
                                       name="<?php echo $this->general_settings_key; ?>[watermark_images][<?php echo $size; ?>]"
                                       value="<?php echo !empty($options['watermark_images'][$size]) ? $options['watermark_images'][$size] : ''; ?>"
                                       class="regular-text"/>
                                <input id="watermark-<?php echo $size_id ?>" type="button"
                                       class="button upload_image_button" value="Select Image"/>
                                <br>

                                <div class="clear">OR</div>
                                <label for="watermark-text-<?php echo $size_id ?>"><input
                                        id="type-text-<?php echo $size_id ?>" title="Text Watermark" type="radio"
                                        name="<?php echo $this->general_settings_key; ?>[watermark_type][<?php echo $size; ?>]"
                                        value="text" <?php checked($checkedType, 'text') ?> /> </label>
                                <input maxlength="100" id="watermark-text-<?php echo $size_id ?>"
                                       placeholder="Enter watermark text here" type="text"
                                       name="<?php echo $this->general_settings_key; ?>[watermark_text][<?php echo $size; ?>]"
                                       value="<?php echo !empty($options['watermark_text'][$size]) ? $options['watermark_text'][$size] : ''; ?>"
                                       class="regular-text watermark-text"/>
                                <table style="width:100%">
                                    <tr>
                                        <td align="center"><?php echo $this->watermark_position_field($size, $this->general_settings_key); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php
                }
            }
        } else
            echo "Please select an image size.";
        echo "</div>";
    }


    /**
     * Main Settings Page function
     */
    function options_callback()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : $this->general_settings_key;
        ?>

        <div class="wrap">
            <div class="icon32" id="icon-options-general"><br></div>
            <h2>Awesome Watermark</h2>
            <?php //settings_errors();
            ?>
            <h2 class="nav-tab-wrapper">
                <?php
                foreach ($this->settings_tabs as $tab_key => $tab_caption) {
                    $active = $active_tab == $tab_key ? 'nav-tab-active' : '';
                    echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->settings_page_key . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
                }
                ?>
            </h2>
            <form action="options.php" method="post">
                <?php settings_fields($active_tab); ?>
                <?php do_settings_sections($active_tab); ?>
                <?php if ($active_tab != $this->preview_section_slug && $active_tab != $this->help_section_slug) submit_button(); ?>
            </form>
        </div>
    <?php
    }


    /**
     * Watermark Quality slider
     */
    function watermark_quality_field()
    {
        $options = $this->general_settings;
        ?>
        <div id="watermark_quality_val" style="width:50px;display: inline-block;"><?php echo $options['quality'] ?>%
        </div>
        <div id="quality_slider" style="width:50%;display: inline-block"></div>
        <input id="watermark_quality" value='<?php echo $options['quality'] ?>'
               name='<?php echo $this->general_settings_key ?>[quality]' type='hidden'/><br/>
    <?php
    }

    /**
     * Scale watermark checkbox
     */
    function watermark_scale_field()
    {
        $options = $this->general_settings;
        $checked = $options['scale_watermark'];
        echo "<label><input value='1' name='" . $this->general_settings_key . "[scale_watermark]' type='checkbox' " . checked($checked, 1, false) . "/> Scale watermark image to fit inside original image.</label><br />";
    }

    /**
     * Checkbox for Backup Watermark
     */
    function watermark_backup_field()
    {
        $checked = $this->general_settings['backup'];
        $html = "<label><input value='1' name='" . $this->general_settings_key . "[backup]' type='checkbox' " . checked($checked, 1, false) . "/> Take backup of original images before applying watermark.</label><br />";
        $html .= '<p class="description" style="color:red;">After disabling this feature, you will not be able to erase watermarks.</p>';
        echo $html;
    }

    /**
     * Fontsize field for Text watermark
     */
    function watermark_text_fontsize_field()
    {
        ?>
        <input size="8" id="watermark-text-font-size" type="number" step="1" min="2" max="60"
               name="<?php echo $this->general_settings_key; ?>[watermark_text_fontsize]"
               value="<?php echo $this->general_settings['watermark_text_fontsize']; ?>" class="small-text"/>
    <?php
    }

    /**
     * Font selector for Text watermark
     */
    function watermark_text_font_field()
    {
        $selected = $this->general_settings['watermark_text_font'];
        echo '<select name="' . $this->general_settings_key . '[watermark_text_font]" >';
        $fontsDir = AWM_PATH . "/fonts";
        if (is_dir($fontsDir)) {
            if ($dh = opendir($fontsDir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' && $file != '..') {
                        $font = ucfirst(rtrim($file, '.ttf'));
                        echo '<option ' . selected($selected, $file) . ' style="font-family:' . $font . ' !important" value="' . $file . '">' . $font . '</option>';
                    }
                }
                closedir($dh);
            }
        }
        echo '<select>';
    }

    /**
     * Text watermark color picker
     */
    function watermark_color_field()
    {
        ?>
        <input size="8" id="watermark-text-color" type="text"
               name="<?php echo $this->general_settings_key; ?>[watermark_text_color]"
               value="<?php echo $this->general_settings['watermark_text_color']; ?>" class="color-field"/>
    <?php
    }

    /**
     * Tiled watermark checkbox
     */
    function watermark_tile_field()
    {
        $options = $this->general_settings;
        $checked = $options['tile_watermark'];
        echo "<label><input id='tiled_watermark' value='1' name='" . $this->general_settings_key . "[tile_watermark]' type='checkbox' " . checked($checked, 1, false) . "/> Tiled watermarks</label><br />";
    }

    /**
     * Watermark angle slider
     */
    function watermark_angle_field()
    {
        $options = get_option($this->general_settings_key);
        ?>
        <div id="watermark_angle_val" style="width:50px;display: inline-block;"><?php echo $options['angle'] ?>
            &#176;</div>
        <div id="angle_slider" style="width:50%;display: inline-block;"></div>
        <input id="watermark_angle" value='<?php echo $options['angle'] ?>'
               name='<?php echo $this->general_settings_key ?>[angle]' type='hidden'/><br/>
    <?php
    }

    /**
     * Margins fields
     */
    function watermark_margins_field()
    {
        $options = get_option($this->general_settings_key);
        if (isset($options['margin_leftright']) && isset($options['margin_topbottom'])) {
            $left_right = (int)$options['margin_leftright'];
            $top_bottom = (int)$options['margin_topbottom'];
        } else {
            $left_right = 0;
            $top_bottom = 0;
        }
        ?>
        <label for="margin_leftright" class="wcpiw-margins">Left & Right: <input
                name="<?php echo $this->general_settings_key ?>[margin_leftright]" type="number" step="1" min="0"
                max="200" id="margin_leftright" value="<?php echo $left_right ?>" class="small-text"></label>
        <br/>
        <label for="margin_topbottom" class="wcpiw-margins">Top & Bottom: <input
                name="<?php echo $this->general_settings_key ?>[margin_topbottom]" type="number" step="1" min="0"
                max="200" id="margin_topbottom" value="<?php echo $top_bottom ?>" class="small-text"></label>
    <?php
    }

    /**
     * Angle and Quality sliders
     */
    function admin_js()
    {
        $options = get_option($this->general_settings_key);
        ?>
        <script>
            jQuery(document).ready(function ($) {
                $("#quality_slider").slider("value", <?php echo $options['quality']; ?>);
                $("#watermark_quality").val(<?php echo $options['quality']; ?>);
                $("#angle_slider").slider("value", <?php echo $options['angle']; ?>);
                $("#watermark_angle").val(<?php echo $options['angle']; ?>);
            });
        </script>
    <?php
    }

    /**
     * Watermark position radio buttons
     * @param $size
     * @param $settings_key
     * @return string
     */
    function watermark_position_field($size, $settings_key)
    {
        if ($size == 'default_watermark')
            $checked = isset($this->general_settings['watermark_position'][$size]) ? $this->general_settings['watermark_position'][$size] : 10;
        else {
            $checked = isset($this->configuration['watermark_position'][$size]) ? $this->configuration['watermark_position'][$size] : $this->general_settings['watermark_position']['default_watermark'];
        }
        $html = "";
        $html .= "<div class='watermark_position_container' >";
        $html .= "<label class='top-left'><input title='Top left' value='1' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 1, false) . "/> </label>";
        $html .= "<label class='top-center'><input title='Top center' value='2' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 2, false) . "/> </label>";
        $html .= "<label class='top-right'><input title='Top right' value='3' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 3, false) . "/> </label>";
        $html .= "<label class='middle-left cleft'><input title='Middle left' value='4' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 4, false) . "/> </label>";
        $html .= "<label class='middle-center'><input title='Middle center' value='5' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 5, false) . "/> </label>";
        $html .= "<label class='middle-right'><input title='Middle right' value='6' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 6, false) . "/> </label>";
        $html .= "<label class='cleft bottom-left'><input title='Bottom left' value='7' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 7, false) . "/> </label>";
        $html .= "<label class='bottom-center'><input title='Bottom center' value='8' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 8, false) . "/> </label>";
        $html .= "<label class='bottom-right'><input title='Bottom right' value='9' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 9, false) . "/> </label>";
        $html .= "</div>";
        $html .= '<div>';
        $html .= "<label class='bottom-right'><input value='10' name='" . $settings_key . "[watermark_position][" . $size . "]' type='radio' " . checked($checked, 10, false) . "/>No Watermark </label>";
        $html .= '</div>';
        return $html;
    }

    /**
     * Preview Watermark Tab
     */
    function watermark_preview_section()
    {
        if (!empty($options['sizes']) && !empty($options['watermark_images'])) {
            foreach ($options['sizes'] as $size) {
                if ($options['watermark_images'][$size] != '') {
                    @unlink(AWM_PATH . '/images/preview_' . $size . '.jpg');
                }
            }
        }
        $watermarker = new AwesomeWaterMarker('preview');
        $watermarker->add_watermark('preview');
        $options = $this->configuration;
        if (!empty($options['sizes']) && !empty($options['watermark_images'])) {
            echo '<div class="metabox-holder">';
            foreach ($options['sizes'] as $size) :
                ?>
                <div id="dashboard_right_now" class="postbox awm-postbox " style="">
                    <h3 class="hndle">
                        <span><?php echo ucwords(str_replace(array('_', '-'), array(' ', ' '), $size)); ?></span></h3>

                    <div class="inside">
                        <div class="center">
                            <img src="<?php echo AWM_URL; ?>images/preview_<?php echo $size ?>.jpg"/>
                        </div>
                    </div>
                </div>
            <?php
            endforeach;
            echo '</div>';
        } else
            echo 'Please select some images sizes and choose watermark.';
    }

    /**
     * Help Tab
     */
    function help_section()
    {

        $html = "<h3>Features Explained</h3>";
        $html .= "<h4>Target Image Sizes</h4>";
        $html .= "WordPress has different image sizes, and more can be added via plugins. For example WooCommerece adds its own sizes that are used for products. You have to choose the sizes on which you want to add watermark.";
        $html .= "<h4>Tiled Watermarks</h4>";
        $html .= "If you enabled this feature, entire image will be covered with watermark tiles";
        $html .= "<h4>Image Quality</h4>";
        $html .= "You can control image quality, 1 being the lowest and 100 being the best quality";
        $html .= "<h4>Angle</h4>";
        $html .= "Watermark can be rotated at available angles";
        $html .= "<h4>Watermark Scaling</h4>";
        $html .= "You may add big watermark images and text without worrying about the dimensions, if the watermark is wider than the image it will be scale to fit on the image.";
        $html .= "<h4>Backup</h4>";
        $html .= "This is very important. When enabled, the plugin will take backup of original images before applying watermark to them. You can remove watermark from images or add a different watermark at any time. But if this is not enabled you will not be able to remove a watermark once applied.";
        $html .= "<h4>Text Settings</h4>";
        $html .= "You can specify font-family, font-size and color for text watermark.";

        $html .= "<h3>Frequently Asked Questions</h3>";
        $html .= "<h4>How to add more fonts?</h4>";
        $html .= "Adding more fonts is very easy, just copy the .ttf file to fonts folder and you will see the new font in fonts dropdown list.";
        $html .= "<h4>What are supported image formats?</h4>";
        $html .= "Watermarks will be applied only to JPEG and PNG images.";
        $html .= "<h4>How to apply watermark to existing images?</h4>";
        $html .= "<ol>"
            . "<li>Specify a watermark either Text or an Image</li>"
            . "<li>Install <a target='_blank' href='https://wordpress.org/plugins/regenerate-thumbnails/'>Regenerate Thumbnails</a> plugin</li>"
            . "<li>Go to Tools -> Regenerate Thumbnails and click Regenerate All Thumbnails button. You may apply watermark to specific images only, for that purpose go to Media -> Library, choose images and choose Regenerate Thumbnails from Bulk Actions</li>";
        $html .= "</ol>";
        $html .= "<h4>How to remove watermarks from images?</h4>";
        $html .= "<i>Watermark can be removed only if the Backup was enabled.</i>";
        $html .= "<ol>"
            . "<li>Click No Watermark radio button.</li>"
            . "<li>Install <a target='_blank' href='https://wordpress.org/plugins/regenerate-thumbnails/'>Regenerate Thumbnails</a> plugin</li>"
            . "<li>Go to Tools -> Regenerate Thumbnails and click Regenerate All Thumbnails button. You may remove watermark from specific images only, for that purpose go to Media -> Library, choose images and choose Regenerate Thumbnails from Bulk Actions</li>";
        $html .= "</ol>";
        $html .= "<h4></h4>";
        $html .= "";
        echo $html;
    }

    /**
     * Creating plugin settings menu under Settings
     */
    function admin_menu()
    {
        add_options_page('Awesome Watermarks', 'Watermarks', 'manage_options', $this->settings_page_key, array($this, 'options_callback'));
    }

    /**
     * Adding scripts to admin head
     */
    function admin_head_script()
    {
        wp_register_style('wcawm_wp_admin_css', AWM_URL . '/css/style.css', false, '1.0.0');
        wp_enqueue_style('wcawm_wp_admin_css');
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Adding required JS and CSS scripts
     */
    function enqueue_scripts()
    {
        if ('settings_page_awm_options' == get_current_screen()->id) {
            add_action('admin_head', array(&$this, 'admin_js'));
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-slider');
            wp_enqueue_script('thickbox');
            wp_enqueue_style('thickbox');
            wp_enqueue_style('jquery-ui-core');
            wp_enqueue_style("jquery-ui-css", "http://code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css");
            wp_enqueue_script('media-upload');
            wp_enqueue_script('wcpiwm-upload', AWM_URL . '/js/scripts.js', array('wp-color-picker'));
        }
    }

    /**
     * Validation for configurations data
     * @param $input
     * @return mixed validated value
     */
    function awm_options_validate($input)
    {
        if (isset($input['watermark_images']) && $input['watermark_images'] != '') {
            foreach ($input['watermark_images'] as $size => $image) {

                $test = getimagesize($image);
                if ($test['mime'] != 'image/png' && $test['mime'] != 'image/jpeg')
                    $input['watermark_images'][$size] = '';
            }
        }
        if (isset($input['margin_leftright']) && isset($input['margin_topbottom'])) {
            if ((int)$input['margin_leftright'] < 0 || (int)$input['margin_leftright'] > 200)
                $input['margin_leftright'] = '0';
            if ((int)$input['margin_topbottom'] < 0 || (int)$input['margin_topbottom'] > 200)
                $input['margin_topbottom'] = '0';
        }

        return $input;
    }

    /**
     * Customize media thickbox used for uploading watermark images
     */
    function customize_media_thickbox()
    {
        global $pagenow;
        if ('media-upload.php' == $pagenow || 'async-upload.php' == $pagenow) {
            add_filter('gettext', array($this, 'replace_thickbox_text'), 1, 3);
        }
    }

    /**
     * @param $translated_text
     * @param $text
     * @param $domain
     * @return string
     */
    function replace_thickbox_text($translated_text, $text, $domain)
    {
        if ('Insert into Post' == $text) {
            $referer = strpos(wp_get_referer(), 'watermark_options_page');
            if ($referer != '') {
                return 'Use image as watermark';
            }
        }
        return $translated_text;
    }

    /**
     * Show action links on the plugin screen.
     *
     * @access    public
     * @param    mixed $links Plugin Action links
     * @return    array
     */
    function plugin_action_links($links)
    {
        $action_links = array(
            '<a href="' . admin_url('options-general.php?page=awm_options') . '">Settings</a>',
        );
        return array_merge($action_links, $links);
    }
}

