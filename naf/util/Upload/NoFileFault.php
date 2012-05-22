<?php

/**
 * $Id: NoFileFault.php 184 2008-04-07 09:18:44Z vbolshov $
 * 
 * @package naf.util
 * @subpackage Upload
 * @copyright Victor Bolshov <crocodile2u@gmail.com>
 */

namespace naf\util\Upload;

class NoFileFault extends Fault {
	function __construct()
	{
		parent::__construct("No file has been uploaded");
	}
}
