<?php
/*
TODO:
Busy but necessary work:
Comment everything!

Features:
Add a verbose mode toggle
Add fatal params - if fatal is true, program quits on error
Add insert function
Add query function
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

  public function __construct($server, $user, $password, $port=null) {
    $this->server = $server;
    $this->user = $user;
    $this->password = $password;
    $this->port = $port;
  }

  private function quit($error_message = "") {
    echo "<p> " . $error_message . " </p>\n";
    $this->connection->close();
    die("<p> ---------- CONNECTION TERMINATED ---------- </p>\n");
  }

  public function connect() {
    $server = $this->server;
    if($port) {
      $server . ":" . $port;
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
      echo "<p> ----------    CONNECTION OPEN    ---------- </p>\n";
    }

    echo "<p> Successfully connected to: " . $server . " </p>\n";
  }

  public function disconnect() {
    $this->connection->close();
    echo "<p> ----------   CONNECTION CLOSED   ---------- </p>\n";
  }

  private function attempt($query) {
    // if(!$query) {
    //   $this->quit("You must enter a query to send to the database.");
    // }

    $q_is_ok = $this->connection->query($query);

    if($q_is_ok) { // these needs to be adapted to be able to return query info
      // if(gettype($status) == "object") {
      //   var_dump($status->fetch_all());
      // }
      return null; // successful query
    } else {
      // if(gettype($status) == "object") {
      //   var_dump($status->fetch_all());
      // }
      return $this->connection->error; // unsuccessful query
    }
  }

  public function create_database($name, $replace=false) {
    if(!$name) {
      $this->quit("You must enter a name for the database you want to create");
    }

    $sql = "CREATE DATABASE " . $name . ";";
    $error = $this->attempt($sql);

    if($error) {
      if(self::contains($error, "database exists")) {
        if($replace) {
          echo "<p> " . $name .
            " database already exists, it will be deleted and replaced </p>\n";
          $this->delete_database($name);
          $this->create_database($name);
          return;
        }
      }

      $this->quit(
        "Could not create database: " . $name . "\n" .
        "Error: " . $error
      );
    } else {
      echo "<p> " . $name . " database created successfully </p>\n";
    }
  }

  public function delete_database($name) {
    if(!$name) {
      $this->quit("You must enter a name for the database you want to delete");
    }

    $sql = "DROP DATABASE " . $name . ";";
    $error = $this->attempt($sql);

    if($error) {
      $this->quit(
        "Could not delete database: " . $name . "\n" .
        "Error: " . $error
      );
    } else {
      echo "<p> " . $name . " database deleted successfully </p>\n";
    }
  }

  public function use_database($name) {
    if(!$name) {
      $this->quit("You must enter a name for the database you want to use");
    }

    // could actually take this out and append $name to each query
    $sql = "USE " . $name . ";";
    $error = $this->attempt($sql);

    if($error) {
      $this->quit(
        "Could not use database: " . $name . "\n" .
        "Error: " . $error
      );
    } else {
      $this->database = $name;
      echo "<p> The " . $name . " database will now be used </p>\n";
    }
  }

  //_________________________________________________________
  // remember to add db if using
  public function create_table($name, $structure, $replace=false) {
    if(!$name) {
      $this->quit("You must enter a name for the table you want to create");
    }

    if(!$structure) {
      $this->quit("You must define a structure for the table you want to create");
    }

    $sql = "CREATE TABLE " . $name . "(" . self::stringify($structure) . ");";
    $error = $this->attempt($sql);

    if($error) {
      if(self::contains($error, "table exists")) {
        if($replace) {
          echo "<p> " . $name .
            " table already exists, it will be deleted and replaced </p>\n";
          $this->delete_table($name);
          $this->create_table($name, $structure);
          return;
        }
      }

      $this->quit(
        "Could not create table: " . $name . "\n" .
        "Error: " . $error
      );
    } else {
      echo "<p> " . $name . " table created successfully </p>\n";
      // $this->sql("describe " . $name . ";");
    }
  }

  // remember to add db name is using
  public function delete_table($name=null) {
    if(!$name) {
      $this->quit("You must enter a name for the table you want to delete");
    }

    $sql = "DROP TABLE " . $name . ";";
    $error = $this->attempt($sql);

    if($error) {
      $this->quit(
        "Could not delete table: " . $name . "\n" .
        "Error: " . $error
      );
    } else {
      echo "<p> " . $name . " table deleted successfully </p>\n";
    }
  }

  // send a sql command to the server
  // to handle cases that are not yet catered for
  public function sql($sql) {
    if(!$sql) {
      $this->quit(
        "You must enter a sql command that you want to send to the server"
      );
    }

    // could actually take this out and append $name to each query
    $error = $this->attempt($sql);
    if($error) {
      $this->quit(
        "Could not execute command\n" .
        "Error: " . $error
      );
    } else {
      echo "<p> The command was successfully executed </p>\n";
      // var_dump($this->connection);
    }
  }

  // this really has no busines here, it should actually be in a utils.php
  private static function contains($string, $token) {
    if(strpos($string, $token)) {
      return true;
    } else {
      return false;
    }
  }

  private static function stringify($object) {
    $string = "";
    foreach($object as $key => $value) {
      $string .= $key . " " . $value . ", ";
    }
    $string = substr($string, 0, count($string) - 3);
    return $string;
  }
}
?>
