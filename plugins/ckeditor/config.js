/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	//config.filebrowserBrowseUrl = '/plugins/ckeditor/filemanager/?Connector=https://dev.finalmedia.nl/ckeditor/filemanager/connectors/php/filemanager.php';
	
	   config.extraPlugins = 'youtube,format';
	   config.filebrowserBrowseUrl = '/plugins/kcfinder-2.51/browse.php?type=files';
	   config.filebrowserImageBrowseUrl = '/plugins/kcfinder-2.51/browse.php?type=images';
	   config.filebrowserFlashBrowseUrl = '/plugins/kcfinder-2.51//browse.php?type=flash';
	   config.filebrowserUploadUrl = '/plugins/kcfinder-2.51/upload.php?type=files';
	   config.filebrowserImageUploadUrl = '/plugins/kcfinder-2.51/upload.php?type=images';
	   config.filebrowserFlashUploadUrl = '/plugins/kcfinder-2.51//upload.php?type=flash';
		
	
	config.format_tags = 'h1;h2;h3;h4;p';
	config.format_h4 = { element: 'h4', attributes: { 'class': 'gray-dark' } };
	config.toolbar_Basic = [
	    [ 'Bold', 'Italic','Underline','Outdent', 'Indent',  'Copy', 'Cut','Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ]


	];
	
	config.toolbar_Advanced = [
		    [ 'Bold', 'Italic','Underline', 'Outdent', 'Indent', 'Copy', 'Cut','Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ],
		    [ 'Image', 'youtube', 'Link', 'Table',  'SpecialChar','Format' ], 
		    ['Maximize']
	
	
		];
	
	
	config.toolbar = 'Advanced';
	
	config.allowedContent = true;
	
};

