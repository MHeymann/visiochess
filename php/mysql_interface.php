<?php

require_once "utils.php";
/*
TODO:
Busy but necessary work:
Comment everything!

Features:
Add fatal params - if fatal is true, program quits on error
Add multi insert function
Add args checks for new functions
*/
class MySqlPhpInterface
{
	private $server = null;
	private $port = null;
	private $user = null;
	private $password = null;
	private $database = null;
	private $table = null;
	private $connection = null;
	private $verbose = null;

	public function __construct($server, $user, $password, $port=null, $verbose=false) {
		$this->server = $server;
		$this->user = $user;
		$this->password = $password;
		$this->port = $port;
		$this->verbose = $verbose;
	}

	private function quit($error_message = "") {
		echo "<p> " . $error_message . " </p>\n";
		$this->connection->close();
		die("<p> ---------- CONNECTION TERMINATED ---------- </p>\n");
	}

	public function connect() {
		$server = $this->server;
		if($this->port) {
			$server .= ":" . $this->port;
		}

		$this->connection = new mysqli(
			$server,
			$this->user,
			$this->password
		);

		$error = $this->connection->connect_error;
		if($error) {
			$this->quit(
				"Could not connect to ". $server . "\n" .
				"Error: " . $error
			);
		} else {
			if($this->verbose) {
				echo "<p> ----------    CONNECTION OPEN    ---------- </p>\n";
			}
		}

		if($this->verbose) {
			echo "<p> Successfully connected to: " . $server . " </p>\n";
		}
	}

	public function disconnect() {
		$this->connection->close();
		if($this->verbose) {
			echo "<p> ----------   CONNECTION CLOSED   ---------- </p>\n";
		}
	}

	private function attempt($query) {
		/* if(!$query) { */
		/*   $this->quit("You must enter a query to send to the database."); */
		/* } */

		$q_is_ok = $this->connection->query($query);

		if($q_is_ok) { /* TODO: these need to be adapted to be able to return query info */
			/* if(gettype($status) == "object") { */
			/*   var_dump($status->fetch_all()); */
			/* } */
			return null; /* successful query */
		} else {
			/* if(gettype($status) == "object") { */
			/*   var_dump($status->fetch_all()); */
			/* } */
			return $this->connection->error; /* unsuccessful query */
		}
	}

	public function create_database($name, $replace=false) {
		if(!$name) {
			$this->quit("You must enter a name for the database you want to create");
		}

		$sql = "CREATE DATABASE `" . $name . "`;";
		$error = $this->attempt($sql);

		if($error) {
			if(contains($error, "database exists")) {
				if($replace) {
					if($this->verbose) {
						echo "<p> `" . $name .
							"` database already exists, it will be
							deleted and replaced </p>\n";
					}
					$this->delete_database($name);
					$this->create_database($name);
					return;
				}
			}

			$this->quit(
				"Could not create database: `" . $name . "`\n" .
				"Error: " . $error
			);
		} else {
			if($this->verbose) {
				echo "<p> `" . $name . "` database created successfully </p>\n";
			}
		}
	}

	public function delete_database($name) {
		if(!$name) {
			$this->quit("You must enter a name for the database you want to delete");
		}

		$sql = "DROP DATABASE `" . $name . "`;";
		$error = $this->attempt($sql);

		if($error) {
			$this->quit(
				"Could not delete database: `" . $name . "`\n" .
				"Error: " . $error
			);
		} else {
			if($this->verbose) {
				echo "<p> `" . $name . "` database deleted successfully </p>\n";
			}
		}
	}

	public function use_database($name) {
		if(!$name) {
			$this->quit("You must enter a name for the database you want to use");
		}

		/* could actually take this out and append $name to each query */
		$sql = "USE `" . $name . "`;";
		$error = $this->attempt($sql);

		if($error) {
			$this->quit(
				"Could not use database: `" . $name . "`\n" .
				"Error: " . $error
			);
		} else {
			$this->database = $name;
			if($this->verbose) {
				echo "<p> The `" . $name . "` database will now be used </p>\n";
			}
		}
	}

		/*_________________________________________________________ */
		/* remember to add db if using */
	public function create_table($name, $structure, $replace=false) {
		if(!$name) {
			$this->quit("You must enter a name for the table you want to create");
		}

		if(!$structure) {
			$this->quit("You must define a structure for the table you want to create");
		}

		$sql = "CREATE TABLE `" . $name . "`(" . stringify($structure) . ");";
		$error = $this->attempt($sql);

		if($error) {
			if(contains($error, "table exists")) {
				if($replace) {
					if($this->verbose) {
						echo "<p> `" . $name .
							"` table already exists, it will be deleted and replaced </p>\n";
					}
					$this->delete_table($name);
					$this->create_table($name, $structure);
					return;
				}
			}

			$this->quit(
				"Could not create table: `" . $name . "`\n" .
				"Error: " . $error
			);
		} else {
			if($this->verbose) {
				echo "<p> `" . $name . "` table created successfully </p>\n";
			}
		/* $this->sql("describe `" . $name . "`;"); */
		}
	}

