<?php

/**
 * Abstract logger
 * 
 * $Id: AbstractLog.php 191 2008-04-08 07:12:13Z vbolshov $
 * 
 * @package naf\log
 */

namespace naf\log;
use \Exception;

abstract class ExceptionCatcher
{
	protected $exception;

	final function __construct(Exception $e)
	{
		$this->exception = $e;
	}

	abstract function run();
}