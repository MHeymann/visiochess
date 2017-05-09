<?php
require_once 'utils.php';

use PHPUnit\Framework\TestCase;

class test_utils extends TestCase {

	/**
	* @dataProvider contains_provider
	*/
	function test_contains($string, $substring, $expected) {
		$this->assertEquals($expected, contains($string, $substring));
	}

	function contains_provider() {
		return [
			["hello", "hell", true],
			[" hello", "hel", true],
			["hello", "hell ", false],
			["hel lo", " ", true],
			["hello", "he ll", false],
			["", "hell", false],
			["", "", false],
			["hello", "", false]
		];
	}

	/**
	* @dataProvider stringify_provider
	*/
	function test_stringify($input, $expected) {
		$this->assertEquals($expected, stringify($input));
	}

	function stringify_provider() {
		return [
			[
				[
					'key' => 'value'
				],
				"key value"
			],
			[
				[
					'key' => 'value',
					'key2' => 'value2'
				],
				"key value, key2 value2"
			],
			[
				[],
				""
			],
			[
				['value'],
				"0 value"
			],
			[
				['value', 'key' => 'value'],
				"0 value, key value"
			],
			[
				['key' => 'value', 'value'],
				"key value, 0 value"
			],
			[
				['value', 'key' => 'value', 'value'],
				"0 value, key value, 1 value"
			]
		];
	}

	/**
	 * @dataProvider trim_chars_provider
	 */
	 function test_trim_chars($string, $num, $expected) {
		 $this->assertEquals($expected, trim_chars($string, $num));
	 }

	 function trim_chars_provider() {
	 	return [
			['hello', 2, 'hel'],
			[' what  what ', 5, ' what  '],
			['', 0, ''],
			['', 1, ''],
			['', 5, ''],
			['hell', 5, ''],
			['hell', 0, 'hell'],
			['hell', -1, 'hell']
		];
	 }
}
