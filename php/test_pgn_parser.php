<?php
require_once 'pgn_parser.php';

use PHPUnit\Framework\TestCase;

class Test extends TestCase {

	// all functions with the "test_" prefix are automatically run
	public function test_sscan_tag() {
		$this->sscan_tag_trailing_space();

		// note that if the above test fails, this test will not run
		$this->sscan_tag_two_words();
	}

	// make sub functions to test each aspect of a function
	function sscan_tag_trailing_space() {
		$input = '[Event "Spring "]'; // comes directly from database
		$output = sscan_tag($input);
		$correct_output = "Spring";

		/*
		 * yes this test does fail, the sscan_tag function has a
		 * small bug, this is why testing is so important.
		 * I am going to leave the testers to figure out what the bug is
		 * and release a fix patch for it.
		 */
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
	}

	function get_longest_moves_string_dud1() {
		$file = '/test/dud1.pgn';
		$result = get_longest_moves_string($file);
		$expect = 481;
		$this->assertEquals($expect, $result);
	}
}
?>
