<?php

/**
 * This is a simple unit-testing framework.
 *
 * $Id: NafUnit.php 159 2008-04-06 04:50:25Z vbolshov $
 */

class NafUnit {
	
	private $total, $totalAsserts, $ok, $okAsserts, $failed, $failedAsserts, $messages;
	
	private $filename;
	
	final function __construct()
	{
		$r = new ReflectionClass($this);
		$this->filename = $r->getFileName();
	}
	
	function setUp()
	{}
	
	function tearDown()
	{}
	/**
	 * runs the testcase
	 */
	final function run()
	{
		$this->total = $this->ok = $this->failed = $this->totalAsserts = $this->okAsserts = $this->failedAsserts = 0;
		$this->messages = array();
		foreach (get_class_methods($this) as $m)
		{
			if ($this->isTestMethod($m))
			{
				++$this->total;
				try {
					
					$failedBefore = $this->failedAsserts;
					
					$this->setUp();
					
					$this->$m();
					
					$this->tearDown();
					
					if ($this->failedAsserts > $failedBefore)
					{
						$this->failed++;
					} else {
						$this->ok++;
					}
					
				} catch (Exception $e) {
					$this->fail(get_class($e) . ' with message ' . $e->getMessage() . PHP_EOL .
						implode(PHP_EOL . "\t", explode(PHP_EOL, $e->getTraceAsString())));
				}
			}
		}
		
		if (! $this->failed)
		{
			$this->ok = 'all';
		}
		
		if (PHP_SAPI != 'cli')
		{
			$background = $this->failed ? 'red' : 'darkgreen';
			print "<div style='background:" . $background . "'>&nbsp;</div><pre>";
		}
		
		print $this->failed ? "FAILED!" : "OK";
		print " - " . get_class($this);
		print PHP_EOL;
		print $this->total . " test methods, {$this->ok} ok";
		if ($this->failed) {
			print ", {$this->failed} failed";
		}
		print ", {$this->okAsserts} correct assertions";
		if ($this->failed) {
			print ", {$this->failed}, {$this->failedAsserts} failed";
		}
		print PHP_EOL;
		print implode(PHP_EOL, $this->messages);
		
		if (PHP_SAPI == 'cli')
		{
			print PHP_EOL;
		} else {
			print "</pre>";
		}
		
	}
	
	final function assert($expr, $message = null)
	{
		if ($expr)
		{
			$this->okAsserts++;
		} else {
			$this->failedAsserts++;
			if (! $message) $message = "Assertion failed";
			$this->messages[] = $this->message($message);
		}
	}
	
	final function assertEqual($arg1, $arg2, $message = "Equal assertion fails")
	{
		$message .= ' as ' . var_export($arg1, 1) . ' !=' . var_export($arg2, 1);
		$this->assert($arg1 == $arg2, $message);
	}
	
	final function assertIdentical($arg1, $arg2, $message = "Identical assertion fails")
	{
		$message .= ' as ' . var_export($arg1, 1) . ' !==' . var_export($arg2, 1);
		$this->assert($arg1 === $arg2, $message);
	}
	
	final function assertNull($arg, $message = "NULL assertion fails")
	{
		$message .= ' as ' . var_export($arg, 1) . ' is not NULL';
		$this->assert($arg === null, $message);
	}
	
	final function assertNotNull($arg, $message = "NOT NULL assertion fails")
	{
		$this->assert($arg !== null, $message);
	}
	
	final function assertIsA($arg, $class, $message = "\"Is-a\" assertion fails")
	{
		$message .= ' as ' . var_export($arg, 1) . ' is not an instance of '. $class;
		$this->assert($arg instanceof $class, $message);
	}
	
	final protected function message($preamble = "Failure")
	{
		foreach (debug_backtrace() as $entry)
		{
			if (($entry['file'] == $this->filename))
			{
				break;
			}
		}
		if (empty($entry['function'])) $entry['function'] = 'Unknown';
		if (empty($entry['file'])) $entry['file'] = 'Unknown';
		if (empty($entry['line'])) $entry['line'] = 'Unknown';
		return "$preamble; {$entry['function']}, {$entry['file']} ({$entry['line']})";
	}
	
	final private function isTestMethod($m)
	{
		return 0 === strpos($m, 'test');
	}
	
	final function fail($message)
	{
		$this->failedAsserts++;
		$this->messages[] = $message;
	}
}