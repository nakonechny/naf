<?php

/**
 * Smart-logger. initializes either TextLog or Browser depending on SAPI
 * 
 * $Id$
 * 
 * @package naf\log
 */
namespace naf\log;

class ExceptionCatcher_Smart extends ExceptionCatcher
{
	/**
	 * Displays exception information
	 */
	function run()
	{
		if ((PHP_SAPI == 'cli') || ('POST' == @$_SERVER['REQUEST_METHOD']))
		{
			$logger = new ExceptionCatcher_Text($this->exception);
		} else {
			$logger = new ExceptionCatcher_Browser($this->exception);
		}
		
		return $logger->run();
	}
}