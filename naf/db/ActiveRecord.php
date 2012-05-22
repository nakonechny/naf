<?php

/**
 * ActiveRecord class 
 * 
 * $Id: ActiveRecord.php 270 2008-09-24 15:21:48Z vbolshov $
 * 
 * A VERY simple implementation of ActiveRecord design pattern.
 * Any domain logic is intended to be implemented in child-classes.
 * However, validation facility is provided by naf\util\Validator.
 */

namespace naf\db;

use \naf\util\Validator;
use \naf\err\NotFoundError;
use \Exception, \ArrayAccess, \PDO;

class ActiveRecord implements ArrayAccess {
	/**
	 * @var PDO
	 */
	static protected $connection;
	
	static protected $statementCache = array();
	
	static protected $table, $pk = 'id', $sequence;
	
	static protected $defaults;
	
	static protected $registry = array();
	
	protected $data = array();
	/**
	 * @var naf\util\Validator
	 */
	protected $validator;
	
	/**
	 * Callbacks to setup a certain property
	 *
	 * @var callback[]
	 */
	protected $setters = array();
	
	/**
	 * Aggregated objects
	 *
	 * @var object[]
	 */
	private $aggregates = array();
	
	/**-@*/
	/**
	 * Insert a new row
	 *
	 * @param array $row
	 * @return int new row ID
	 */
	static function insert($row)
	{
		$sql = 'INSERT INTO ' . (static::$table) . ' (' . implode(', ', array_keys($row)) . 
				') VALUES (?' . str_repeat(', ?', count($row) - 1) . ')';
		static::statement($sql, array_values($row));
		return static::getConnection()->lastInsertId(static::getSequence());
	}
	
	/**
	 * Update a row specified by id $id
	 *
	 * @param array $row
	 * @param int $id (optional, may be specified as a $row member)
	 * @return bool
	 */
	static function update($row, $id = null)
	{
		if ((! $id) && ($row[static::$pk]))
		{
			$id = $row[static::$pk];
		}
		
		if (! $id)
		{
			return false;// @todo exception throw?
		}
		
		$updates = array();
		foreach (array_keys($row) as $field)
		{
			$updates[] = $field . ' = ?';
		}
		
		$sql = 'UPDATE ' . (static::$table) . ' SET ' . implode(', ', $updates) . 
				' WHERE ' . (static::$pk) . ' = ?';
		$data = array_values($row);
		$data[] = $id;
		return static::statement($sql, $data)->errorCode() == '00000' ?
			$id :
			false;
	}
	/**
	 * Delete row specified by $id
	 *
	 * @param int | int[] $id
	 * @return int Number of rows deleted
	 */
	static function deleteRow($id)
	{
		return (bool) static::statement('DELETE FROM ' . (static::$table) . ' WHERE ' . (static::$pk) . ' = ?', $id)->rowCount();
	}
	static function deleteAll($where)
	{
		$bound_vars = $and = array();
		foreach ((array) $where as $key => $val)
		{
			if (is_string($key))
			{
				$and[] = $key;
				if (null !== $val)
				{
					$val = (array) $val;
				}
				$bound_vars = array_merge($bound_vars, $val);
			} else {
				$and[] = $val;
			}
		}
		return (bool) static::statement('DELETE FROM ' . (static::$table) . ' WHERE (' . implode(') AND (', $and) . ')', $bound_vars)->rowCount();
	}
	/**
	 * @param int $id
	 * @param string $cols
	 * @param int | array $fetchMode - per-call fetching mode overriding static::$fetchMode value
	 * @return ActiveRecord
	 */
	static function find($id)
	{
		if ($id === null) {
			$id = array(null);
		}
		if (isset(self::$registry[$registry_key = get_called_class() . '-' . $id]))
		{
			return self::$registry[$registry_key];
		}
		$sql = "SELECT * FROM " . (static::$table) . " WHERE ".static::$pk." = ?";
		$s = static::statement($sql, $id);
		return self::$registry[$registry_key] = $s->fetch();
	}
	
	/**
	 * Find an existing table row by ID,
	 * or create a new instance of an appropriate ActiveRecord class - 
	 * should the $id be empty
	 * @return ActiveRecord
	 */
	static function findOrCreate($id)
	{
		if ($id) {
			return static::find($id);
		} else {
			$class = get_called_class();
			return new $class;
		}
	}

	/**
	 *
	 * @param mixed $id
	 * @param string $exception_class
	 * @return ActiveRecord
	 * @throws Exception
	 */
	static function findOrThrowException($id, $exception_class = 'naf\err\NotFoundError')
	{
		$active_record_class = get_called_class();

		$found = static::find($id);

		if (! ($found instanceof $active_record_class)) {
			throw new $exception_class("$active_record_class $id not found");
		}

		return $found;
	}
	
