$(document).ready(function() {
 
	var errorHandler = function(event, id, fileName, reason, xhr) {
		qq.log("id: " + id + ", fileName: " + fileName + ", reason: " + reason);
	};
	
	
	var aanlevering = $('#upload-aanlevering').uploadFiles('Aanlevering', errorHandler);
	var mutatie = $('#upload-mutatie').uploadFiles('Mutatie', errorHandler);
	
	
	
	$('#triggerUpload-aanlevering').click(function() {
		aanlevering.fineUploader('uploadStoredFiles');
	}).css('cursor', 'pointer');
	
	$('#triggerUpload-mutatie').click(function() {
		
		mutatie.fineUploader('uploadStoredFiles');
	}).css('cursor', 'pointer');
	

	
});