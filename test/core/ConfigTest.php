<?php

require_once '../../naf/core/Config.php';
use naf\core\Config;

//require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Config test case.
 */
class ConfigTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Config
	 */
	private $Config;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp() {
		parent::setUp ();

		$this->Config = new Config(/* parameters */);

	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown() {
		// TODO Auto-generated ConfigTest::tearDown()


		$this->Config = null;

		parent::tearDown ();
	}

	/**
	 * Constructs the test case.
	 */
	public function __construct() {
		// TODO Auto-generated constructor
	}

	/**
	 * Tests Config::loadFromFile()
	 */
	public function testLoadFromFile() {
		// TODO Auto-generated ConfigTest::testLoadFromFile()
		$this->markTestIncomplete ( "loadFromFile test not implemented" );

		Config::loadFromFile(/* parameters */);

	}

	/**
	 * Tests Config->import()
	 */
	public function testImport() {
		// TODO Auto-generated ConfigTest->testImport()
		$this->markTestIncomplete ( "import test not implemented" );

		$this->Config->import(/* parameters */);

	}

	/**
	 * Tests Config->__get()
	 */
	public function test__get() {
		// TODO Auto-generated ConfigTest->test__get()
		$this->markTestIncomplete ( "__get test not implemented" );

		$this->Config->__get(/* parameters */);

	}

}

