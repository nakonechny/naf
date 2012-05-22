<?php

/**
 * No permissions exception
 * 
 * $Id: ForbiddenError.php 184 2008-04-07 09:18:44Z vbolshov $
 * 
 * @package naf\err
 */

namespace naf\err;
use \Exception;

class ForbiddenError extends Exception {
	function exposeStatus()
	{
		header("HTTP/1.0 403 Forbidden", true, 403);
	}
}