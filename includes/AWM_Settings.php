<?php

/**
 * Class AWM_Settings
 */
class AWM_Settings
{
    /** general settings options key
     * @var string
     */
    protected $general_settings_key = 'awm_general_settings';

    /**
     * Plugin settings page key
     * @var string
     */
    protected $settings_page_key = 'awm_options';
    /**
     * Variable to store General Settings
     * @var array
     */
    protected $general_settings = array();

    /**
     * Variable to store all configurations
     * @var array
     */
    protected $configuration = array();
    /**
     * Page slug for preview page
     * @var string
     */
    protected $preview_section_slug = 'awm_preview';
    /**
     * page slug for help page
     * @var string
     */
    protected $help_section_slug = 'awm_help';

    /**
     * Settings constructor
     */
    function __construct()
    {
        $this->general_settings = (array)get_option($this->general_settings_key);
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
                'full size' => '© Awesome Watermark',
            ),
            'watermark_position' => array(
                'full size' => '5',
            ),
            'quality' => '90',
            'angle' => '45',
            'margin_leftright' => '0',
            'margin_topbottom' => '0',
            'tile_watermark' => 0,
            'backup' => 0,
            'scale_watermark' => 0,
            'watermark_text_font' => 'arial.ttf',
            'watermark_text_fontsize' => '12',
            'watermark_text_color' => '#eeee22',
        );
        $this->general_settings = array_merge($defaults, $this->general_settings);
        $this->configuration = $this->general_settings;
    }
}

?>