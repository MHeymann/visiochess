<?php
require_once 'pgn_parser.php';

use PHPUnit\Framework\TestCase;

class test_pgn_parser extends TestCase {

	// all functions with the "test_" prefix are automatically run
	public function test_sscan_tag() {
		$this->sscan_tag_trailing_space();

		// note that if the above test fails, this test will not run
		$this->sscan_tag_two_words();
	}

	// make sub functions to test each aspect of a function
	function sscan_tag_trailing_space() {
		$input = '[Event "Spring "]';
		$output = sscan_tag($input);
		$correct_output = "Spring";

		$this->assertEquals($correct_output, $output);
	}

	function sscan_tag_two_words() {
		$input = '[Site "Budapest open"]';
		$output = sscan_tag($input, '[Site "');
		$correct_output = "Budapest open";

		$this->assertEquals($correct_output, $output);
	}

	public function test_get_longest_moves_string() {
		$this->get_longest_moves_string_dud1();
		$this->get_longest_moves_string_dud4();
	}

	function get_longest_moves_string_dud1() {
		$file = '/test/dud1.pgn';
		$result = get_longest_moves_string($file);
		$expect = 481;
		$this->assertEquals($expect, $result);
	}

	function get_longest_moves_string_dud4() {
		$file = '/test/dud4.pgn';
		$result = get_longest_moves_string($file);
		$expect = 424;
		$this->assertEquals($expect, $result);
	}

	function test_evaluate_line() {
		$this->evaluate_with_punctuation();
	}

	function evaluate_with_punctuation() {
		$string = "Hello, darkness my old friend...";
		$should_start = "Hello,";
		$this->assertEquals(true, evaluate_line($string, $should_start));

		$string = "Hello, darkness my old friend...";
		$should_start = "Hello";
		$this->assertEquals(true, evaluate_line($string, $should_start));

		$string = "Hello, darkness my old friend...";
		$should_start = ".Hello";
		$this->assertEquals(false, evaluate_line($string, $should_start));

		$string = "  Hello, darkness my old friend...";
		$should_start = "Hello";
		$this->assertEquals(false, evaluate_line($string, $should_start));
	}
}
?>
