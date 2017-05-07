<?php
require_once "utils.php";

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

	/**
	 * Initialize the variables necessary to connect to the mysql server.
	 *
	 * @param $server The ip of the mysql server you wish to connect to
	 * @param $user The username you wish to log into the mysql server with
	 * @param $password The password associated with this username
	 * @param $port The port through which you wish to connect to the server
	 * @param $verbose If true, will produce output for each process
	 */
	public function __construct($server=null, $user=null,
	$password=null, $port=null, $verbose=false) {
		if(!$server) {
			$this->quit(
				"You must specify a server you wish to connect to"
			);
		}

		if(!$user) {
			$this->quit(
				"You must specify a username to connect to the server with"
			);
		}

		if($password === null) {
			$this->quit(
				"You must give the password associated with the given username"
			);
		}

		$this->server = $server;
		$this->user = $user;
		$this->password = $password;
		$this->port = $port;
		$this->verbose = $verbose;
	}

	/**
	 * Displays an error message and then kills the current process.
	 * To be used when a fatal error is encountered.
	 *
	 * @param $error_message A string to display before killing the process.
	 */
	private function quit($error_message="") {
		echo "<p> " . $error_message . " </p>\n";
		$this->connection->close();
		die("<p> ---------- CONNECTION TERMINATED ---------- </p>\n");
	}

	/**
	 * Initialize a connection to the mysql server.
	 */
	public function connect() {
		/* if a port has been specified, append it to the server ip */
		$server = $this->server;
		if($this->port) {
			$server .= ":" . $this->port;
		}

		/* create the connection */
		$this->connection = new mysqli(
			$server,
			$this->user,
			$this->password
		);

		/* check for connection errors */
		$error = $this->connection->connect_error;
		if($error) {
			/* kill the process if an error was encountered */
			$this->quit(
				"Could not connect to $server\nError: $error"
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

	/**
	 * Close the conneciton to the mysql server.
	 */
	public function disconnect() {
		$this->connection->close();
		if($this->verbose) {
			echo "<p> ----------   CONNECTION CLOSED   ---------- </p>\n";
		}
	}

	/**
	 * Sends an sql statement that is to be executed by the server.
	 *
	 * @param $query A string containing the sql statement to be executed.
	 */
	private function attempt($query) {
		/* send the query to the server */
		$q_is_ok = $this->connection->query($query);

		if($q_is_ok) {
			return null; /* successful query */
		} else {
			return $this->connection->error; /* unsuccessful query */
		}
	}

	/**
	 * Create a database, with a specified name, on the server.
	 *
	 * @param $name The name of the database you wish to create.
	 * @param $replace If true, will delete and replace any existing database
	 * with the same name as the one specified.
	 */
	public function create_database($name=null, $replace=false) {
		if(!$name) {
			$this->quit(
				"You must enter a name for the database you want to create"
			);
		}

		/* construct and send the sql statement */
		$sql = "CREATE DATABASE `" . $name . "`;";
		$error = $this->attempt($sql);

		/* check for and handle errors */
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

	/**
	 * Delete the specified database that exists on the server.
	 *
	 * @param $name The name of the database you wish to delete.
	 */
	public function delete_database($name=null) {
		if(!$name) {
			$this->quit(
				"You must enter a name for the database you want to delete"
			);
		}

		/* construct and send the sql statement */
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

	/**
	 * Tell the server which database you wish to
	 * send all following sql statements to.
	 *
	 * @param $name The name of the database you
	 * wish to send sql statements to.
	 */
	public function use_database($name=null) {
		if(!$name) {
			$this->quit(
				"You must enter a name for the database you want to use"
			);
		}

		/* construct and send the sql statement */
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

	/**
	 * Create a table, with a specified name, on the server.
	 *
	 * @param $name The name of the table you wish to create.
	 * @param $structure This defines the columns and datatypes for the table,
	 * this should be an object with the keys being the names of the columns
	 * and the values being the corrosponding datatypes.
	 * @param $replace If true, will delete and replace any existing table
	 * with the same name as the one specified.
	 */
	public function create_table($name=null, $structure=null, $replace=false) {
		if(!$name) {
			$this->quit(
				"You must enter a name for the table you want to create"
			);
		}

		if(!$structure) {
			$this->quit(
				"You must define a structure for the table you want to create"
			);
		}

		/* construct and send sql statement */
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
		}
	}

	/**
	 * Delete the specified table that exists in the database, on the server.
	 *
	 * @param $name The name of the table you wish to delete.
	 */
	public function delete_table($name=null) {
		if(!$name) {
			$this->quit(
				"You must enter a name for the table you want to delete"
			);
		}

		/* construct and send sql statement */
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

	/**
	 * Creates indexes on the specified table. Indexes are
	 * a way to specify how the internal data in the database
	 * should be structured to optimse queries.
	 *
	 * @param $iname The name of the index you wish to create.
	 * @param $tname The name of the table you wish to create the index on.
	 * @param $cols The columns (in descending order of importance)
	 * you wish to create the index on.
	 * @param $replace If true, will delete and replace any existing database
	 * and table indexes with the same name as the one specified.
	 */
	public function create_index($iname=null, $tname=null, $cols=null, $replace=false) {
		if(!$tname) {
			$this->quit(
				"You must enter a name for the table you want to index"
			);
		}

		if(!$iname) {
			$this->quit(
				"You must enter a name for the index you want to create"
			);
		}

		if(!$cols) {
			$this->quit(
				"You must provide the columns for the index you want to create"
			);
		}

		/* construct and send sql statement */
		$sql = "CREATE INDEX `" . $iname. "` ON `" . $tname . "` (" .
			implode(", ", $cols) . ");";
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
		}
	}

	/**
	 * Delete the specified index that exists on a
	 * specific table, in the database, on the server.
	 *
	 * @param $iname The name of the index you wish to delete.
	 * @param $tname The name of the table is is currently on.
	 */
	public function delete_index($iname=null, $tname=null) {
		if(!$iname) {
			$this->quit(
				"You must enter a name for the index you want to delete"
			);
		}

		if(!$tname) {
			$this->quit(
				"You must enter a name for the table you want to delete " .
				"an index on."
			);
		}

		/* construct and send sql statement */
		$sql = "DROP INDEX `" . $iname . "` ON `" . $tname . "`;";
		$error = $this->attempt($sql);

		if($error) {
			$this->quit(
				"Could not delete index: `$iname` on `$tname`\n" .
				"Error: " . $error
			);
		} else {
			if($this->verbose) {
				echo "<p> `$iname` index on `$tname` " .
				" deleted successfully </p>\n";
			}
		}
	}

	/**
	 * Insert data into a specific table, in a database on the server.
	 *
	 * @param $table_name The name of the
	 * table you wish to insert the data into.
	 * @param $data This gives the specific data entries to be inserted,
	 * this should be an object with the keys being the names of the columns
	 * and the values being the corrosponding data.
	 */
	public function insert($table_name=null, $data=null) {
		if(!$table_name) {
			$this->quit(
				"You must provide the name of the table" .
				" you wish to insert the data into"
			);
		}

		if(!$data) {
			$this->quit(
				"You must provide data you wish to" .
				" insert into the specified table"
			);
		}

		/*
		 * construct insert sql statement, this statement needs to
		 * be prapared to ensure it is safe, the user is able to specify
		 * some of the data that is received by this function and thus we
		 * need to make sure we protect the sql server.
		 */
		$insert_statement = "INSERT INTO `" . $table_name . "` (";

		/* holds "?" place holders for the prepare statment */
		$value_placeholder = "";

		/*
		 * the prepare statement requires a string describing the
		 * data types of all data to be inserted into the table.
		 */
		$value_types = "";

		/*
		 * The prepare statement needs the references
		 * of all data to be inserted into the table.
		 */
		$values = array();

		foreach ($data as $column => &$value) {
			$insert_statement .= "`" . $column . "`, ";
			$value_placeholder .= "?, ";
			$values[] = &$value; /* add reference to the data to be inserted */

			/* figure out what datatype the value is */
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

		/* remove extra comma and space */
		$insert_statement = trim_chars($insert_statement, 2);
		$insert_statement .= ") VALUES (";

		/* remove extra comma and space */
		$insert_statement .= trim_chars($value_placeholder, 2);
		$insert_statement .= ")";

		/* prepend the data types to the data values array */
		array_unshift($values, $value_types);

		/* tell the sql server to prepare the sql statent */
		$statement = $this->connection->prepare($insert_statement);

		/*
		 * This calls the `bind_param` function on the sql statement
		 * and the relevant data. This is done through the
		 * `call_user_func_array` function as we need to send a variable
		 * amount of data to the `bind_param` function and it does
		 * not inerintly allow this.
		 */
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

	/**
	 * Insert data into a specifict table, in a database on the server.
	 *
	 * @param $table_name The name of the
	 * table you wish to insert the data into.
	 * @param $data This gives the specific data entries to be inserted,
	 * this should be an array of objects with the keys being the names
	 * of the columns and the values being the corrosponding data.
	 */
	public function insert_multiple($table_name=null, $data=null) {
		if(!$table_name) {
			$this->quit(
				"You must provide the name of the table" .
				" you wish to insert the data into"
			);
		}

		if(!$data) {
			$this->quit(
				"You must provide data you wish to" .
				" insert into the specified table"
			);
		}

		/*
		 * construct insert sql statement, this statement needs to
		 * be prapared to ensure it is safe, the user is able to specify
		 * some of the data that is received by this function and thus we
		 * need to make sure we protect the sql server.
		 */
		$insert_statement = "INSERT INTO `" . $table_name . "` (";

		/* holds "?" place holders for the prepare statment */
		$value_placeholder = "";

		/*
		 * the prepare statement requires a string describing the
		 * data types of all data to be inserted into the table.
		 */
		$value_types = "";

		/*
		 * The prepare statement needs the references
		 * of all data to be inserted into the table.
		 */
		$values = array();

		if (count($data) <= 0) {
			echo "<p>No data provided to insert</p>\n";
			return;
		}

		/* get the column names */
		foreach ($data[0] as $column => &$value) {
			$insert_statement .= "`" . $column . "`, ";
		}

		/* remove extra comma and space */
		$insert_statement = trim_chars($insert_statement, 2);
		$insert_statement .= ") VALUES (";

		foreach ($data as &$row) {
			foreach ($row as $column => &$value) {
				$value_placeholder .= "?, ";

				/* add reference to the data to be inserted */
				$values[] = &$value;

				/* figure out what datatype the value is */
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

			/* remove extra comma and space */
			$insert_statement .= trim_chars($value_placeholder, 2);
			$insert_statement .= "), (";

			/* reset placeholder for next row of data */
			$value_placeholder = "";
		}

		/* remove extra comma, space and parenthasis */
		$insert_statement = trim_chars($insert_statement, 3);
		array_unshift($values, $value_types);

		/* tell the sql server to prepare the sql statent */
		$statement = $this->connection->prepare($insert_statement);
		if($this->verbose) {
			echo "<p>" . $insert_statement . "</p>";
		}

		/*
		 * This calls the `bind_param` function on the sql statement
		 * and the relevant data. This is done through the
		 * `call_user_func_array` function as we need to send a variable
		 * amount of data to the `bind_param` function and it does
		 * not inerintly allow this.
		 */
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

	/**
	 * Query the database for information.
	 *
	 * @param $table_name The name of the
	 * table you wish to insert the data into.
	 * @param $columns An array containing the names of
	 * the columns you would like to receive from the server.
	 * @param $conditions This defines the conditions of 'filters'
	 * of the data you want to receive, this should be an object
	 * with the keys being the names of the columns and the values
	 * being objects themselves. These inner-objects' keys should
	 * be a string representing the logical operator to be used for
	 * comparason and the values should be a value or column you
	 * that this column is to be compared to.
	 * @param $extra_arguments This should be an array of strings,
	 * each extra arguments that define the query but do not pertain
	 * the the conditions. Examples include "ORDER BY" and "GROUP BY".
	 *
	 * @return object An array of entries retreived from the database.
	 */
	public function select_from($table_name=null, $columns=null, $conditions=null, $extra_arguments=null) {
		if(!$table_name) {
			$this->quit(
				"You must provide the name of the table" .
				" you wish to insert the data into"
			);
		}

		if(!$columns) {
			$this->quit(
				"You must the columns you want to receive from the table"
			);
		}

		/*
		 * If no conditions are given, assume the user
		 * wants all the relavant data, thus we insert
		 * a condition that will return all data
		 */
		if(!$conditions) {
			$conditions = array('gameID' => array('>' => -1));
		}

		/*
		 * construct sql statement, this statement needs to be prapared to
		 * ensure it is safe, the user is able to specify some of the data
		 * that is received by this function and thus we need to make sure
		 * we protect the sql server.
		 */
		$query = "SELECT ";
		$query .= implode(", ", $columns);
		$query .= " FROM `" . $table_name . "` WHERE ";

		/*
		 * the prepare statement requires a string describing the
		 * data types of all data to be conditioned on.
		 */
		$value_types = "";

		/*
		 * The prepare statement needs the references
		 * of all data to be conditioned on.
		 */
		$values = array();

		foreach ($conditions as $column => &$condition) {
			foreach ($condition as $operation => &$value) {
				$query .= "`" . $column . "` $operation ? AND ";

				/* add reference to the data to be inserted */
				$values[] = &$value;

				/* figure out what datatype the value is */
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

		/* remove extra " AND " */
		$query = trim_chars($query, 5);

		/* prepend the data types to the data values array */
		array_unshift($values, $value_types);

		/* add extra arguments not related to conditionals */
		if($extra_arguments) {
			foreach ($extra_arguments as $argument) {
				$query .= " " . $argument;
			}
		}

		/* tell the sql server to prepare the sql statent */
		$statement = $this->connection->prepare($query);
		if(!$statement) {
			if($this->verbose) {
				echo "Error with prepare statement: " .
					$this->connection->error . "\n";
			}
		}

		/*
		 * This calls the `bind_param` function on the sql statement
		 * and the relevant data. This is done through the
		 * `call_user_func_array` function as we need to send a variable
		 * amount of data to the `bind_param` function and it does
		 * not inerintly allow this.
		 */
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

		/* reshape the data in a way the user can easily use it */
		$return_data = array();
		$result = $statement->get_result();
		while($entry = $result->fetch_assoc()) {
			$return_data[] = $entry;
		}

		$statement->close();
		return $return_data;
	}

	/**
	 * This function sends a sql statement directly to the server,
	 * this should not be used if the user is allowed to specify
	 * any of the data passed to this function. This function is
	 * only here in case the users needs are not already met with
	 * existing functionality of this interface.
	 *
	 * @param $sql A string of the sql statement to be sent to the server.
	 */
	public function sql($sql=null) {
		if(!$sql) {
			$this->quit(
				"You must enter a sql command that you want to send to the server"
			);
		}

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
		}
	}
}
?>
