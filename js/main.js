/* global $ */
/* global hex_sha256 */
/* global send_url */
/* global config */
/* global draw */

/* Global variables */
var pgnHashes = {};
var mainJSON;

/**
 * Set up the necessary parameters and event handlers as soon as the html
 * form is loaded.
 */
window.onload = function() {
	getConfigSettings();
	add_max_year_attr();

	$("#pgn_up_form").submit(handle_pgn_submit);
	$('#filter_form').submit(handle_filter_submit);
	$('#clear_filters').click(handle_filter_clear);
	$('div[name=eco-filters] input[type=radio]')
		.change(handleEcoFilterChange);
	$(window).resize(handle_window_resize);

}

/**
 * Query the current year and set it as a maximum on the year filter
 * options.
 */
function add_max_year_attr() {
	var date = new Date();
	var year = date.getFullYear();

	$("input[name=year-low]").attr("max", year);
	$("input[name=year-high]").attr("max", year);
}

// there may be a way to not have this global using closures
var configTryCount = 0;
function getConfigSettings(alternateLink=null) {
	$.ajax({
		url: (alternateLink || "") + "php/send_config.php",
		type: 'post',
		dataType: 'json',
		success: function(response) {
			config = response;
			configTryCount = 0;
		},
		error: function (xhr, textStatus, errorMessage) {
			configTryCount++;
			if(configTryCount < 10) {
				getConfigSettings("http://127.0.0.1:8000/");
			} else {
				console.log(errorMessage);
			}
		}
	});
}

/**
 * Event handle for processing the uploading of pgn files
 *
 * @param e the event object of the event that triggered this function.
 */
function handle_pgn_submit(e) {
	e.preventDefault();
	var fileSubmitter = document.getElementById("user_db_uploader");

	if (('files' in fileSubmitter) && (fileSubmitter.files.length == 1)) {
		var file = fileSubmitter.files[0];
		var reader = new FileReader();
		var $form = $('#pgn_up_form');
		var send_url = $form.attr("action");
		var hash = "";

		if (file.size > 10000000) {
			alert("only file sizes up to 10Mb allowed");
			return;
		}

		/* readers load files asyncronously */
		reader.onload = function(e) {
			var text = reader.result;
			hash = hex_sha256(text);

			if (pgnHashes[hash]) {
				console.log("file with hash " + hash + " already exists!");
			} else {
				pgnHashes[hash] = [file.name, file];
				console.log("New file " +
					pgnHashes[hash][0] + " with hash: " + hash);
				var db_selector = $("#db_selector");
				db_selector.append($('<option/>', {
					value : hash,
					text : file.name,
					selected : "selected"
				}));
			}
		};

		/* send to server */
		//TODO: this testing for successful upload has not been tested
		//in case where server fails to receive.
		if (!submit_file(file, send_url)) {
			console.log("failed to upload to server");
		} else {

			/*
			 * Tell the reader this is a textfile. It will load the file into
			* memory, which will then call the onload function fust defined
			 * above.
			*/
			reader.readAsText(file);
		}
	} else {
		console.log("Something is wrong with the file input object...");
		if ('files' in fileSubmitter &&
			(fileSubmitter.files.length <= 0)) {
			console.log("No file selected!");
		}
		if ('files' in fileSubmitter && (fileSubmitter.files.length > 1)) {
			console.log("Please select exactly one file");
		}
	}
}

/**
 * Submit a file to the server synchronously using an ajax post.
 *
 * @param file		The file being submitted.
 * @param send_url	he URL of the script handling the submission on the
 *					server
 * @return true if successful upload, false otherwise.
 */
function submit_file(file, send_url) {

	var form_data = new FormData();
	var retval = false;
	form_data.append("user_db_uploader", file);

	$.ajax({
		url: ((config['dev_mode'])?config['php_server']:'') + send_url,
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
			retval = true;
		},
		error: function(xhr, textStatus, errorMessage) {
			console.log(errorMessage);
			retval = false;
		}
	});

	return retval;
}

/**
 * Process the filter form into a json object and send to the server to
 * perform the appropriate query. Successful submission sends the response
 * the the appropriate function.
 *
 * @param event A json object of he event that triggered this subbmission.
 */
function handle_filter_submit(event) {
	event.preventDefault();

	var $form = $('#filter_form');
	var db_val = $('#db_selector').val() || "default";
	send_url = $form.attr("action");
	console.log("action url: ", send_url);
	var filters = getFormData($form);

	filters['database'] = db_val;

	// TODO: vaidation: ensure low year less than high year.

	console.log(
		'Sending these filters:\n',
		filters
	);

	/* check for presence of db syncronously, reloading if necessary */
	if (db_val != "default_chess_db") {
		ensure_database_exists_on_server(db_val);
	}
	$.ajax({
		url: ((config['dev_mode'])?config['php_server']:'') + send_url,
		type: 'post',
		data: filters,
		dataType: 'json',
		success: handle_filter_response,
		error: function(xhr, textStatus, errorMessage) {
			console.log(errorMessage);
		}
	});
}

/**
 * Take a form and process it's data into a json object for submission.
 *
 * @param $form The form to processed.
 * @return The json object containing the form data.
 */
