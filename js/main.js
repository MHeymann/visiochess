/* global $ */
/* global hex_sha256 */
/* global send_url */
/* global config */
/* global draw */

/* Global variables */
var pgnHashes = {};
var mainJSON;

function handle_pgn_submit(e) {
	e.preventDefault();
	var fileSubmitter = document.getElementById("user_db_uploader");

	if (('files' in fileSubmitter) && (fileSubmitter.files.length == 1)) {
		var file = fileSubmitter.files[0];
		var reader = new FileReader();
		var $form = $('#pgn_up_form');
		send_url = $form.attr("action");
		console.log("action url: ", send_url);

		/* readers load files asyncronously */
		reader.onload = function(e) {
			var text = reader.result;
			var hash = hex_sha256(text);
			pgnHashes[hash] = [file.name, file];
			console.log("New file " +
				pgnHashes[hash][0] + " with hash: " + hash);
			var db_selector = document.getElementById("db_selector");
			var option = document.createElement("option");
			option.text = file.name; // set name a clickable display
			option.value = hash; //set hash as identifiable val
			db_selector.add(option);
		};

		/*
		 * Tell the reader this is a textfile. It will load the file into
		 * memory, which will then call the onload function fust defined
		 * above.
		 */
		reader.readAsText(file);

		submit_file(file, send_url);

	}
	else {
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

function submit_file(file, send_url) {

	var form_data = new FormData();
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
		},
		error: function(xhr, textStatus, errorMessage) {
			console.log(errorMessage);
		}
	});

}

function getFormData($form) {
	var unindexed_array = $form.serializeArray();
	var indexed_array = {};

	$.map(unindexed_array, function(n, i) {
		indexed_array[n['name']] = n['value'];
	});

	return indexed_array;
}

function handle_filter_response(response) {
	// render results
	if (response.error) {
		$("#temp_results").append("<p>" + response.error_message + "</p>");
	}
	else {
		mainJSON = response;
		$("#display_svg").empty();
		draw(response);

		//$("#temp_results").append("<p>" + JSON.stringify(response) +
		//	"</p>");
	}
}

function handle_filter_submit(event) {
	event.preventDefault();

	var $form = $('#filter_form');
	var db_val = $('#db_selector').val() || "default";
	send_url = $form.attr("action");
	console.log("action url: ", send_url);
	var filters = getFormData($form);

	filters['database'] = db_val;

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

function ensure_database_exists_on_server(hash) {
	console.log("checking for presence of " + hash);
	$.ajax({
		url: ((config['dev_mode'])?config['php_server']:'') + "php/has_db.php",
		async: false,
		type: 'post',
		dataType: 'json',
		data: {
			"hash": hash,
		},
		success: function(response) {
			if (response.db_present) {
				$("#temp_results").append("<p>Database " + response.hash +
					" is present</p>");
			}
			else {
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

function handle_window_resize(e) {
	if (mainJSON != null) {
		$("#display_svg").empty();
		draw(mainJSON);
	}
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
			$categorySelect = $('<select />').attr({
				'class': 'control-label col-xs-12',
				'name': 'eco-class'// NB this may again break some things!
			});

			$categorySelect.append($('<option />').attr({
				'value': ''
			}).text(
				'Filter by eco category...'
			));

			$.each(['A', 'B', 'C', 'D', 'E'], function(index, letter) {
				$option = $('<option />').attr({
					'value': letter
				}).text(letter);
				$categorySelect.append($option);
			});

			$currentFilter.append($categorySelect);

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
			/*
			$categorySelect = $('<select />').attr({
				'class': 'control-label col-xs-12',
				'name': 'eco-class'// NB this may again break some things!
			});

			$categorySelect.append($('<option />').attr({
				'value': ''
			}).text(
				'Filter by eco category...'
			));

			$.each(['A', 'B', 'C', 'D', 'E'], function(index, letter) {
				$option = $('<option />').attr({
					'value': letter
				}).text(letter);
				$categorySelect.append($option);
			});

			$currentFilter.append($categorySelect);
			*/

			$currentFilter.append($('<input />').attr({
				'type': 'number',
				'min': 0,
				'max': 3000,
				'name': 'year',
				'placeholder': 'Select a year',
				'class': 'control-label col-xs-12'
			}));

			/*
			$currentFilter.append($('<input />').attr({
				'type': 'number',
				'min': 0,
				'max': 99,
				'name': 'eco-high',
				'placeholder': 'High',
				'class': 'control-label col-xs-12'
			}));
			*/

			break;
	}
}

window.onload = function() {
	getConfigSettings();

	/* TODO
	 * Add function that, when files are selected, checks them for size
	 * constraints.
	 */
	$("#pgn_up_form").submit(handle_pgn_submit);
	/* TODO:
	 * in filter_submitions, look at which database is currently selected
	 * in the selector form, and send that data to the server along with
	 * the filters.
	 */

	 add_max_year_attr();

	$('#filter_form').submit(handle_filter_submit);

	$(window).resize(handle_window_resize);

	$("#test_reupload").click(function(e) {
		$.ajax({
			url: ((config['dev_mode'])?config['php_server']:'') + "php/query.php",
			type: 'post',
			data: {
				'year': 2014,
				'query_type': 'elo_histo',
				'eco-filter-type':'year-eco-analysis'
			},
			dataType: 'json',
			success: function (response) {
				$("#temp_results").append("<p>" + JSON.stringify(response) +
						"</p>");
				if (response.error) {
					$("#temp_results").append("<p>" + response.error_message + "</p>");
				}
				else {
					mainJSON = response;
					$("#display_svg").empty();
					draw(response);

					$("#temp_results").append("<p>" + JSON.stringify(response) +
							"</p>");
				}
			},
			error: function(xhr, textStatus, errorMessage) {
				console.log(errorMessage);
			}
		});
	});

	$('div[name=eco-filters] input[type=radio]')
		.change(handleEcoFilterChange);
}

function add_max_year_attr() {
	var date = new Date();
	var year = date.getFullYear();

	$("input[name=eco-low]").attr("max", year);
	$("input[name=eco-high]").attr("max", year);
}

function add_max_year_attr() {
	var date = new Date();
	var year = date.getFullYear();

	$("input[name=eco-low]").attr("max", year);
	$("input[name=eco-high]").attr("max", year);
}
