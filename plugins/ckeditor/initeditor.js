function createEditor( editorID, languageCode ) {
	if ( editor )
		editor.destroy();

	// Replace the <textarea id="editor"> with an CKEditor
	// instance, using default configurations.
	return CKEDITOR.replace( editorID, {
		language: languageCode,

		on: {
			instanceReady: function() {
				// Wait for the editor to be ready to set
				// the language combo.
				
			}
		}
	});
}