<?php

/**
 * File uploads handler.
 * Doesn't handle files uploaded from inputs named like "userfile[]" (i. e. "arrays") !!!
 * 
 * $Id: Upload.php 269 2008-09-24 15:15:13Z vbolshov $
 * 
 * @package naf.util
 * @subpackage Upload
 * @copyright Victor Bolshov <crocodile2u@gmail.com>
 */

namespace naf\util;
use naf\util\Upload\Fault;

class Upload {
	
	/**#@+
	 * Upload details
	 *
	 * @var string | int
	 */
	protected $_name;
	protected $_type;
	protected $_size;
	protected $_tmpName;
	protected $_error;
	/**#@-*/
	
	/**
	 * Allowed extensions
	 *
	 * @var array
	 */
	protected $_extensions = array();
	
	const CHECKNAME_REGEXP = '/^[a-z0-9\-_\.\(\)\[\]\~]+$/i';
	
	function __construct($name)
	{
		if (array_key_exists($name, $_FILES))
		{
			$this->_name = $_FILES[$name]['name'];
			$this->_type = $_FILES[$name]['type'];
			$this->_size = $_FILES[$name]['size'];
			$this->_tmpName = $_FILES[$name]['tmp_name'];
			$this->_error = $_FILES[$name]['error'];
		}
		else
		{
			$this->_name = '';
			$this->_type = '';
			$this->_size = 0;
			$this->_tmpName = '';
			$this->_error = UPLOAD_ERR_NO_FILE;
		}
	}
	
	/**
	 * Add allowed extension
	 *
	 * @param string $ext1
	 * ...
	 * @param string $extN
	 * @return void
	 */
	function addExtension($ext1)
	{
		$new = func_get_args();
		$new = array_map('strtolower', $new);
		$this->_extensions = array_unique(array_merge($this->_extensions, $new));
	}
	
	/**
	 * @return string basename
	 */
	function getName()
	{
		return $this->_name;
	}
	
	/**
	 * @return string tmpname
	 */
	function getTmpName()
	{
		return $this->_tmpName;
	}
	
	/**
	 * @return string
	 */
	function getExtension()
	{
		return substr($this->_name, strrpos($this->_name, '.') + 1, strlen($this->_name));
	}
	
	function imageSize()
	{
		return getimagesize($this->_tmpName);
	}
	
	function move($destFolder, $name = null)
	{
		if (! $this->ok())
			$this->_exception();
		
		if (null === $name)
			$name = $this->_name;
		
		$destination = rtrim($destFolder, '/ ') . '/' . $name;
		if (! @move_uploaded_file($this->_tmpName, $destination))
			throw new Fault('File upload failed! Destination folder/file not writable?');
		
		return $destination;
	}
	
	function checkName()
	{
		return preg_match(self::CHECKNAME_REGEXP, $this->_name);
	}
	
	function checkNameAllowedSymbols()
	{
		return str_replace("\\", "", substr(self::CHECKNAME_REGEXP, 3, strlen(self::CHECKNAME_REGEXP) - 3 - 5));
	}
	
	function ok($exception = false)
	{
		if (UPLOAD_ERR_OK !== $this->_error)
		{
			if ($exception) $this->_exception();
			else return false;
		}
		elseif (count($this->_extensions) && 
			! in_array(strtolower($this->getExtension()), $this->_extensions))
		{
			throw new Fault('Filename extension not allowed');
		}
		else
			return true;
	}
	
	protected function _exception()
	{
		switch ($this->_error)
		{
			case UPLOAD_ERR_NO_FILE:
				throw new Upload\NoFileFault();
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = 'Failed to write file to disk';
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = '';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
				break;
			case UPLOAD_ERR_INI_SIZE:
				$message = 'The uploaded file exceeds the upload_max_filesize directive (' . ini_get("upload_max_filesize") . ') in php.ini';
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = 'Missing a temporary folder';
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = 'The uploaded file was only partially uploaded';
				break;
			default:
				$message = 'Unknown error';
				break;
		}
		
		throw new Fault($message);
	}
}
