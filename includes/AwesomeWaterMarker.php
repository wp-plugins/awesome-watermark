<?php

require_once 'AWM_Settings.php';

/**
 * Class AwesomeWaterMarker
 */
class AwesomeWaterMarker extends AWM_Settings
{

    /**
     * Variable to differentiate preview and actual watermark
     * @var string
     */
    private $mode;
    /**
     * Base image path that will be used in previews
     * @var string
     */
    private $preview_image_path;

    /**
     * Constructor function
     * @param string $mode
     */
    public function __construct($mode)
    {
        $this->mode = $mode;
        $this->preview_image_path = AWM_PATH . "/images/preview.jpg";
        parent::__construct();
    }


    /**
     * Add watermark to images
     * @param mixed $meta Image meta data
     *
     * @return mixed $meta Image meta data
     */
    function add_watermark($meta)
    {
        $referer = strpos(wp_get_referer(), 'watermark_options_page');
        if ($referer != '') {
            return $meta;
        }

        if (!isset($this->configuration['sizes']))
            return $meta;

        foreach ($this->configuration['sizes'] as $size) {// Apply watermark to each enabled size
            if (isset($meta['sizes'][$size]) || $size == 'full size' || $this->mode == 'preview') { // Check if given size exist
                $watermark_image = $this->configuration['watermark_images'][$size];
                $watermark_type = $this->configuration['watermark_type'][$size];

                if ($watermark_type == 'image' && !empty($watermark_image)) {
                    $pos = strpos($watermark_image, 'wp-content/');
                    $watermark_path = ABSPATH . substr($watermark_image, $pos);
                    $watermark_info = getimagesize($watermark_path);
                    if ($watermark_info['mime'] == 'image/png') {
                        $stamp = imagecreatefrompng($watermark_path);
                    } elseif ($watermark_info['mime'] == 'image/jpeg') {
                        $stamp = imagecreatefromjpeg($watermark_path);
                    }
                    list($watermark_width, $watermark_height) = getimagesize($watermark_path);
                } else {
                    // creating text watermark
                    $fontFile = AWM_PATH . "/fonts/" . $this->configuration['watermark_text_font'];
                    $fontSize = $this->configuration['watermark_text_fontsize'];
                    $text = $this->configuration['watermark_text'][$size];
                    $box = imagettfbbox($fontSize, 0, $fontFile, $text);
                    $minX = min(array($box[0], $box[2], $box[4], $box[6]));
                    $maxX = max(array($box[0], $box[2], $box[4], $box[6]));
                    $minY = min(array($box[1], $box[3], $box[5], $box[7]));
                    $maxY = max(array($box[1], $box[3], $box[5], $box[7]));
                    $watermark_width = ($maxX - $minX) + 5;
                    $watermark_height = ($maxY - $minY) + 5;
                    $left = abs($minX) + $watermark_width;
                    $top = abs($minY) + $watermark_height;
                    $stamp = @imagecreatetruecolor($watermark_width, $watermark_height);
                    imagesavealpha($stamp, true);
                    imagealphablending($stamp, false);
                    $white = imagecolorallocatealpha($stamp, 255, 255, 255, 127);
                    imagefill($stamp, 0, 0, $white);
                    $textRGB = $this->convertHexToRGB($this->configuration['watermark_text_color']);
                    $textColor = imagecolorallocate($stamp, $textRGB['R'], $textRGB['G'], $textRGB['B']);
                    imagettftext($stamp, $fontSize, 0, 2, $fontSize + 2, $textColor, $fontFile, $text);
                }

                if (empty($stamp))
                    return $meta;

                imagealphablending($stamp, false);
                imagesavealpha($stamp, true);

                if ($this->mode != 'preview') {
                    $file = wp_upload_dir();
                    if ($size == 'full size') {
                        $base_dir = trailingslashit($file['basedir']);
                        $sub_dir = trailingslashit(substr($meta['file'], 0, strrpos($meta['file'], '/')));
                        $pos = strrpos($meta['file'], '/');
                        $img_name = substr($meta['file'], $pos + 1);
                        $file = $base_dir . $sub_dir . $img_name;
                    } else {
                        $base_dir = trailingslashit($file['basedir']);
                        $sub_dir = trailingslashit(substr($meta['file'], 0, strrpos($meta['file'], '/')));
                        $img_name = $meta['sizes'][$size]['file'];
                        $file = $base_dir . $sub_dir . $img_name;
                    }
                } else
                    $file = $this->preview_image_path;

                $src = $file;
                if ($this->mode != 'preview') {
                    $backup_image = $base_dir . $sub_dir . '_backup_' . $img_name;
                    if (file_exists($backup_image))
                        $src = $backup_image;
                }

                $image_info = getimagesize($file);
                if ($image_info['mime'] == 'image/png') {
                    $im = imagecreatefrompng($src);
                } elseif ($image_info['mime'] == 'image/jpeg') {
                    $im = imagecreatefromjpeg($src);
                }

                if (empty($im))
                    return $meta;

                imagealphablending($im, true);
                imagesavealpha($im, true);

                $watermark_position = $this->configuration['watermark_position'][$size];
                if ($watermark_position != '10') {
                    if ($this->mode != 'preview' && $this->configuration['backup'] && !file_exists($backup_image)) {
                        copy($file, $backup_image);
                    }
                    list($image_width, $image_height) = getimagesize($file);
                    // Scale watermark if scaling is enabled and watermark is wider than target image
                    if ($watermark_width > $image_width && $this->configuration['scale_watermark'] == 1) {
                        $stamp_backup = $stamp;
                        $width = $image_width / 2;
                        $height = $this->resizeHeightByWidth($watermark_height, $watermark_width, $width);
                        if ($height > 0) {
                            $stamp = imagecreatetruecolor($width, $height);
                            imagealphablending($stamp, false);
                            imagesavealpha($stamp, true);
                            imagecopyresampled($stamp, $stamp_backup, 0, 0, 0, 0, $width, $height, $watermark_width, $watermark_height);
                            $black = imagecolorallocate($stamp, 0, 0, 0);
                            imagecolortransparent($stamp, $black);
                        }
                    }

                    // Rotate watermark on specified angle
                    $angle = empty($this->configuration['angle']) ? 0 : $this->configuration['angle'];
                    if ($angle > 0)
                        $stamp = imagerotate($stamp, $angle, imageColorAllocateAlpha($stamp, 0, 0, 0, 127));

                    // Set the margins for the stamp and get the height/width of the stamp image
                    $margin_leftRight = (int)$this->configuration['margin_leftright'];
                    $margin_topBottom = (int)$this->configuration['margin_topbottom'];
                    $stampw = imagesx($stamp);
                    $stamph = imagesy($stamp);
                    $mainw = imagesx($im);
                    $mainh = imagesy($im);

                    switch ($watermark_position) {
                        case '1':
                            $posx = $margin_leftRight;
                            $posy = $margin_topBottom;
                            break;
                        case '2':
                            $posx = ($mainw - $stampw) / 2;
                            $posy = $margin_topBottom;
                            break;
                        case '3':
                            $posx = $mainw - $stampw - $margin_leftRight;
                            $posy = $margin_topBottom;
                            break;
                        case '4':
                            $posx = $margin_leftRight;
                            $posy = ($mainh - $stamph) / 2;
                            break;
                        case '5':
                            $posx = ($mainw - $stampw) / 2;
                            $posy = ($mainh - $stamph) / 2;
                            break;
                        case '6':
                            $posx = $mainw - $stampw - $margin_leftRight;
                            $posy = ($mainh - $stamph) / 2;
                            break;
                        case '7':
                            $posx = $margin_leftRight;
                            $posy = $mainh - $stamph - $margin_topBottom;
                            break;
                        case '8':
                            $posx = ($mainw - $stampw) / 2;
                            $posy = $mainh - $stamph - $margin_topBottom;
                            break;
                        case '9':
                            $posx = $mainw - $stampw - $margin_leftRight;
                            $posy = $mainh - $stamph - $margin_topBottom;
                            break;

                        default:
                    }

                    // If enabled then create a tiled watermark
                    if ($this->configuration['tile_watermark']) {
                        // creating a bigger canvas by adding margins
                        $maxWidth = imagesx($stamp) + $margin_leftRight;
                        $maxHeight = imagesy($stamp) + $margin_topBottom;
                        $canvas = imagecreatetruecolor($maxWidth, $maxHeight);
                        imagealphablending($canvas, false);
                        $background = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
                        imagefilledrectangle($canvas, 0, 0, $maxWidth, $maxHeight, $background);
                        imagealphablending($canvas, true);
                        //copy watermark to the canvas
                        imagecopyresampled($canvas, $stamp, 0, 0, 0, 0, imagesx($stamp), imagesy($stamp), imagesx($stamp), imagesy($stamp));
                        imagealphablending($canvas, true);
                        imagesavealpha($canvas, true);
                        // now tile the canvas on original image
                        imagesettile($im, $canvas);
                        imagefilledrectangle($im, 0, 0, $image_width, $image_height, IMG_COLOR_TILED);
                    } else
                        imagecopy($im, $stamp, $posx, $posy, 0, 0, imagesx($stamp), imagesy($stamp));

                    if ($this->mode == 'preview') {
                        $file = str_replace('preview.jpg', 'preview_' . $size . '.jpg', $file);
                        @unlink($file);
                    }

                    $quality = empty($this->configuration['quality']) ? 90 : $this->configuration['quality'];

                    $pathinfo = pathinfo($file);
                    if ($pathinfo['extension'] == 'png') {
                        $quality = abs($quality);
                        imagepng($im, $file, $quality[0]);
                    } else
                        imagejpeg($im, $file, $quality);
                    imagedestroy($im);
                    imagedestroy($stamp);
                } else {
                    if ($this->mode != 'preview') {
                        if (file_exists($backup_image)) {
                            $original = str_replace('_backup_', '', $backup_image);
                            rename($backup_image, $original);
                        }
                    } else {
                        $file = str_replace('preview.jpg', 'preview_' . $size . '.jpg', $file);
                        @unlink($file);
                        copy($this->preview_image_path, $file);
                    }
                }
            }
        }
        return $meta;
    }

    /**
     * Resize watermark height by its width
     * @param $origHeight Original Watermark height
     * @param $origWidth Original Watermark width
     * @param $newWidth New width
     *
     * @return float Height of watermark
     */
    private function resizeHeightByWidth($origHeight, $origWidth, $newWidth)
    {
        return floor(($origHeight / $origWidth) * $newWidth);
    }

    /**
     * Converts Hex Color to RGB color
     * @param string $hex HEX Color code
     *
     * @return array RGB color array
     */
    private static function convertHexToRGB($hex)
    {
        $hex = ltrim($hex, '#');
        return array(
            'R' => (int)base_convert(substr($hex, 0, 2), 16, 10),
            'G' => (int)base_convert(substr($hex, 2, 2), 16, 10),
            'B' => (int)base_convert(substr($hex, 4, 2), 16, 10),
        );
    }
}