		/* remember to add db name is using */
	public function delete_table($name=null) {
		if(!$name) {
			$this->quit("You must enter a name for the table you want to delete");
		}

		$sql = "DROP TABLE `" . $name . "`;";
		$error = $this->attempt($sql);

		if($error) {
			$this->quit(
				"Could not delete table: `" . $name . "`\n" .
				"Error: " . $error
			);
		} else {
			if($this->verbose) {
				echo "<p> `" . $name . "` table deleted successfully </p>\n";
			}
		}
	}


		/*_________________________________________________________ */
		/* remember to add db if using */
	public function create_index($iname=null, $tname=null, $cols=null, $replace=false) {
		if(!$tname) {
			$this->quit("You must enter a name for the table you want to index");
		}
		if(!$iname) {
			$this->quit("You must enter a name for the index you want to create");
		}
		if(!$cols) {
			$this->quit("You must provide the columns for the index you want to create");
		}

		$sql = "CREATE INDEX `" . $iname. "` ON `" . $tname . "` (" .
		   	self::stringify_cols($cols) . ");";
		$error = $this->attempt($sql);

		if($error) {
			/* this error needs testing */
			if(contains($error, "Duplicate key name")) {
				if($replace) {
					if($this->verbose) {
						echo "<p> index`" . $iname . "` on `" . $tname .
							"`  already exists, it will be deleted and replaced </p>\n";
					}
					$this->delete_index($iname, $tname);
					$this->create_index($iname, $tname, $cols);
					return;
				}
			}

			$this->quit(
				"Could not create index `" . $iname . "` on: `" . $tname . "`\n" .
				"Error: " . $error
			);
		} else {
			if($this->verbose) {
				echo "<p> `" . $iname . "` index on `" . $tname .
				"` created successfully </p>\n";
			}
		/* $this->sql("describe `" . $name . "`;"); */
		}
	}

		/* remember to add db name is using */
	public function delete_index($iname=null, $tname=null) {
		if(!$iname) {
			$this->quit("You must enter a name for the index you want to delete");
		}
		if(!$tname) {
			$this->quit("You must enter a name for the table you want to delete " .
			"an index on.");
		}

		$sql = "DROP INDEX `" . $iname . "` ON `" . $tname . "`;";
		$error = $this->attempt($sql);

		if($error) {
			$this->quit(
				"Could not delete index: `" . $iname . "` on `" . $tname . "`\n" .
				"Error: " . $error
			);
		} else {
			if($this->verbose) {
				echo "<p> `" . $iname . "` index on `" . $tname . "` deleted successfully </p>\n";
			}
		}
	}


	public function insert($table_name, $data) {
		/* construct insert sql statement */
		$insert_statement = "INSERT INTO `" . $table_name . "` (";
		$value_placeholder = "";
		$value_types = "";
		$values = array();

		foreach ($data as $column => &$value) {
			$insert_statement .= "`" . $column . "`, ";
			$value_placeholder .= "?, ";
			$values[] = &$value;

			switch(gettype($value)) {
			case "string":
				$value_types .= "s";
				break;
			case "integer":
				$value_types .= "i";
				break;
			case "double":
				$value_types .= "d";
				break;
			default:
		/* MySQL does not support it, so add it as a blob */
				$value_types .= "b";
			}
		}

		$insert_statement = substr($insert_statement, 0, count($insert_statement) - 3);
		$insert_statement .= ") VALUES (";
		$insert_statement .= substr($value_placeholder, 0, count($value_placeholder) - 3);
		$insert_statement .= ")";
		array_unshift($values, $value_types);

		$statement = $this->connection->prepare($insert_statement);
		call_user_func_array(array($statement, "bind_param"), $values);

		if($statement->execute()) {
			if($this->verbose) {
				echo "<p> Successfully inserted data </p>\n";
			}
		} else {
			if($this->verbose) {
				echo "<p> Could not insert data </p>\n";
			}
		}
		$statement->close();
	}



