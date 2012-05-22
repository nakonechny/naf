<?php

/**
 * Object not found exception
 * 
 * $Id: NotFoundError.php 184 2008-04-07 09:18:44Z vbolshov $
 * 
 * @package naf\err
 */

namespace naf\err;
use \Exception;

class NotFoundError extends Exception {
	function exposeStatus()
	{
		header("HTTP/1.0 404 Not Found", true, 404);
	}
}