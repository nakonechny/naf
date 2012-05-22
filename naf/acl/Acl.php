<?php

/**
 * Naf_Acl class. A simple Access-control layer.
 *
 * @author vbolshov
 * @package Naf
 * @subpackage ACL
 * @version 1.0
 */

namespace naf\acl;
use \ReflectionMethod;

class Acl {

	/**
	 * @var array
	 */
	protected $config;
	
	/**
	 * @var array
	 */
	protected $params = array();
	
	/**
	 * @var object (possibly Yacl_User)
	 */
	protected $user;
	
	/**
	 * Constructor
	 *
	 * @param array $config
	 * @param object $user
	 */
	function __construct(array $config, $user)
	{
		$this->config = $config;
		$this->user = $user;
	}
	
	/**
	 * Get user
	 *
	 * @return Yacl_User
	 */
	function getUser()
	{
		return $this->user;
	}

	/**
	 * Determine whether the user is allowed to access path $path
	 *
	 * @param string | array $path
	 * @return bool
	 */
	function allow($path)
	{
		if (is_string($path))
		{
			$path = explode('/', trim($path, '/ '));
		}
		
		$tempConfig = $this->config;
		foreach ($path as $part) {
			if (! array_key_exists($part, $tempConfig))
			{
				if (array_key_exists('*', $tempConfig))
				{
					$part = '*';
				}
				else
				{
					return true;
				}
			}
			if (! $this->_check($tempConfig[$part]))
			{
				return false;
			}
			$tempConfig = $tempConfig[$part];
		}
		
		return true;
	}
	
	/**
	 * Set external parameter.
	 * Parameters could be used for substituting placeholders
	 * in auth-domain configuration
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	function setParam($name, $value)
	{
		$this->params[$name] = $value;
	}
	
	/**
	 * Get an external parameter
	 *
	 * @param string $name
	 * @return mixed
	 */
	function getParam($name)
	{
		return array_key_exists($name, $this->params) ? $this->params[$name] : null;
	}
	
	/**
	 * Check an auth-domain configuration token for being available for user
	 *
	 * @param array $token
	 * @return bool
	 * @access protected
	 */
	protected function _check($token)
	{
		if (array_key_exists('check', $token))
		{
			return (bool) $this->_callUserMethod($token['check']);
		}
		
		return true;
	}
	
	/**
	 * Parses method specification, taking in account parameters,
	 * substituting them with placeholders when necessary
	 *
	 * @param string $spec Method/arguments specification (method:arg1:arg2:{placeholder})
	 * @return array (method, arguments)
	 * @access protected
	 */
	protected function _parseMethodSpec($spec)
	{
		$tokens = explode(':', $spec);
		foreach ($tokens as $i => $token) {
			if (false === ($placeholder = $this->_isPlaceholder($token)))
			{
				continue;
			}
			
			$tokens[$i] = $this->getParam($placeholder);
		}
		$methodName = array_shift($tokens);
		return array($methodName, $tokens);
	}
	
	/**
	 * Check if the argument passed should be considered a placeholder
	 *
	 * @param string $string
	 * @return string Placeholder name or bool FALSE
	 */
	protected function _isPlaceholder($string)
	{
		if (preg_match('/^\{([^\}]*)\}$/', $string, $matches))
		{
			return $matches[1];
		}
		
		return false;
	}
	
	/**
	 * Invoke user method
	 *
	 * @param string $spec Method specification
	 * @return mixed
	 */
	protected function _callUserMethod($spec)
	{
		list($method, $args) = $this->_parseMethodSpec($spec);
		$m = new ReflectionMethod($this->user, $method);
		return $m->invokeArgs($this->user, $args);
	}
}