<?php

/**
 * naf\util\Validator class (Validator.php, Wed Jan 10 12:32:57 MSK 2007 12:32:57)
 * Simple validation of input data, based on filter extension for PHP.
 * Filter extension is enabled by default since php 5.2.0
 * 
 * Validator supports fluent interface ( http://www.martinfowler.com/bliki/FluentInterface.html )
 * 
 * example of usage:
 * $v = new Validator();
 * $v->addRequired('domain', 'Domain field is required')
 *		->addRule('domain', 
 *			array('filter' => FILTER_VALIDATE_URL, 'flags' => array(FILTER_FLAG_HOST_REQUIRED)), 
 *			'Domain must be a valid URL')
 *		->addRule('domain', 
 *			array('filter' => FILTER_CALLBACK, 'options' => array($this, 'filterUniqueDomain')), 
 *			'Domain name must be unique within the database')
 *		->addRule('enabled', FILTER_VALIDATE_BOOLEAN, '"Enabled" field must be a boolean value');
 *
 *	$vResult = $v->check($MY_INPUT_DATA);// $MY_INPUT_DATA is supposed to be an associative array (like $_POST or $_GET)
 *	var_dump($vResult->ok());
 *	var_dump($vResult->export());
 *	var_dump($vResult->getErrorList());
 * 
 * @package naf\util
 * @subpackage Validator
 */

namespace naf\util;

class Validator {
	
	/**#@+
	 * @var array
	 */
	protected $_rules = array();
	protected $_messages = array();
	protected $_required = array();
	protected $_equals = array();
	protected $_validated = array();
	/**#@-*/
	