	/**
	 * @param array $where conditions for the WHERE SQL clause
	 * @param string $cols
	 * @param int | array $fetchMode - per-call fetching mode overriding static::$fetchMode value
	 * @return naf\db\Select
	 */
	static function findAll($where = null, $cols = "*", $fetchMode = null)
	{
		$s = new Select(static::$table, $cols);
		return $s->addFilters($where)
			->setConnection(static::getConnection())
			->setFetchMode(array(PDO::FETCH_CLASS, get_called_class()));
	}
	/**
	 * @return int
	 */
	static function count($where = null, $expr = '*')
	{
		return static::findAll($where)->count($expr);
	}
	
	static function setConnection($c)
	{
		static::$connection = $c;
	}
	
	/**
	 * Constructor
	 * 
	 * @param int | array $arg (optional) either instance ID -
	 * 							to be immediately loaded from DB, 
	 * 							or an array of data to be immediately imported.
	 */
	function __construct($arg = null)
	{
		if (! count($this->data))
		{/* a check for count($this->data) is necessary:
			when a class instance is created inside PDO->fetch() using PDO::FETCH_CLASS, 
			the constructor is called AFTER the properties have been assigned;
			@see http://bugs.php.net/bug.php?id=4371
			@todo remove the check once the bug is fixed */
			$this->reset();
		}
		
		if (null !== $arg)
		{
			if (is_scalar($arg))
			{
				if (! $this->load($arg))
				{
					throw new NotFoundError();
				}
			} else {
				$this->import($arg);
			}
		}
		
		$this->setup();
	}
	
	/**
	 * Get aggregated object by name
	 */
	protected function getAggregate($name)
	{
		if (! array_key_exists($name, $this->aggregates))
		{
			if (! method_exists($this, $createMethod = 'createAggregate' . $name))
			{
				throw new Exception(__METHOD__ . ": was unable to create $name aggregate due to absense of $createMethod");
			}
			
			$this->aggregates[$name] = $this->$createMethod();
		}
		return $this->aggregates[$name];
	}
	/**
	 * set aggregated object
	 */
	protected function setAggregate($name, $object)
	{
		$this->aggregates[$name] = $object;
	}
	
	/**
	 * Dummy default setup.
	 */
	function setup()
	{}
	
