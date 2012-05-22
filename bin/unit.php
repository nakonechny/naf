<?php

/**
 * Unit tests runner
 * 
 * $Id: unit.php 184 2008-04-07 09:18:44Z vbolshov $
 * 
 * @copyright Victor Bolshov <crocodile2u@gmail.com>
 */

/************************* <CONFIGURATION> **************************/

$unitTestBaseClass = 'NafUnit';
$unitTestRunMethod = 'run';
$unitTestFilenameRegexp = '/Test\.php$/';

/************************* </CONFIGURATION> **************************/

// read args
$thisfile = basename(__FILE__);
$args = array();
$usage = "Usage: php $thisfile f=<UNIT_TEST_CASE_FILE> [c=<SETUP_FILE_NAME>]\n";

foreach ($_SERVER['argv'] as $arg)
{
	if ((1 === strpos($arg, '=')) && (strlen($arg) > 2))
	{
		list($key, $val) = explode("=", $arg, 2);
		$args[$key] = $val;
	}
}

if (empty($args['f']))
{
	echo 'ERROR: testcase file not set'."\n";
	echo $usage;
	exit();
}

function check_file($name, $desc) {
	global $usage;
	// check for valid filename
	if (! is_file($name))
	{
		echo 'ERROR (' . $desc . '): "' . $name . '" is not a file'."\n";
		echo $usage;
		exit();
	}
	
	if (! is_readable($name))
	{
		echo 'ERROR (' . $desc . '): "' . $name . '" is not readable'."\n";
		echo $usage;
		exit();
	}
}

check_file($args['f'], 'test case file');

if (! empty($args['c']))
{
	check_file($args['c'], 'configuration file');
}

$classes_old = get_declared_classes();
echo "\n-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n\nRunning all in {$args['f']}\n";
ob_start();
include_once $args['f'];
foreach (array_diff(get_declared_classes(), $classes_old) as $newclass)
{
	$r = new ReflectionClass($newclass);
	if ($r->isSubclassOf($unitTestBaseClass))
	{
		echo "$newclass:\n";
		$testcase = new $newclass();
		$testcase->$unitTestRunMethod();
	}
}
ob_end_flush();
echo "\n-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-\n";