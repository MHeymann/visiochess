<?php
require_once 'eco_category.php';

use PHPUnit\Framework\TestCase;

class test_eco extends TestCase {

	/**
	 * @dataProvider valid_eco_provider
	 */
	public function test_category_valid_ecos($eco_alpha, $eco_numero, $expected) {
		$this->assertEquals($expected, get_eco_category($eco_alpha, $eco_numero));
	}

	public function valid_eco_provider() {
		return [
			['A', '40', 'A1.2'],
			['D', '5', 'D4.1'],
			['B', '33', 'B2.2'],
			['C', '4', 'C3.1'],
			['A', '79', 'A1.4']];
	}
	/**
	 * @dataProvider invalid_eco_provider
	 */
	public function test_category_invalid_ecos($eco_alpha, $eco_numero, $expected) {
		$this->assertEquals($expected, get_eco_category($eco_alpha, $eco_numero));
	}

	public function invalid_eco_provider() {
		return [
			'eco with zero' => ['E', '0', 'blamo'],
			'eco with negative' => ['A', '-45', 'blamo'],
			'eco with invalid category' => ['F', '34', 'blamo'],
			'eco above max range' => ['B', '999', 'blamo']];
	}

	/**
	 * @dataProvider valid_class_provider
	 */
	public function test_class_valid_category($eco_category, $expected) {
		$this->assertEquals($expected, get_eco_class($eco_category));
	}

	public function valid_class_provider() {
		return [
			['A1.2', array('category' => 'A', 'low' => 40, 'high' => 44)],
			['A1.4', array('category' => 'A', 'low' => 50, 'high' => 79)],
			['E5.2', array('category' => 'E', 'low' => 60, 'high' => 99)],
			['B2.2', array('category' => 'B', 'low' => 20, 'high' => 99)]];
	}

	/**
	 * @dataProvider invalid_class_provider
	 */
	public function test_class_invalid_category($eco_category, $expected) {
		$this->assertEquals($expected, get_eco_class($eco_category));
	}

	public function invalid_class_provider() {
		return [
			['A0.4', null],
			['G7.1', null],
			['B2.9', null],
			['D-4.1', null],
			['A1.-2', null]];
	}
}
?>
