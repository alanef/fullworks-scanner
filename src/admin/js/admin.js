/*
 *  @copyright (c) 2023.
 *  @author     Alan Fuller (support@fullworksplugins.com)
 *  @licence    GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 *  @link       https://fullworksplugins.com
 *
 *  This file is part of a Fullworks' Plugin.
 *
 *  This WordPress plugin  is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This WordPress plugin  is  distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  Any copying of any part of this code not in compliance with the licence terms is strictly prohibited.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with the plugin.  https://www.gnu.org/licenses/gpl-3.0.en.html
 */

(function ($) {
    'use strict';

    $(function () {
        $('.delete-file').on('click', function () {
            return confirm('Are you sure want to do delete this file? Have you made a backup?');
        });
    });

    $.fn.extend({
        toggleText: function(a, b){
            return this.text(this.text() == b ? a : b);
        }
    });

    $('#waf-button').click(function () {
        if ($('#waf-button').hasClass('button-primary')) {
           var change = 'enable';
        } else {
            var change = 'disable';
        }
        $.ajax({
            url: ajaxurl,
            method: 'post',
            data: {
                action: 'enable_disable_waf',
                security: FullworksSecurityWAF.nonce,
                change: change,
            },
            success: function (response) {
                window.location.reload();
            }
        });
    });


    var mediaUploader;
    $('#fw_logo_image_button').click(function (e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            }, multiple: false
        });
        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#background_image').val(attachment.url);
        });
        mediaUploader.open();
    });

})(jQuery);