	/**
	 * @var Validator_Result
	 */
	protected $_result;
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->_result = new Validator_Result();
	}
	
	/**
	 * Add required field
	 *
	 * @param string $key
	 * @param string $message
	 * @return Validator
	 */
	function addRequired($key, $message)
	{
		$this->_validated[$key] = null;
		$this->_required[$key] = $message;
		return $this;
	}
	
	/**
	 * Add equal fields. Useful for password confirmation.
	 *
	 * @param string $key1
	 * @param string $key2
	 * @param string $message
	 * @return Validator
	 */
	function addEquals($key1, $key2, $message)
	{
		$this->_equals[] = array($key1, $key2, $message);
		$this->_validated[$key1] = null;
		$this->_validated[$key2] = null;
		return $this;
	}
	
	/**
	 * Add rule
	 *
	 * @param string $key
	 * @param array | int filter @see filter_* functions ( http://php.net/filter )
	 * @param string $message
	 * @return Validator
	 */
	function addRule($key, $filter, $message)
	{
		$this->_validated[$key] = null;
		foreach ($this->_rules as $index => $stack)
			if (! array_key_exists($key, $stack))
				return $this->_doAddRule($key, $filter, $message, $index);
		
		return $this->_doAddRule($key, $filter, $message, null);
	}
	
	/**
	 * @param string $key
	 * @param string $message
	 * @param int $flags
	 * @return Validator
	 */
	function addStringRule($key, $message, $flags = null)
	{
		if (null === $flags)
			$flags = FILTER_FLAG_STRIP_LOW;
		
		return $this->addRule($key, array('filter' => FILTER_SANITIZE_STRING, 'flags' => $flags), $message);
	}
	
	/**
	 * @param string $key
	 * @param string $message
	 * @param int $flags
	 * @return Validator
	 */
	function addRawStringRule($key, $message, $flags = null)
	{
		return $this->addRule($key, array('filter' => FILTER_UNSAFE_RAW, 'flags' => $flags), $message);
	}
	
	/**
	 * @param string $key
	 * @param string $message
	 * @param string $regexp
	 * @return Validator
	 */
	function addRegexpRule($key, $message, $regexp)
	{
		return $this->addRule($key, 
			array('filter' => FILTER_VALIDATE_REGEXP, 'options' => array('regexp' => $regexp)), 
			$message);
	}
	
	/**
	 * @param string $key
	 * @param string $message
	 * @return Validator
	 */
	function addEmailRule($key, $message)
	{
		return $this->addRule($key, FILTER_VALIDATE_EMAIL, $message);
	}
	
	/**
	 * @param string $key
	 * @param string $message
	 * @return Validator
	 */
	function addUrlRule($key, $message)
	{
		return $this->addRule($key, FILTER_VALIDATE_URL, $message);
	}
	
	/**
	 * @param string $key
	 * @param string $message
	 * @return Validator
	 */
	function addIntegerRule($key, $message, $options = null)
	{
		return $this->addRule($key, array('filter' => FILTER_VALIDATE_INT, 'options' => $options), $message);
	}
	
	/**
	 * @param string $key
	 * @param string $message
	 * @return Validator
	 */
	function addFloatRule($key, $message, $flags = null)
	{
		return $this->addRule($key, array('filter' => FILTER_VALIDATE_FLOAT, 'flags' => $flags), $message);
	}
	
	/**
	 * @param string $key
	 * @param string $message
	 * @return Validator
	 */
	function addBooleanRule($key, $message)
	{
		return $this->addRule($key, FILTER_VALIDATE_BOOLEAN, $message);
	}
	
	/**
	 * @param string $key
	 * @param callback $callback
	 * @param string $message
	 * @return Validator
	 */
	function addCallbackRule($key, $callback, $message)
	{
		return $this->addRule($key, array('filter' => FILTER_CALLBACK, 'options' => $callback), $message);
	}
	
	/**
	 * @param string $key
	 * @param callback $callback
	 * @param string $message
	 * @return Validator
	 */
	function addDateRule($key, $message)
	{
		return $this->addRule($key, array('filter' => FILTER_CALLBACK, 'options' => array($this, '_valiDate')), $message);
	}
	
	/**
	 * Validate string $value as a date specification
	 *
	 * @param string $value
	 * @return string or bool FALSE on failure
	 */
	protected function _valiDate($value) {

		if (! ($timestamp = strtotime($value)))
			return false;
		
		$d = date('Y-m-d H:i:s', $timestamp);
		$datetime = explode(' ', $d);
		if ('00:00:00' == $datetime[1])
			return $datetime[0];
		else
			return $d;
	}
	
	/**
	 * Perform validation check
	 *
	 * @param array $input
	 * @return Validator_Result
	 */
	function check($input)
	{
		array_walk_recursive($input, array($this, '_prepareInput'));

		$this->_result->reset();
		$this->_result->importRaw(array_intersect_key($input, $this->_validated));
		
		if ($this->_failRequired($input) || 
			$this->_failEquals($input) || 
			$this->_failRules($input))
		{
			;
		}
		
		return $this->_result;
	}
	
	/**
	 * @return Validator_Result
	 */
	final function result()
	{
		return $this->_result;
	}
	
	protected function _failRequired($input)
	{
		foreach ($this->_required as $key => $message)
			if (empty($input[$key]))
				$this->_result->addError($key, $message);
		
		return ! $this->_result->ok();
	}
	
	protected function _failEquals($input)
	{
		foreach ($this->_equals as $spec)
			if (@$input[$spec[0]] != @$input[$spec[1]])
				$this->_result->addError($spec[1], $spec[2]);
		
		return ! $this->_result->ok();
	}
	
	protected function _failRules($input)
	{
		$output = array();
		foreach ($input as $key => $value)
			if (! empty($value))
				$output[$key] = $value;

		foreach ($this->_rules as $index => $stack)
		{
			$tmp_output = filter_var_array($output, array_intersect_key($stack, $output));
			foreach ($tmp_output as $key => $value)
			{
				if (empty($this->_rules[$index][$key])) continue;
				
				if (FILTER_VALIDATE_BOOLEAN == $this->_rules[$index][$key]['filter'])
				{
					$tmp_output[$key] = (bool) $value;
				}
				elseif (false === $value)
				{
					$this->_result->addError($key, $this->_messages[$index][$key]);
				}
			}
			
			$output = array_merge($output, $tmp_output);
		}

		$output = array_merge($input, $output);
		$ok = $this->_result->ok();
		if ($ok) {
			$this->_result->import($output);
		}
		
		return ! $ok;
	}
	
	protected function _prepareInput(& $value)
	{
		if (is_string($value)) $value = trim($value);
	}
	
	/**
	 * @return Validator
	 */
	protected function _doAddRule($key, $filter, $message, $index)
	{
		if (null === $index)
		{
			$index = count($this->_rules);
			$this->_rules[$index] = array();
			$this->_messages[$index] = array();
		}
		
		if (is_int($filter))
		{// make filters uniform
			$filter = array('filter' => $filter);
		}
		
		$this->_rules[$index][$key] = $filter;
		$this->_messages[$index][$key] = $message;
		return $this;
	}
}