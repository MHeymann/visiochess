/* global $ */
/* global hex_sha256 */
/* global send_url */
/* global config */

/* Global variables */
var pgnHashes = {};
var pgnOptions = {};
var pgnCurrent = "";
var board;
var game = null;
var statusEl;
var pgnEl;
var moveCount;

/**
 * Set up the necessary parameters and event handlers as soon as the html
 * form is loaded.
 */
window.onload = function() {
	getConfigSettings();
	add_max_year_attr();

	$("#pgn_up_form").submit(handle_pgn_submit);
	$('#filter_form').submit(handle_filter_submit);
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
	if (event != null) {
		event.preventDefault();
	}

	var $form = $('#filter_form');
	var db_val = $('#db_selector').val() || "default";
	send_url = $form.attr("action");
	console.log("action url: ", send_url);
	var filters = getFormData($form);
	var filters = {};

	filters['database'] = db_val;
	if (pgnCurrent != null) {
		filters['pgn_moves'] = pgnCurrent;
	} else {
		filters['pgn_moves'] = "";
	}

	console.log(
		'Sending these filters:\n',
		JSON.stringify(filters)
		, "to",
		send_url
	);
	/* Commented out because script doesn't exist yet for server to
	 * respond.
	*/
	$.ajax({
		url: send_url,
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
 * Check the response json object sent from the server for errors, and draw
 * the necessary graph if no errors occured.
 * @param	response The json object sent for the server containing either
 *			error information or the data to graph.
 */
function handle_filter_response(response) {
	if (response.error) {
		$("#temp_results").append("<p>" + response.error_message + "</p>");
		console.log("<p>" + response.error_message + "</p>");
	} else {
		pgnOptions = response.data;

		var total = 0;
		for (option in pgnOptions) {
			total += pgnOptions[option];
		}

		var myTable= "<table><tr><th>Move</th>";
		myTable+="<th style='text-align: right;'>Total</th>";
		myTable+="<th style='text-align: right;'>Percent</th></tr>";

		for (option in pgnOptions) {
			myTable+="<tr><td style='width: 100px;'>" + option + ":</td>";
			myTable+="<td style='width: 100px; text-align: right;'>" + pgnOptions[option] + "</td>";
			myTable+="<td style='width: 100px; text-align: right;'>" + (pgnOptions[option] / (total + 0.0) * 100).toFixed(2) + "%</td></tr>";
		}

		myTable+="<tr><td style='width: 100px;'>Total:</td>";
		myTable+="<td style='width: 100px; text-align: right;'>" + total + "</td>";
		myTable+="<td style='width: 100px; text-align: right;'>" + ((total) / (total + 0.0) * 100) + "%</td></tr>";
		myTable+="</table>";

		$("#temp_results").html(myTable);

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


var game_init = function() {

	game = new Chess();
	statusEl = $('#status');
	pgnEl = $('#pgn');
	moveCount = 1;

	var cfg = {
		draggable: true,
		position: 'start',
		onDragStart: onDragStart,
		onDrop: onDrop,
		onSnapEnd: onSnapEnd
	};
	board = ChessBoard('board', cfg);
	$(window).resize(board.resize);

	updateStatus();
}; // end init()


function onDrop(source, target) {
	var moveColor = 'White';
	if (game.turn() === 'b') {
		moveColor = 'Black';
	}
	// see if the move is legal
	var move = game.move({
		from: source,
		to: target,
		promotion: 'q' // NOTE: always promote to a queen for example simplicity
	});

	// illegal move
	if (move === null) {
		return 'snapback';
	}
	console.log("move: " + JSON.stringify(move));

	newPgn = game.pgn();
	console.log("evaulating if new pgn is valid: ", newPgn);
	var match = false;
	for (option in pgnOptions) {
		var cmpOpt = "";
		if (moveColor == 'Black') {
			options = option.split(" ");
			cmpOpt = pgnCurrent + " " + options[options.length - 1];

		} else {
			if (moveCount == 1) {
				cmpOpt = moveCount +". " + option;
			} else {
				cmpOpt = pgnCurrent + " " + moveCount +". " + option;
			}
		}
		if (cmpOpt.includes(newPgn)) {
			console.log("positive match!");
			match = true;
			break;
		}
	}
	if (!match) {
		console.log("No match found. Undoing move");
		game.undo();
		return 'snapback';
	}

	if (moveColor == 'Black') {
		moveCount++;
	}
	updateStatus();
};


// do not pick up pieces if the game is over
// only pick up pieces for the side to move
function onDragStart(source, piece, position, orientation) {
	if (game.game_over() === true ||
			(game.turn() === 'w' && piece.search(/^b/) !== -1) ||
			(game.turn() === 'b' && piece.search(/^w/) !== -1)) {
		return false;
	}
};


// update the board position after the piece snap
// for castling, en passant, pawn promotion
function onSnapEnd() {
	board.position(game.fen());
};




function updateStatus() {
	var status = '';


	var moveColor = 'White';
	if (game.turn() === 'b') {
		moveColor = 'Black';
	}

	// checkmate?
	if (game.in_checkmate() === true) {
		status = 'Game over, ' + moveColor + ' is in checkmate.';
	}

	// draw?
	else if (game.in_draw() === true) {
		status = 'Game over, drawn position';
	} else {
		// game still on
		status = moveColor + ' to move';

		// check?
		if (game.in_check() === true) {
			status += ', ' + moveColor + ' is in check';
		}
	}

	statusEl.html(status);
	pgnCurrent = game.pgn();
	// ajax for new move options
	handle_filter_submit(null);
	pgnEl.html(pgnCurrent);
};
