<?php

/**
 * Authorization-required exception
 * 
 * $Id: UnauthorizedError.php 184 2008-04-07 09:18:44Z vbolshov $
 * 
 * @package naf\err
 */

namespace naf\err;
use \Exception;

class UnauthorizedError extends Exception {
	function exposeStatus()
	{
		header("HTTP/1.0 401 Unauthorized", true, 401);
	}
}