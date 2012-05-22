<?php

require_once dirname(__FILE__) . '/../../naf/util/Pager.php';

use naf\util\Pager;

class PagerTest extends PHPUnit_Framework_TestCase {
	function testDefaultUrlGeneration()
	{
		// fixture
		$_GET = array('x' => 'y');
		$_SERVER['REQUEST_URI'] = '/some/page/';
		
		$pager = new Pager(10, 1, 10);
		
		$this->assertEquals($_SERVER['REQUEST_URI'] . '?x=y&amp;page=2', $pager->url(2));
	}
	
	function testCustomFormatUrlGeneration()
	{
		$pager = new Pager(10, 1, 10);
		$pager->setFormat('/blog/page-%d/');
		$this->assertEquals('/blog/page-2/', $pager->url(2));
	}
}