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

window.onload = function() {
	document.getElementById("up_form").onsubmit = handle_onsubmit;
	console.log("Hello!");
}
