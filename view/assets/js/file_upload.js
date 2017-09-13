
/*
 * jQuery File Upload Plugin JS Example 8.9.1
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/* global $, window */

$(function () {
    'use strict';

    if ($('#fileupload').html() != undefined)
    {
        // Initialize the jQuery File Upload widget:
        $('#fileupload').fileupload({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: '/file-upload',
            replaceFileInput: false
        });

        // Load existing files:
        $('#fileupload').addClass('fileupload-processing');
        $.ajax({
            // Uncomment the following to send cross-domain cookies:
            //xhrFields: {withCredentials: true},
            url: '/file-upload',
            dataType: 'json',
            context: $('#fileupload')[0]
        }).always(function () {
            $(this).removeClass('fileupload-processing');
        }).done(function (result) {
            $(this).fileupload('option', 'done')
                .call(this, $.Event('done'), {result: result});
        });
    }

    var elm, type;
    var url = '/file-upload/index/document/';


	//return;
	//for (var key in idsFileUpload)
	$('[id^="fileupload-"]').each(function() {
        //elm = $('#' + idsFileUpload[key]);
        elm = $(this);
        if (elm.html() != undefined) {
	        type = elm.attr('id').replace('fileupload-', '');

            // Initialize the jQuery File Upload widget:
            elm.fileupload({
                // Uncomment the following to send cross-domain cookies:
                //xhrFields: {withCredentials: true},
                url: url + type,
                replaceFileInput: false,
                downloadTemplateId: 'template-download-' + type
            });

            // Load existing files:
            elm.addClass('fileupload-processing');
            $.ajax({
                // Uncomment the following to send cross-domain cookies:
                //xhrFields: {withCredentials: true},
                url: url + type,
                dataType: 'json',
                context: elm[0]
            }).always(function () {
                $(this).removeClass('fileupload-processing');
            }).done(function (result) {
                $(this).fileupload('option', 'done')
                    .call(this, $.Event('done'), {result: result});
            });
        }
    });
    // END page order-sale/documents

});