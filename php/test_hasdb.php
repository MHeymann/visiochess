<?php

/*Take a look at 'phpunit.xml' to set the test db name*/
require_once 'has_db.php';

use PHPUnit\Framework\TestCase;

class has_dbtest extends TestCase {
	private $conn = NULL;

	final public function get_connection() {
		if ($this->conn == NULL) {
			$this->conn = new PDO("mysql:host=" . $GLOBALS['DB_HOST'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASS']);
		}
		return $this->conn;
	}

	public function setUp() {
		$pdo = $this->get_connection();
		$sql = "DROP DATABASE " . $GLOBALS['DB_DBNAME'];
		$pdo->exec($sql);
		$_POST = array("hash" => $GLOBALS['DB_DBNAME']);
	}

	public function test_database_doesnt_exist() {
		$result = has_db_available($GLOBALS['DB_DBNAME']);
		$this->assertFalse($result);
	}

	public function test_database_exists() {
		$pdo = $this->conn;
		$sql = "CREATE DATABASE " . $GLOBALS['DB_DBNAME'];
		$pdo->exec($sql);

		$result = has_db_available($GLOBALS['DB_DBNAME']);
		$this->assertTrue($result);
	}
}
?>