	/**
	 * Import data from array
	 *
	 * @param array $data
	 * @param bool $includeId Whether to import the 'id' element
	 */
	function import($data, $includeId = true)
	{
		if ($data instanceof ActiveRecord)
		{
			$data = $data->export();
		}
		
		if ((! $includeId) && array_key_exists(static::$pk, $data))
		{
			unset($data[static::$pk]);
		}

		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}
	}
	
	/**
	 * Export data
	 *
	 * @return array
	 */
	function export()
	{
		return $this->data;
	}
	
	function json()
	{
		return json_encode($this->data);
	}
	
	/**
	 * Save a row
	 *
	 * @param array $row
	 * @return int new row ID
	 */
	function save()
	{
		if (! $this->_check()) return false;
		
		$rowData = array_intersect_key($this->data, static::$defaults);
		if (empty($this->data[static::$pk]))
		{
			$this->data[static::$pk] = static::insert($rowData);
			// put the new instance into registry
			self::$registry[get_class($this) . '-' . $this->data[static::$pk]] = $this;
			return $this->data[static::$pk];
		}
		else
			return static::update($rowData, $this->data[static::$pk]);
	}
	
	function delete()
	{
		if (empty($this->data[static::$pk]))
			return false;
		
		return static::deleteRow($this->data[static::$pk]);
	}
	
	/**
	 * Loads data from a table row with id=$id
	 *
	 * @param int $id
	 * @return array row data on success or bool false if no results can be found
	 */
	function load($id)
	{
		$this->reset();
		$found = static::find($id);
				if (! $found) {
			return false;
		}
		
		return $this->data = $found->export();
	}
	
	/**
	 * Loads data from a table row with $colname=$colvalue
	 *
	 * @param mixed $colname
	 * @param mixed $colvalue
	 * @return array row data on success or bool false if no results can be found
	 */
	function loadByColumn($colname, $colvalue)
	{
		return $this->loadByFilter(array($colname . ' = ?' => $colvalue));
	}
	
	/**
	 * @param array|string $filter
	 */
	function loadByFilter($filter) {
		$this->reset();
		$row = static::findAll($filter)->export()->fetch();
		if (! $row) {
			return false;
		}
		
		$this->import($row);
		return $row;
	}
	
	/**
	 * Reset data to defaults
	 */
	function reset()
	{
		$this->data = static::$defaults;
		$this->data[static::$pk] = null;
	}
	
	/**
	 * Easy read access to object properties
	 *
	 * @param string $name
	 * @return mixed
	 */
	function __get($name)
	{
		return array_key_exists($name, $this->data) ? $this->data[$name] : null;
	}
	
	/**
	 * Easy write access to object properties
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	function __set($name, $value)
	{
		if (array_key_exists($name, $this->setters))
		{
			$method = $this->setters[$name];
			$this->data[$name] = $this->{$this->setters[$name]}($value);
		} else
			$this->data[$name] = $value;
	}
	
	/**
	 * Get validator for the row to be inserted/updated
	 *
	 * @return Validator
	 */
	final function validator()
	{
		if (null === $this->validator)
			$this->validator = $this->_createValidator();
		
		return $this->validator;
	}
	/**
	 * Shortcut for $this->validator()->result()->getErrorList();
	 * @return array
	 */
	function getErrorList()
	{
		return $this->validator()->result()->getErrorList();
	}
	/**
	 * A commonly used setter
	 * @param mixed $value
	 * @return mixed
	 */
	function nullSetter($value)
	{
		if (empty($value))
		{
			return null;
		} else {
			return $value;
		}
	}
	/**
	 * A commonly used setter
	 * @param mixed $value
	 * @return mixed
	 */
	function zeroSetter($value)
	{
		if (empty($value))
		{
			return 0;
		} else {
			return $value;
		}
	}
	/**
	 * A commonly used validator-filter
	 * @return Closure
	 */
	function getDateFilter()
	{
		return function($date) {
			$ts = strtotime($date);
			if (! $ts) {
				return false;
			}
			
			return date("Y-m-d", $ts);
		};
	}
	/**
	 * A commonly used validator-filter
	 * @return Closure
	 */
	function getDateTimeFilter()
	{
		return function($date) {
			$ts = strtotime($date);
			if (! $ts) {
				return false;
			}
			
			return date("Y-m-d H:i:s", $ts);
		};
	}
	
	/**
	 * Create validator for the row to be inserted/updated
	 *
	 * @return Validator
	 */
	protected function _createValidator()
	{
		return new Validator();
	}
	
	/**
	 * Filter a field to be unique.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return mixed value of $value if it is unique, bool FALSE otherwise
	 */
	protected function _filterUnique($field, $value)
	{
		$where = array($field . ' = ?' => $value);
		if (! empty($this->data[static::$pk]))
		{
			$where[(static::$table) . '.' . (static::$pk) . ' != ?'] = $this->data[static::$pk];
		}

		if (static::count($where))
		{
			return false;
		}

		return $value;
	}
	/**
	 * it is a common case when for validation, we have to be sure
	 * that an object exists in DB - that is specified by ID.
	 * This method returns an appropriate finder - a Closure object
	 * 
	 * @param string $class_name the ActiveRecord-class name
	 * @return Closure
	 */
	protected function getFinder($class_name)
	{
		return function($id) use ($class_name) {
			$object = call_user_func(array($class_name, 'find'), $id);
			if (! $object) {
				return false;
			}
			
			return $object->id;
		};
	}
	
	protected function _check()
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
	 * @return PDO
	 */
	static function getConnection()
	{
		if (null === static::$connection)
		{
			ActiveRecord::setConnection(\Naf::pdo());
		}
		
		return static::$connection;
	}
	
	/**
	 * @return PDOStatement
	 */
	static protected function statement($sql, $data, $fetchMode = null)
	{
		$s = static::getConnection()->prepare($sql);
		foreach (array_values((array) $data) as $n => $value)
		{
			if (is_bool($value))
			{// explicitly specify type
				$s->bindValue($n + 1, $value, PDO::PARAM_BOOL);
			} else {// rely on PDO
				$s->bindValue($n + 1, $value);
			}
		}
		$s->execute();
		$s->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		return $s;
	}
	/**
	 * @param object $s must implement setFetchMode!
	 * @param $fetchMode override statically bound value when needed
	 */
	static protected function setupFetchMode($s, $fetchMode = null)
	{
		if (null === $fetchMode)
		{
			$fetchMode = static::$fetchMode;
		} else {
			$fetchMode = (array) $fetchMode;
		}
		call_user_func_array(array($s, 'setFetchMode'), $fetchMode);
	}
	
	static private function getSequence()
	{
		if (static::$sequence)
		{
			return static::$sequence;
		} else {
			return static::$table . '_' . static::$pk . '_seq';
		}
	}
	
	/**
	 * ArrayAccess methods
	 */
	function offsetExists($name)
	{
		return array_key_exists($name, $this->data);
	}
	function offsetGet($name)
	{
		return $this->__get($name);
	}
	function offsetSet($name, $value)
	{
		return $this->__set($name, $value);
	}
	function offsetUnset($name)
	{
		$this->$name = null;
	}

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return static::$table;
	}

	/**
	 *	Inserts current record including it's primary key field
	 *
	 * @return string last_insert_id
	 */
	public function insertWithPk()
	{
		if (! $this->_check()) return false;

		$rowData = array_intersect_key($this->data, static::$defaults);
		$rowData[static::$pk] = $this->data[static::$pk];

		static::insert($rowData);
		// put the new instance into registry
		self::$registry[get_class($this) . '-' . $this->data[static::$pk]] = $this;
		return $this->data[static::$pk];
	}
}