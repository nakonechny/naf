<?php

/**
 * $Id: ActiveRecordTest.php 184 2008-04-07 09:18:44Z vbolshov $
 */

/**
 * prerequisites
 */

require_once dirname(__FILE__) . '/../../NafUnit.php';
require_once dirname(__FILE__) . '/../../naf/db/ActiveRecord.php';
require_once dirname(__FILE__) . '/../../naf/db/Select.php';
require_once dirname(__FILE__) . '/../../naf/util/Validator.php';
require_once dirname(__FILE__) . '/../../naf/util/Validator/Result.php';

use naf\db\ActiveRecord;

class Test extends ActiveRecord {
	static protected $table = 'activerecordtest';
	static protected $defaults = array(
		'name' => null
	);
	protected function _createValidator()
	{
		return parent::_createValidator()
			->addRequired('name', 'name is required')
			->addStringRule('name', 'name should be a string');
	}
}

class ActiveRecordTest extends NafUnit {
	private $pdo;
	
	function testTableMethods()
	{
		$id = $this->addRow();
		
		$updatedRow = array('name' => 'test updated', 'id' => $id);
		$this->assert(Test::update($updatedRow) === $id, "Update failed");
		$this->assert(Test::find($id)->export() == $updatedRow, "Test::find failed");
		
		$s = Test::findAll();
		$sc = $s->count();
		$this->assert($s->count() == 1, "Test::findAll(NO ARGUMENTS) failed (incorrect count $sc)");
		$sf = $s->export()->fetch(PDO::FETCH_ASSOC);
		$this->assert($s->export()->fetch(PDO::FETCH_ASSOC) == $updatedRow, "Test::findAll(NO ARGUMENTS) failed (incorrect row " . var_export($sf, 1) . ")");
		
		$s = Test::findAll(array('name = ?' => $updatedRow['name']));
		$sc = $s->count();
		$this->assert($s->count() == 1, "Test::findAll(EXISTING_ROW) failed (incorrect count $sc)");
		$sf = $s->export()->fetch(PDO::FETCH_ASSOC);
		$this->assert($sf == $updatedRow, "Test::findAll(EXISTING_ROW) failed (incorrect row " . var_export($sf, 1) . ")");
		
		$s = Test::findAll(array('name = ?' => $updatedRow['non-existing']));
		$sc = $s->count();
		$this->assert($s->count() == 0, "Test::findAll(NON_EXISTING_ROW) failed (incorrect count $sc)");
		$sf = $s->export()->fetch(PDO::FETCH_ASSOC);
		$this->assert($sf == false, "Test::findAll(NON_EXISTING_ROW) failed (incorrect row " . var_export($sf, 1) . ")");
	}
	
	function testRowMethods()
	{
		$id = $this->addRow();
		
		$tr = new Test();
		$loaded = $tr->load($id);
		$this->assert($updatedRow = $loaded, "Test->load() failed: " . var_export($loaded, 1));
		
		$exp = $tr->export();
		$this->assert($updatedRow = $exp, "Test->export() failed: " . var_export($exp, 1));
		
		$newname = 'test updated again';
		$tr->name = $newname;
		$save = $tr->save();
		$this->assert($save, "Test->save() failed: " . var_export($tr->getErrorList(), 1));
		$this->assert(Test::count() == 1, "Test::count() failed");
		
		$tr->name = null;
		$this->assert(! $tr->save(), "Test->save() should have failed (required name rule should fail)!");
		
		$tr->name = array('123');
		$this->assert(! $tr->save(), "Test->save() should have failed (string name rule should fail)!");
		
		$tr2 = new Test();
		$tr2->name = '000 second row';// three zeroes - to ensure it sorts above the first one
		$this->assert($tr2->save(), 'Test->save() failed: ' . var_export($tr->getErrorList(), 1));
		
		$s = Test::findAll();
		$s->setOrder('name');
		$s->paginate(1, 1);
		$all = $s->export()->fetchAll();
		$this->assert(1 == count($all), 'Select->count() failed after pagination');
		$this->assert($tr2->name == $all[0]->name, 'Select->export() failed ordering');
	}
	
	function setUp()
	{
		if (null === $this->pdo)
		{
			$this->pdo = new PDO("mysql:host=localhost;dbname=test", "root");
			ActiveRecord::setConnection($this->pdo);
			ActiveRecord::setFetchModeClass();
		}
		$this->pdo->query("DROP TABLE activerecordtest");
		$this->pdo->query("CREATE TABLE activerecordtest (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
			name VARCHAR(255))");
	}
	function tearDown()
	{
		$this->pdo->query("DROP TABLE activerecordtest");
	}
	
	private function addRow()
	{
		$ret = Test::insert(array('name' => 'test'));
		$this->assert($ret, "INSERT FAILED");
		return $ret;
	}
}