function getFormData($form) {
	var unindexed_array = $form.serializeArray();
	var indexed_array = {};

	$.map(unindexed_array, function(n, i) {
		indexed_array[n['name']] = n['value'];
	});

	return indexed_array;
}

/**
 * A Function asking the server whether a certain database is present.  If
 * not, the appropriate file is uploaded to the server.
 *
 * @param hash	The hash of the file that is being inquired about.
 */
function ensure_database_exists_on_server(hash) {
	console.log("Checking for presence of " + hash);
	$.ajax({
		url: ((config['dev_mode'])?config['php_server']:'') + "php/has_db.php",
		async: false,
		type: 'post',
		dataType: 'json',
		data: {
			"hash": hash,
		},
		success: function(response) {
			if (!response.db_present) {
				$("#temp_results").append("<p>Database " + response.hash +
					" being reuploaded</p>");
				submit_file(get_file_from_hash(hash),
					"php/user_upload.php");
				$("#temp_results").append("<p>Database " + response.hash +
					" reuploaded</p>");
			}
		},
		error: function(xhr, textStatus, errorMessage) {
			console.log(errorMessage);
		}
	});
}

/**
 * Check the response json object sent from the server for errors, and draw
 * the necessary graph if no errors occured.
 * @param	response The json object sent for the server containing either
 *			error information or the data to graph.
 */
function handle_filter_response(response) {
	if (response.error) {
		$("#temp_results").append("<p>" + response.error_message + "</p>");
	}
	else {
		mainJSON = response;
		$("#display_svg").empty();
		draw(response);
	}
}

/**
 * Takes a hash as argument, looks it up in the pgnHashes structure and
 * returns if found.
 *
 * @param hash The hash to look up
 *
 * @return The file, if present in the pgnHashes structure, or null if not.
 */
function get_file_from_hash(hash) {
	if (hash in pgnHashes) {
		return pgnHashes[hash][1];
	}
	else {
		return null;
	}
}

function handle_filter_clear(e) {
	e.preventDefault();
	console.log("clearing filters");
	//TODO: clear filter fields
}

/**
 * Everytime the window size changes, redraw the graph to ensure it is the
 * appropriate size for the new browser window shape.
 *
 * @param e	A json object of the event that triggered this, that is, the
 *			resize event of the window.
 */
function handle_window_resize(e) {
	if (mainJSON != null) {
		$("#display_svg").empty();
		draw(mainJSON);
	}
}

/**
 * When a eco filter option is chosen, repopulate the options that follow,
 * according to the requirements of the filter option.
 */
function handleEcoFilterChange() {
	radioButton = $(this);
	$currentFilter = $('div.current-eco-filter');
	$currentFilter.empty();

	switch(radioButton.val()) {
		case 'category':
			$categorySelect = $('<select />').attr({
				'class': 'control-label col-xs-12',
				'name': 'eco-category'// NB this may break some things!
			});

			$categorySelect.append($('<option />').attr({
				'value': ''
			}).text(
				'Filter by eco category...'
			));

			$.each(['A', 'B', 'C', 'D', 'E'], function(index_let, letter) {
				$optGroup = $('<optgroup />').attr({
					'label': letter
				});

				decimal = (letter == 'A')?[1.1, 1.2, 1.3, 1.4, 1.5]:[1.1, 1.2];
				$.each(decimal, function(index_dec, value) {
					num = value + index_let;
					label = letter + num;

					$optGroup.append($('<option />').attr({
						'value': label
					}).text(label));
				});

				$categorySelect.append($optGroup);
			});

			$currentFilter.append($categorySelect);

			break;
		case 'class':
			$classSelect = $('<select />').attr({
				'class': 'control-label col-xs-12',
				'name': 'eco-class'
			});

			$classSelect.append($('<option />').attr({
				'value': ''
			}).text(
				'Filter by eco class...'
			));

			$.each(['A', 'B', 'C', 'D', 'E'], function(index, letter) {
				$option = $('<option />').attr({
					'value': letter
				}).text(letter);
				$classSelect.append($option);
			});


			$currentFilter.append($classSelect);

			break;
		case 'code':
			$codeSelect = $('<select />').attr({
				'class': 'control-label col-xs-12',
				'name': 'eco-class'// NB this may again break some things!
			});

			$codeSelect.append($('<option />').attr({
				'value': ''
			}).text(
				'Filter by eco class (and number)...'
			));

			$.each(['A', 'B', 'C', 'D', 'E'], function(index, letter) {
				$option = $('<option />').attr({
					'value': letter
				}).text(letter);
				$codeSelect.append($option);
			});

			$currentFilter.append($codeSelect);

			$currentFilter.append($('<input />').attr({
				'type': 'number',
				'min': 0,
				'max': 99,
				'name': 'eco-low',
				'placeholder': 'Low',
				'class': 'control-label col-xs-12'
			}));

			$currentFilter.append($('<input />').attr({
				'type': 'number',
				'min': 0,
				'max': 99,
				'name': 'eco-high',
				'placeholder': 'High',
				'class': 'control-label col-xs-12'
			}));

			break;
		case 'year-eco-analysis':

			$currentFilter.append($('<input />').attr({
				'type': 'number',
				'min': 0,
				'max': 3000,
				'name': 'year',
				'placeholder': 'Select a year',
				'class': 'control-label col-xs-12'
			}));

			break;
	}
}
