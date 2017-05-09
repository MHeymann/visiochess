<?php
require_once 'validate.php';

use PHPUnit\FrameWork\TestCase;

class test_validate extends TestCase {

	/**
	 * @dataProvider invalid_year_provider
	 */
	public function test_invalid_years($year) {
		$expected = array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('year'), 'filter_on' => array());
		$result = validate_given_year($year);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider invalid_year_provider
	 */
	public function test_invalid_year_low($year) {
		$expected = array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('year-low'), 'filter_on' => array());
		$result = validate_year_low($year);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider invalid_year_provider
	 */
	public function test_invalid_year_high($year) {
		$expected = array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('year-high'), 'filter_on' => array());
		$result = validate_year_high($year);
		$this->assertEquals($expected, $result);
	}

	public function invalid_year_provider() {
		return [
			'Non-numeric string' => ['abcd'],
			'Negative year' => [-50],
			'Future year' => [2150]];
	}

	/**
	 * @dataProvider valid_year_provider
	 */
	public function test_valid_years($year) {
		$result = validate_given_year($year);
		$this->assertNull($result);
	}

	/**
	 * @dataProvider valid_year_provider
	 */
	public function test_valid_year_low($year) {
		$result = validate_year_low($year);
		$this->assertNull($result);
	}

	/**
	 * @dataProvider valid_year_provider
	 */
	public function test_valid_year_high($year) {
		$result = validate_year_high($year);
		$this->assertNull($result);
	}


	public function valid_year_provider(){
		return [
			['2001'],
			[1984],
			[2017],
			["2000"]];
	}

	/**
	 * @dataProvider valid_year_provider
	 */
	 public function test_invalid_low_high_years($yearlow) {
		$expected = array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('year-low>high'), 'filter_on' => array());
		$result = low_greater_than_high($yearlow, $yearlow - 42, 'year');
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider valid_year_provider
	 */
	public function test_validate_year_single_year_success($year) {
		$filter = array('year' => $year);
		$expected = array('error' => false, 'error_message' => '', 'error_fields' => array(), 'filter_on' => array('year'));
		$result = validate_year($filter);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider invalid_year_provider
	 */
	public function test_validate_year_single_year_failure($year) {
		$filter = array('year' => $year);
		$expected = array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('year'), 'filter_on' => array('year'));
		$result = validate_year($filter);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider valid_year_provider
	 */
	public function test_validate_low_high_success($year) {
		$filter = array('year' => null, 'year-low' => $year - 1, 'year-high' => $year);
		$expected = array('error' => false, 'error_message' => '', 'error_fields' => array(), 'filter_on' => array('year-low', 'year-high'));
		$result = validate_year($filter);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider valid_year_provider
	 */
	public function test_validate_low_high_failure($year) {
		$filter = array('year' => null, 'year-low' => $year, 'year-high' => $year - 1);
		$expected = array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('year-low>high'), 'filter_on' => array('year-low', 'year-high'));
		$result = validate_year($filter);
		$this->assertEquals($expected, $result);
	}

	/**
	 * @dataProvider elo_provider
	 */
	public function test_validate_elo_range($low, $high, $expected) {
		$type = 'black';
		$filter = array("$type-elo-low" => $low, "$type-elo-high" => $high);
		$result = validate_elo($filter, $type);
		$this->assertEquals($expected, $result);
	}

	public function elo_provider() {
		return [
			[2150, 2400, array('error' => false, 'error_message' => '', 'error_fields' => array(), 'filter_on' => array('black-elo-low', 'black-elo-high'))],
			[-50, 3400, array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('black-elo-low'), 'filter_on' => array('black-elo-low', 'black-elo-high'))],
			[1840, 3800, array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('black-elo-high'), 'filter_on' => array('black-elo-low', 'black-elo-high'))],
			[-1, 3401, array('error' => true, 'error_message' => 'validation error', 'error_fields' => array('black-elo-low', 'black-elo-high'), 'filter_on' => array('black-elo-low', 'black-elo-high'))]];
	}

	/**
	 * @dataProvider eco_cat_provider
	 */
	public function test_validate_eco_cat($cat, $expected) {
		$result = validate_eco_cat($cat);
		$this->assertEquals($expected, $result);
	}

	public function eco_cat_provider() {
		return [
			['A1.5', add_filter('eco-category', new_response())],
			['B2.2', add_filter('eco-category', new_response())],
			['G7. 1', add_filter('eco-category', add_error('eco-category: given G7. 1', new_response()))],
			['K26.5', add_filter('eco-category', add_error('eco-category: given K26.5', new_response()))],
			['B1.4', add_filter('eco-category', add_error('eco-category: given B1.4', new_response()))],
			['C3.1', add_filter('eco-category', add_error('eco-category: given C3.1', new_response()))],
			['E3.2' , add_filter('eco-category', add_error('eco-category: given E3.2', new_response()))]
		];
	}

	/**
	 * @dataProvider eco_class_provider
	 */
	public function test_validate_eco_class($class, $fail) {
		$expected = null;
		if ($fail) {
			$expected = new_response();
			$expected['error'] = true;
			$expected['error_message'] = 'validation error';
			$expected['error_fields'] = array('eco-class: given '. $class);
		}
		$result = validate_eco_class($class);
		$this->assertEquals($expected, $result);
	}

	public function eco_class_provider() {
		return [
			['A', false],
			['D', false],
			['Z', true],
			['K', true]];
	}

	/**
	 * @dataProvider filters_provider
	 */
	public function test_validate_filters($filters, $expected) {
		$result = validate_filters($filters);
		$this->assertEquals($expected, $result);
	}

	public function filters_provider() {
		return [
			[array('year' => null, 'year-low' => 1989, 'white-elo-low' => 2100, 'eco-filter-type' => ''), array('error' => false, 'error_message' => '', 'error_fields' => array(), 'filter_on' => array('year-low', 'white-elo-low'))]];
	}

}
?>
