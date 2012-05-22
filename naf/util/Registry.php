<?php

/**
 * An implementation of Registry pattern.
 */

namespace naf\util;
use naf\util\Registry\Fault;

class Registry {
	/**
	 * @var array
	 */
	static private $storage = array();
	/**
	 * @param string $name
	 * @param object $object
	 * @throws naf\util\Registry\Fault
	 */
	static function put($name, $object)
	{
		if (self::exists($name))
			throw new Fault("Object $name already registered");
		else
			self::$storage[$name] = $object;
	}
	/**
	 * @param string $name
	 * @return bool
	 */
	static function exists($name)
	{
		return array_key_exists($name, self::$storage);
	}
	/**
	 * @param string $name
	 * @return object
	 * @throws naf\util\Registry\Fault
	 */
	static function get($name)
	{
		if (self::exists($name))
			return self::$storage[$name];
		else
			throw new Fault("Object $name not found in registry");
	}
}