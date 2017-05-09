<?php
require_once "mysql_interface.php";
//require_once "validate.php";
require_once "utils.php";

/* still needed for testing */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// TODO: remove this and check if everything works on hosted server
header('Access-Control-Allow-Origin: *');

$filters = array();
foreach($_POST as $filter_field => $value) {
    $filters[$filter_field] = trim($value);
}

if (!isset($filters["pgn_moves"])) {
    $filters["pgn_moves"] = "";
}

$moves_messy = explode(" ", trim($filters["pgn_moves"]));
$moves = array("white" => array(), "black" => array());
/* sort into two arrays, one for each player */
get_moves_array($moves_messy, $moves);

$num_moves = max(count($moves['white']), count($moves['black']));
$num_black_moves = count($moves['black']);
$num_white_moves = count($moves['white']);
if (!isset($moves["black"][$num_moves - 1]) && ($num_moves > 0)) {
	$moves["black"][$num_moves - 1] = "";
}

if ($num_black_moves < 6) {
    $first_moves = array();
    for ($i = 1; $i <= $num_white_moves; $i++) {
        $first_moves["move_$i"] = $moves["white"][$i-1] . " " . $moves["black"][$i-1];
        $first_moves["move_$i"] = trim($first_moves["move_$i"]);
    }
} else {
    $filters["pgn_moves"] = trim($filters["pgn_moves"]);
}

$filter_on = array();

/* if no database set, set default */
if(!isset($filters['database']) || ! $filters['database']) {
    $filters['database'] = 'default_chess_db';
}


/* get login details */
$settings = parse_ini_file(__DIR__."/../.my.cnf", true);

/* setup database interface connection */
$db = new MySqlPhpInterface(
    $server=$settings['client']['mysql_server'],
    $user=$settings['client']['user'],
    $password=$settings['client']['password']
);
$db->connect();

$db->use_database($filters['database']);


/* set up the query "WHERE" fields */
$query = array();
//set_query_where_fields($filter_on, $filters, $query);
if ($num_black_moves < 6) {
    for ($i = 1; $i <= $num_white_moves; $i++) {
        $query["move_$i"] = array();
        $query["move_$i"]["LIKE"] = $first_moves["move_$i"] . "%";
    }
} else {
    $query["pgn_string"]["LIKE"] = $filters["pgn_moves"] . "%";
}




$select = [];
$groups = [];
/* Set up the query to perform */
if (($num_white_moves == 0) && ($num_black_moves == 0)) {
    $select[] = "`move` as `move_1`";
    $select[] = "`total` as `popularity`";
	$query = [];
	$query["total"] = array();
	$query["total"][">="] = 0;
    $groups = ["ORDER BY `popularity` DESC"];
    //$select = ['`date`', 'CONCAT(eco_alpha, eco_numero) as `eco`', 'COUNT(*) AS `popularity`'];

    /* perform query */
    $result = $db->select_from(
            'first_move',
            $select,
            $query,
            $groups
        );

    if(!count($result)) {
        $db->disconnect();
        echo json_encode(array(
            'error'=>true,
            'error_message' => "No data exists that satisfy these filters."
        ));
        die();
    }
} else if ($num_black_moves < 6) {
    $i = $num_black_moves + 1;
    $select[] = "`move_$i`";
    $select[] = "COUNT(*) as `popularity`";
    $groups = ["GROUP BY move_$i", "ORDER BY `popularity` DESC"];
    //$select = ['`date`', 'CONCAT(eco_alpha, eco_numero) as `eco`', 'COUNT(*) AS `popularity`'];

    /* perform query */
    $result = $db->select_from(
            'moves_six',
            $select,
            $query,
            $groups
        );

    if(!count($result)) {
        $db->disconnect();
        echo json_encode(array(
            'error'=>true,
            'error_message' => "No data exists that satisfy these filters."
        ));
        die();
    }
} else {
    $select = ['pgn_string'];
    $result = $db->select_from(
            'moves_pgn',
            $select,
            $query,
            null
        );

    if(!count($result)) {
        $db->disconnect();
        echo json_encode(array(
            'error'=>true,
            'error_message' => "No data exists that satisfy these filters."
        ));
        die();
    }
}



$db->disconnect();

$json_data = array();
if ($num_black_moves < 6) {
    foreach ($result as $entry) {
        $i = $num_black_moves + 1;
        $json_data[$entry["move_$i"]] = $entry["popularity"];
    }
} else {
    foreach ($result as $entry) {
        $moves_messy = explode(" ", trim($entry["pgn_string"]));
        $moves = array("white" => array(), "black" => array());
        /* sort into two arrays, one for each player */
        get_moves_array($moves_messy, $moves);
        if (isset($moves["white"][$num_black_moves])) {
			if (!isset($moves["black"][$num_black_moves])) {
				$moves["black"][$num_black_moves] = "";
			}
            $this_move = $moves["white"][$num_black_moves] . " " . $moves["black"][$num_black_moves];
            $this_move = trim($this_move);
            if (!isset($json_data[$this_move])) {
                $json_data[$this_move] = 0;
            }
            $json_data[$this_move] += 1;
        }

    }
	arsort($json_data);
}


 $json = array();
 if(isset($error) && $error) {
     $json['error'] = true;
     $json['error_message'] = "MySQL server could not process request";
 } else {
     $json['error'] = false;
     $json['data'] = $json_data;
 //  $json['pop'] = $top_ecos;
 }
 echo json_encode($json);



function get_moves_array($moves_messy, &$moves) {
    /* set black as initial so that first push is on white */
    $prev_player = "black";

    foreach($moves_messy as $value) {
        $value = trim($value);
        $value = str_replace(".", "", $value);

        if(!is_numeric($value) && !empty($value)) {
            if($prev_player == "black") {
                array_push($moves["white"], $value);
                $prev_player = "white";
            } else {
                array_push($moves["black"], $value);
                $prev_player = "black";
            }
        }
    }
}

?>
