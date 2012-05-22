<?php

/**
 * Null-logger
 * 
 * $Id: TextLog.php 191 2008-04-08 07:12:13Z vbolshov $
 * 
 * @package naf\log
 */
namespace naf\log;

class ExceptionCatcher_Text extends ExceptionCatcher
{
	/**
	 * Displays exception information
	 */
	function run()
	{
		echo get_class($this->exception) . ":\n";
		echo $this->exception->getMessage() . "\n";
		echo $this->exception->getTraceAsString();
	}
}