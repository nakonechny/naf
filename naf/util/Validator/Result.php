<?php

/**
 * Encapsulates validation result
 * 
 * $Id: Result.php 184 2008-04-07 09:18:44Z vbolshov $
 * 
 * @package naf\util
 */

namespace naf\util;

class Validator_Result {
	
	/**
	 * Collected errors
	 *
	 * @var array
	 */
	protected $_errors = array();
	
	/**
	 * Validated/filtered data
	 *
	 * @var array
	 */
	protected $_data;
	
	/**
	 * Raw data
	 *
	 * @var array
	 */
	protected $_raw;
	
	/**
	 * Check whether result contains errors
	 *
	 * @return bool TRUE if there were no errors collected, FALSE otherwise
	 */
	function ok()
	{
		return 0 == count($this->_errors);
	}
	
	/**
	 * An opposite to ok()
	 */
	function isError()
	{
		return 0 != count($this->_errors);
	}
	
	/**
	 * Reset data, raw-data, errors
	 */
	function reset()
	{
		$this->_data = null;
		$this->_errors = array();
	}

	/**
	 * Add an error message
	 *
	 * @param string $key
	 * @param string $error
	 * @return Naf_Validator_Result $this
	 */
	function addError($key, $error)
	{
		$this->_errors[$key] = $error;
		return $this;
	}
	
	/**
	 * Export error-list
	 *
	 * @param bool $preserveKeys Whether to preserve keys in errors array. This parameter has been
	 * 								introduced for backwards compatibility and is FALSE by default - 
	 * 								so when you call this method without arguments, a numeric array will be returned,
	 * 								and you must specify TRUE as arguments to retrieve a hash of errors.
	 * @return array
	 */
	function getErrorList($preserveKeys = false)
	{
		if ($preserveKeys)
		{
			return (array) $this->_errors;
		} else {
			return array_values((array) $this->_errors);
		}
	}
	
	/**
	 * Import data array
	 *
	 * @param array $data
	 */
	function import($data)
	{
		$this->_data = $data;
	}
	
	/**
	 * Export data
	 *
	 * @return array
	 */
	function export($preserveKeys = false)
	{
		return (array) $this->_data;
	}
	
	/**
	 * Import/export raw (UNfiltered) data - which could be useful in case of an error.
	 * Thanks to Henry <007_id at sbcglobal.net> for suggesting this feature.
	 * 
	 * @param array $rawData
	 */
	function importRaw(array $data)
	{
		$this->_raw = $data;
	}
	
	/**
	 * @return array
	 */
	function exportRaw()
	{
		return (array) $this->_raw;
	}
}