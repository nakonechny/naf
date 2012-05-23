<?php

require_once dirname(__FILE__) . '/../Naf.php';
use \Naf;

class NafTest extends PHPUnit_Framework_TestCase
{
    function testConfigRetrieve()
    {
        Naf::importConfig(array(
            'sec1' => array(
                'sec2' => array(
                    'val1' => 1,
                ),
                'sec3' => array(
                    'val2' => 2,
                ),
            ),
            'sec4.val3' => '3',
        ));
        $this->assertEquals(Naf::config('sec1.sec2.val1'), 1);
        $this->assertEquals(Naf::config('sec1.sec3.val2'), 2);
        $this->assertEquals(Naf::config('sec4.val3'), 3);
        $this->assertEquals(Naf::config('sec1.sec2'), array('val1' => 1));
        $this->assertNull(Naf::config('nonexistent'));
        $this->assertNull(Naf::config('sec1.nonexistent'));
    }
}