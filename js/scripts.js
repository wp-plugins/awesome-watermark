var wc_piwm_thumb_id;

jQuery(document).ready(function ($) {
    $(function () {
        $('.color-field').wpColorPicker();
    });

    window.send_to_editor = function (html) {

        var image_url = jQuery('img', html).attr('src');
        if (wc_piwm_thumb_id)
        {
            jQuery('#image_url-' + wc_piwm_thumb_id).val(image_url);
            tb_remove();
        } else {
            window.original_send_to_editor(html);
        }
    }

    $('.upload_image_button').live('click',function(e){
        e.preventDefault();
        var id = jQuery(this).attr("id")
        var btn = id.split("-");
        wc_piwm_thumb_id = btn[1];
        console.log(wc_piwm_thumb_id);
        $('#type-image-' + wc_piwm_thumb_id).attr('checked', 'checked');
        tb_show('Upload a watermark image', 'media-upload.php?referer=watermark_options_page&type=image&TB_iframe=true&post_id=0', false);
        return false;
    });

    $('.watermark-text').live('keyup',function(e){
        var id = jQuery(this).attr("id").split("-");
        console.log(id[2]);
        $('#type-text-' + id[2]).attr('checked', 'checked');
    });

    $('.regular-text').live('keyup',function(e){
        var id = jQuery(this).attr("id").split("-");
        console.log(id[1]);
        $('#type-image-' + id[1]).attr('checked', 'checked');
    });

    $("#quality_slider").slider({
        range: "min",
        value: 90, // default quality
        min: 1,
        max: 100,
        slide: function (event, ui) {
            $("#watermark_quality").val(ui.value);
            $("#watermark_quality_val").html(ui.value + '%');
        }
    });
    $("#angle_slider").slider({
        step: 45,
        range: "min",
        value: 0, // No rotation by default
        min: 0,
        max: 360,
        slide: function (event, ui) {
            $("#watermark_angle").val(ui.value);
            $("#watermark_angle_val").html(ui.value + '&#176;');
        }
    });

    $("#image_sizes input[type='checkbox']").change(function(e){
        e.preventDefault();
        var size_title = $(this).attr("value").replace('_', ' ').replace('-', ' ');
        var size = $(this).attr("value");
        var size_id= $(this).attr("value").replace(' ', '_');
        if($(this).is(":checked")) {
            var html = '<div style="" class="postbox awm-postbox " id="watermark-'+size_id+'">'
                + '<h3 class="hndle"><span>'+size_title+'</span></h3>'
                + '<div class="inside">'
                + '<div class="main">'
                + '<input type="radio" value="image" name="awm_general_settings[watermark_type]['+size+']" id="type-image-'+size_id+'" title="Image Watermark">'
                + '<input type="text" class="regular-text" value="" name="awm_general_settings[watermark_images]['+size+']" id="image_url-'+size_id+'" placeholder="Enter watermark image URL here">'
                + '<input type="button" value="Select Image" class="button upload_image_button" id="watermark-'+size_id+'">'
                + '<div class="clear">OR</div>'
                + '<label for="watermark-text-'+size+'"> <input type="radio" checked="checked" value="text" name="awm_general_settings[watermark_type]['+size+']" title="Text Watermark" id="type-text-'+size_id+'">  </label>'
                + '<input maxlength="10" type="text" class="regular-text watermark-text" value="" name="awm_general_settings[watermark_text]['+size+']" placeholder="Enter watermark text here" id="watermark-text-'+size_id+'">'
                + '<table style="width:100%">'
                + '<tbody><tr>'
                + '<td align="center"><div class="watermark_position_container"><label class="top-left"><input type="radio" name="awm_general_settings[watermark_position]['+size+']" value="1" title="Top left"> </label><label class="top-center"><input type="radio" name="awm_general_settings[watermark_position]['+size+']" value="2" title="Top center"> </label><label class="top-right"><input type="radio" name="awm_general_settings[watermark_position]['+size+']" value="3" title="Top right"> </label><label class="middle-left cleft"><input type="radio" name="awm_general_settings[watermark_position]['+size+']" value="4" title="Middle left"> </label><label class="middle-center"><input type="radio" name="awm_general_settings[watermark_position]['+size+']" value="5" title="Middle center"> </label><label class="middle-right"><input type="radio" name="awm_general_settings[watermark_position]['+size+']" value="6" title="Middle right"> </label><label class="cleft bottom-left"><input type="radio" name="awm_general_settings[watermark_position]['+size+']" value="7" title="Bottom left"> </label><label class="bottom-center"><input type="radio" name="awm_general_settings[watermark_position]['+size+']" value="8" title="Bottom center"> </label><label class="bottom-right"><input type="radio"  name="awm_general_settings[watermark_position]['+size+']" value="9" title="Bottom right"> </label></div><div><label class="bottom-right"><input type="radio" name="awm_general_settings[watermark_position]['+size+']" value="10" checked="checked">No Watermark </label></div></td>'
                + '</tr>'
                + '</tbody></table>'
                + '</div>'
                + '</div>'
                + '</div>';
            $('#watermarks').append(html);
        }else
        {
            $('#watermark-'+size_id).remove();
        }
    });

});





