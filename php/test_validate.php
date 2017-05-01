<?php
require_once 'validate.php';

use PHPUnit\FrameWork\TestCase;

class test_validate extends TestCase {

	/**
	 * @dataProvider invalid_year_provider
	 */
	public function test_invalid_years($year, $expected) {
		$this->assertEquals($expected, validate_year($year));
	}

	public function invalid_year_provider() {
		return [
			['2001', array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('year'))],
			[-50, array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('year'))],
			['2150', array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('year'))]];
	}
}
?>