	public function insert_multiple($table_name, $data) {
		/* construct insert sql statement */
		$insert_statement = "INSERT INTO `" . $table_name . "` (";
		$value_placeholder = "";
		$value_types = "";
		$values = array();

		if (count($data) <= 0) {
			echo "<p>No data provided to insert</p>\n";
			return;
		}

		foreach ($data[0] as $column => &$value) {
			$insert_statement .= "`" . $column . "`, ";
		}

		$insert_statement = substr($insert_statement, 0, count($insert_statement) - 3);
		$insert_statement .= ") VALUES (";

		foreach ($data as &$row) {
			foreach ($row as $column => &$value) {
				$value_placeholder .= "?, ";
				$values[] = &$value;

				switch(gettype($value)) {
				case "string":
					$value_types .= "s";
					break;
				case "integer":
					$value_types .= "i";
					break;
				case "double":
					$value_types .= "d";
					break;
				default:
					/* MySQL does not support it, so add it as a blob */
					$value_types .= "b";
				}
			}

			$insert_statement .= substr($value_placeholder, 0, count($value_placeholder) - 3);
			$insert_statement .= "), (";
			$value_placeholder = "";
		}
		$insert_statement = substr($insert_statement, 0, count($insert_statement) - 4);
		array_unshift($values, $value_types);

		$statement = $this->connection->prepare($insert_statement);
		if($this->verbose) {
			echo "<p>" . $insert_statement . "</p>";
		}
		call_user_func_array(array($statement, "bind_param"), $values);

		if($statement->execute()) {
			if($this->verbose) {
				echo "<p> Successfully inserted multiple data </p>\n";
			}
		} else {
			if($this->verbose) {
				echo "<p> Could not insert multiple data </p>\n";
			}
		}
		$statement->close();
	}


	public function select_from($table_name, $columns, $conditions, $extra_arguments=null) {
		/* construct select sql query */
		$query = "SELECT ";
		// I can replace this with $columns.join?
		foreach ($columns as $column) {
			// $query .= "`" . $column . "`, ";
			$query .= $column . ", ";
		}
		$query = substr($query, 0, count($query) - 3);
		$query .= " FROM `" . $table_name . "` WHERE ";

		if(!$conditions) {
			$conditions = array('gameID' => array('>' => -1));
		}

		$values = array();
		$value_types = "";
		foreach ($conditions as $column => &$condition) {
			foreach ($condition as $operation => &$value) {
				$query .= "`" . $column . "` $operation ? AND ";
				$values[] = &$value;

				switch(gettype($value)) {
				case "string":
					$value_types .= "s";
					break;
				case "integer":
					$value_types .= "i";
					break;
				case "double":
					$value_types .= "d";
					break;
				default:
		/* MySQL does not support it, so add it as a blob */
					$value_types .= "b";
				}
			}
		}
		$query = substr($query, 0, count($query) - 6);
		array_unshift($values, $value_types);

		if($extra_arguments) {
			foreach ($extra_arguments as $argument) {
				$query .= " " . $argument;
			}
		}

		$statement = $this->connection->prepare($query);
		if(!$statement) {
			if($this->verbose) {
				echo "Error with prepare statement: " .
					$this->connection->error . "\n";
			}
		}

		call_user_func_array(array($statement, "bind_param"), $values);

		if($statement->execute()) {
			if($this->verbose) {
				echo "<p> Query successful </p>\n";
			}
		} else {
			if($this->verbose) {
				echo "<p> Query unsuccessful </p>\n";
			}
		}

		$return_data = array();
		$result = $statement->get_result();
		while($entry = $result->fetch_assoc()) {
			$return_data[] = $entry;
		}

		$statement->close();
		return $return_data;
	}

		/* send a sql command to the server */
		/* to handle cases that are not yet catered for */
	public function sql($sql) {
		if(!$sql) {
			$this->quit(
				"You must enter a sql command that you want to send to the server"
			);
		}

		/* could actually take this out and append $name to each query */
		$error = $this->attempt($sql);
		if($error) {
			$this->quit(
				"Could not execute command\n" .
				"Error: " . $error
			);
		} else {
			if($this->verbose) {
				echo "<p> The command was successfully executed </p>\n";
			}
		/* var_dump($this->connection); */
		}
	}

	/*
	 * this really has no busines here, it should actually be in a utils.php
	 */
	/*
	private static function contains($string, $token) {
		if(strpos($string, $token) !== false) {
			return true;
		} else {
			return false;
		}
	}
	 */
	private static function stringify_cols($object) {
		$string = "";
		foreach($object as $col) {
			$string .= $col . ", ";
		}
		$string = substr($string, 0, count($string) - 3);
		return $string;
	}
}
?>
