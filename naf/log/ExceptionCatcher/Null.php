<?php
/**
 * Null-logger
 * 
 * $Id: NullLog.php 188 2008-04-08 07:03:13Z vbolshov $
 * 
 * @package naf\log
 */
namespace naf\log;

class ExceptionCatcher_Null extends ExceptionCatcher
{
	function run()
	{
		// do nothing
	}
}