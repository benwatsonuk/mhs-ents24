<?php

/**
 * Created by PhpStorm.
 * User: benwatson
 * Date: 24/11/2016
 * Time: 16:50
 */

require('./classes/class-mhs-ents24-main.php');

class MHS_Ents24_MainTest extends PHPUnit_Framework_TestCase {

	private $main;

	protected function setUp() {
		$this->main = new MHS_Ents24_Main();
		$this->jsondata = file_get_contents('./tests/assets/BITresponse.json');
		$this->formatteddata = file_get_contents('./tests/assets/formatted_response.json');
	}

	protected function tearDown(){
		$this->main = NULL;
	}

	public function test_data_is_json () {
		$this->assertJson($this->jsondata);
	}

	public function test_format_the_data_formats_correctly () {
		$theResponse = $this->main->format_the_data(json_decode($this->jsondata));
		$this->assertEquals(json_encode($theResponse), $this->formatteddata);
	}

}
