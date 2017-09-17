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


	// page order-sale/documents
	var idsFileUpload = [
		'fileupload-contract',
		'fileupload-invoice',
		'fileupload-paymentDocuments',
		'fileupload-fizOk',
		'fileupload-yurOk',
		'fileupload-semazInvoice',
		'fileupload-semazVirazhInvoice',
		'fileupload-ttnNActPp',
		'fileupload-act',
		'fileupload-cashVoucher',
		'fileupload-zReport',
		'fileupload-invoices',
		'fileupload-salesInvoice',
		'fileupload-powerOfAttorney',
		'fileupload-identityCard',
		'fileupload-contractOfSale',
		'fileupload-ttn',
		'fileupload-damageReport',
		'fileupload-pdk',
        'fileupload-photos'
	];

	var idTmp, typeTmp;
	var url = '/file-upload/index/document/';

	for (var key in idsFileUpload)
	{
		idTmp = $('#' + idsFileUpload[key]);

		if (idTmp.html() != undefined)
		{
			typeTmp = idsFileUpload[key].replace("fileupload-", "");

			// Initialize the jQuery File Upload widget:
			idTmp.fileupload({
				// Uncomment the following to send cross-domain cookies:
				//xhrFields: {withCredentials: true},
				url: url + typeTmp,
				replaceFileInput: false,
				downloadTemplateId: 'template-download-' + typeTmp
			});

			// Load existing files:
			idTmp.addClass('fileupload-processing');
			$.ajax({
				// Uncomment the following to send cross-domain cookies:
				//xhrFields: {withCredentials: true},
				url: url + typeTmp,
				dataType: 'json',
				context: idTmp[0]
			}).always(function () {
					$(this).removeClass('fileupload-processing');
				}).done(function (result) {
					$(this).fileupload('option', 'done')
						.call(this, $.Event('done'), {result: result});
				});
		}
	}
	// END page order-sale/documents

});
