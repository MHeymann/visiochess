/* Global variables */
var pgnHashes = [];

function handle_pgn_submit(e) {
	e.preventDefault();
	var fileSubmitter = document.getElementById("user_db_uploader");

	if (('files' in fileSubmitter) && (fileSubmitter.files.length == 1)) {
		var file = fileSubmitter.files[0];
		var reader = new FileReader();
		var $form = $('#up_form');
		send_url = $form.attr("action");
		console.log("action url: ", send_url);

		/* readers load files asyncronously */
		reader.onload = function(e) {
			var text = reader.result;
			var i = pgnHashes.length;
			pgnHashes[i] = [file.name, hex_sha256(text), file];
			console.log("New file " + pgnHashes[i][0] + " with hash: " + 
					pgnHashes[i][1]);
			var db_selector = document.getElementById("db_selector");
			var option = document.createElement("option");
			option.text = file.name; // set name a clickable display
			option.value = pgnHashes[i][1]; //set hash as identifiable val
			db_selector.add(option);
		}

		/* 
		 * Tell the reader this is a textfile. It will load the file into
		 * memory, which will then call the onload function fust defined
		 * above.
		 */
		reader.readAsText(file);

		submit_file(file, send_url);

	} else {
		console.log("Something is wrong with the file input object...");
		if ('files' in fileSubmitter && (fileSubmitter.files.length <= 0)) {
			console.log("No file selected!");
		}
		if ('files' in fileSubmitter && (fileSubmitter.files.length > 1)) {
			console.log("Please select exactly one file");
		}
	}
}

function submit_file(file, send_url) {

	var form_data = new FormData();
	form_data.append("user_db_uploader", file)

	$.ajax({
		url: send_url,
		type: 'post',
		data: form_data,
		dataType: 'html',
		/* make synchronous so that once reupload of filters can be done
		 * again */
		async: false,
		/* these three are necessary for file uploads with ajax */
		cache: false,
		contentType: false,
		processData: false,

		success: function(response) {
			// render results
			$("#temp_results").append(response);
		},
		error: function (xhr, textStatus, errorMessage) {
			console.log(errorMessage);
		}
	});

}

function getFormData($form) {
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
		url: send_url,
		type: 'post',
		data: filters,
		dataType: 'html',
		success: function(response) {
			//console.log(response);
			// render results
			$("#temp_results").html(response);
		},
		error: function (xhr, textStatus, errorMessage) {
			console.log(errorMessage);
		}
	});
}

function get_file_from_hash(hash) {
	var i;
	for (i = 0; i < pgnHashes.length; i++) {
		if (pgnHashes[i][1] == hash) {
			break;
		}
	}
	if (i == pgnHashes.length) {
		return null;
	} else {
		return pgnHashes[i][2];
	}
}

window.onload = function() {
	/* TODO 
	 * Add function that, when files are selected, checks them for size
	 * constraints.
	 */
	$("#up_form").submit(handle_pgn_submit);
	/* TODO:
	 * in filter_submitions, look at which database is currently selected
	 * in the selector form, and send that data to the server along with
	 * the filters.  
	 */
	$('#filter_form').submit(handle_filter_submit);

	$("#test_reupload").click(function(e) {
		var i = pgnHashes.length;
		if (i > 0) {
			$.ajax({
				url: "php/reload_pgn_db.php",
				type: 'post',
				data: {
					"hash": pgnHashes[i-1][1]
				},
				dataType: "html",
				success: function(response) {
					$("#temp_results").append("<p>--------reload-----------</p>");
					$("#temp_results").append(response);
				},
				error: function (xhr, textStatus, errorMessage) {
					console.log(errorMessage);
				}

			});
		}
	});
}
