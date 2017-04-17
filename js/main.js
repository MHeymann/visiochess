/* Global variables */
pgnHashes = [];

function handle_onsubmit(e) {
	var fileSubmitter = document.getElementById("user_db");
	console.log("submitter: " + fileSubmitter.files.length);
	if (('files' in fileSubmitter) && (fileSubmitter.files.length == 1)) {
		var file = fileSubmitter.files[0];
		var reader = new FileReader();

		/* readers load files asyncronously */
		reader.onload = function(e) {
			var text = reader.result;
			var i = pgnHashes.length;
			pgnHashes[i] = hex_sha256(text);
			console.log("New file with hash: " + pgnHashes[i]);
		}

		reader.readAsText(file);

	} else {
		console.log("Something is wrong with the file input object...");
		if ('files' in fileSubmitter && (fileSubmitter.files.length <= 0)) {
			console.log("No file selected!");
		}
		if ('files' in fileSubmitter && (fileSubmitter.files.length > 1)) {
			console.log("Please select exactly one file");
		}
	}
	console.log("clicked submit\n");
}

function getFormData($form){
    var unindexed_array = $form.serializeArray();
    var indexed_array = {};

    $.map(unindexed_array, function(n, i){
        indexed_array[n['name']] = n['value'];
    });

    return indexed_array;
}

function handle_filter_submit(event) {
	event.preventDefault();

	var $form = $('#filter_form');
	send_url = $form.attr("action");
	console.log("action url: ", send_url);
	var filters = getFormData($form);

	console.log(
		'Sending these filters:\n',
		filters
	);

	alert('Sending filters, look at console');

	$.ajax({
		// this needs to be changed such that it fetces this from the config file
		url: send_url,
		type: 'post',
		data: filters,
		dataType: 'html',
		success: function(response) {
			console.log(response);
			// render results
		},
		error: function (xhr, textStatus, errorMessage) {
      console.log(errorMessage);
    }
	});
}

window.onload = function() {
	$("#up_form").submit(handle_onsubmit);
	$('#filter_form').submit(handle_filter_submit);
	console.log("Hello!");
}
