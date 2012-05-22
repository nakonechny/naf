<?php
namespace naf\util;

class Form
{
	const REQUIRED_FIELD_EMPTY = 'Required field is empty';
	
	static protected $defaults;
	
	protected $data = array();
	protected $validator;
	/**
	 * Callbacks to setup a certain property
	 *
	 * @var callback[]
	 */
	protected $setters = array();
	
	/**
	 * @return Validator
	 */
	final public function validator()
	{
		if (null === $this->validator)
			$this->validator = $this->createValidator();
		
		return $this->validator;
	}

	/**
	 * @return Validator
	 */
	protected function createValidator()
	{
		return new Form_Validator();
	}

	/**
	 * Import data from array
	 *
	 * @param array $data
	 */
	public function import($data)
	{
		foreach ($data as $key => $value)
		{
			if (array_key_exists($key, static::$defaults)) {
				$this->$key = $value;
			}
		}
	}
	
	/**
	 * Export data
	 *
	 * @return array
	 */
	public function export()
	{
		return $this->data;
	}
	
	public function json()
	{
		return json_encode($this->data);
	}
	
	/**
	 * Reset data to defaults
	 */
	public function reset()
	{
		$this->data = static::$defaults;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return array_key_exists($name, $this->data) ? $this->data[$name] : null;
	}
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		if (array_key_exists($name, $this->setters))
		{
			$this->data[$name] = $this->{$this->setters[$name]}($value);
		} else {
			$this->data[$name] = $value;
		}
	}

	/**
	 * A commonly used validator-filter
	 * @return Closure
	 */
	public function getDateFilter()
	{
		return function($date) {
			$ts = strtotime($date);
			if ($ts) {
				return date("Y-m-d", $ts);
			}
			return false;
		};
	}
	
	/**
	 * A commonly used validator-filter
	 * @return Closure
	 */
	public function getDateTimeFilter()
	{
		return function($date) {
			$ts = strtotime($date);
			if ($ts) {
				return date("Y-m-d H:i:s", $ts);
			}
			return false;
		};
	}
	
	public function check()
	{
		$result = $this->validator()->check($this->data);
		
		if ($result->ok())
		{
			$this->data = $result->export();
			return true;
		}
		else
			return false;
	}

	/**
	 * Shortcut for $this->validator()->result()->getErrorList();
	 * @return array
	 */
	public function getErrorList($preserve_keys = true)
	{
		return $this->validator()->result()->getErrorList($preserve_keys);
	}
}