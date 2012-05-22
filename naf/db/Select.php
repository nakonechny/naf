<?php

/**
 * naf\db\Select is (kind of simple, as alwayse ;) ) Naf implementation of M. Fowler's Query-Object design pattern.
 * 
 * $Id: Select.php 265 2008-09-16 13:09:31Z vbolshov $
 */

namespace naf\db;

use \Naf;// for default DB connection
use \IteratorAggregate, \Countable, \PDO;

class Select implements IteratorAggregate, Countable {
	/**
	 * @var PDO
	 */
	private static $defaultConnection;
	/**
	 * @var PDO
	 */
	private $connection;
	protected $fetchMode = array(PDO::FETCH_ASSOC);
	/**
	 * FROM clause spec
	 *
	 * @var string
	 */
	private $from, $selection;
	/**
	 * JOIN clause spec
	 *
	 * @var string
	 */
	private $join = array();
	/**
	 * @var array
	 */
	private $filters = array();
	
	/**
	 * @var array|string
	 */
	private $order;
	
	/**
	 * @var array|string
	 */
	private $groupBy, $having;
	
	/**
	 * @var int
	 */
	private $pageNumber, $pageSize = 20;
	
	function __construct($from, $selection = '*')
	{
		$this->from = $from;
		$this->selection = $selection;
	}
	
	static function setDefaultConnection($c)
	{
		self::$defaultConnection = $c;
	}
	
	function setConnection($c)
	{
		$this->connection = $c;
		return $this;
	}
	
	function setFetchMode($mode, $opts = null)
	{
		$this->fetchMode = is_array($mode) ?
			$mode :
			func_get_args();
		return $this;
	}
	
	/**
	 * paginate result set.
	 * @param Naf_Pager $pager
	 */
	function paginate($pageNumber, $pageSize = null)
	{
		$this->pageNumber = $pageNumber;
		if ($pageSize)
		{
			$this->pageSize = $pageSize;
		}
		return $this;
	}
	
	/**
	 * @return PDOStatement
	 */
	function export() {
		$data = array();
		$sql = $this->baseSQL($data, $this->selection);
		
		$this->_appendGroupBy($sql);
		$this->_appendHaving($sql);
		
		$this->_appendOrder($sql);
		$this->_appendLimit($sql);
		
		return $this->statement($sql, $data);
	}
	/**
	 * A debug method, allowing to see the resulting SQL
	 * 
	 * NOTE: not portable, best suited for MySQL.
	 *
	 * @return string
	 */
	function sql()
	{
		$data = array();
		$sql = $this->baseSQL($data, $this->selection);
		
		$this->_appendGroupBy($sql);
		$this->_appendHaving($sql);
		
		$this->_appendOrder($sql);
		$this->_appendLimit($sql);
		
		$sql_split = explode('?', $sql);
		$sql_str = $sql_split[0];
		foreach ($data as $i => $bound_var)
		{
			if (is_bool($bound_var))
			{
				$bound_var = (int) $bound_var;
			}
			
			if (is_numeric($bound_var)) {
				$sql_str .= $bound_var;
			} elseif (is_null($bound_var)) {
				$sql_str .= 'NULL';
			} else {
				$sql_str .= "'$bound_var'";
			}
			$sql_str .= $sql_split[$i + 1];
		}
		return $sql_str;
	}
	
	/**
	 * @return Iterator
	 */
	function getIterator()
	{
		return $this->export();
	}
	/**
	 * This method made protected as it turned out to be useful sometimes to use it in child classes
	 *
	 * @param array $data
	 * @param string $selection
	 * @return string the resulting SQL query
	 */
	protected function baseSQL(&$data, $selection)
	{
		$sql = "SELECT " . $selection . " FROM " . $this->from . ' ' . implode(' ', $this->join);
		$data = $this->_appendWhere($sql, $this->filters);
		return $sql;
	}
	/**
	 * Execute COUNT($expression)
	 *
	 * @param string $expression the SQL expression to be wrapped in COUNT()
	 * @return int
	 */
	function count($expression = "*")
	{
		$data = array();
		$sql = $this->baseSQL($data, "COUNT($expression)");
		$this->_appendHaving($sql);
		
		return $this->statement($sql, $data)->fetchColumn();
	}
	
	/**
	 * Join to some table
	 *
	 * @param string $join_clause the full JOIN clause, f. e. LEFT JOIN table AS alias ON (...)
	 * @param string $cols
	 */
	function join($join_clause, $cols = null)
	{
		$this->join[] = $join_clause;
		if ($cols)
		{
			$this->selection .= ", " . $cols;
		}
		return $this;
	}
	/**
	 * Set WHERE clause.
	 * If the first argument is a string - then addFilter() is called with arguments $where, $bound,
	 * otherwise addFilters() is called with a $where argument
	 *
	 * @param string | array $where
	 * @param mixed $bound
	 * @return Select $this
	 */
	function where($where, $bound = null)
	{
		if (is_string($where))
		{
			return $this->addFilter($where, $bound);
		} else {
			assert(is_array($where));
			return $this->addFilters($where);
		}
	}
	/**
	 * Set ORDER BY clause
	 *
	 * @param string $order_clause
	 * @return Select $this
	 */
	function orderBy($order_by_clause)
	{
		$this->order = $order_by_clause;
		return $this;
	}
	/**
	 * Set GROUP BY clause
	 *
	 * @param string $group_by_clause
	 * @return Select $this
	 */
	function groupBy($group_by_clause)
	{
		$this->groupBy = $group_by_clause;
		return $this;
	}
	/**
	 * Set HAVING clause
	 *
	 * @param string $having
	 * @return Select $this
	 */
	function having($having)
	{
		$this->having = $having;
		return $this;
	}
	
	/**
	 * @deprecated use groupBy()
	 * @param string $groupBy
	 * @return Select $this
	 */
	function setGroupBy($groupBy)
	{
		return $this->groupBy($groupBy);
	}
	/**
	 * @deprecated use having()
	 * @param string $having
	 * @return Select $this
	 */
	function setHaving($having)
	{
		return $this->having($having);
	}
	/**
	 * @deprecated use orderBy()
	 * @param string $order
	 * @return Select $this
	 */
	function setOrder($order)
	{
		return $this->orderBy($order);
	}
	/**
	 * @return string | array
	 */
	final function getOrder()
	{
		return $this->order;
	}
	/**
	 * Set FROM clause
	 *
	 * @param string $from
	 * @return Select $this
	 */
	function from($from)
	{
		$this->from = $from;
		return $this;
	}
	/**
	 * Set cloumns to be selected
	 *
	 * @param string $selection
	 * @return Select $this
	 */
	function setSelection($selection)
	{
		$this->selection = $selection;
		return $this;
	}
	/**
	 * Add filters from array
	 *
	 * @param string $from
	 * @return Select $this
	 */
	final function addFilters($filters)
	{
		foreach ((array) $filters as $sql => $data)
		{
			$this->addFilter($sql, $data);
		}
		return $this;
	}
	/**
	 * Add a single filter
	 * 
	 * @param string $sql
	 * @param array $binds
	 * @return Select $this
	 */
	final function addFilter($sql, $binds = null) {
		$this->filters[(string) $sql] = $binds;
		return $this;
	}
	/**
	 * Register filter ONLY IF $condition evaluates to TRUE
	 * 
	 * @param bool $condition
	 * @param string $sql
	 * @param array $binds
	 * @return Select $this
	 */
	final function addFilterIf($condition, $sql, $binds = null) {
		if ($condition)
		{
			$this->filters[$sql] = $binds;
		}
		return $this;
	}
	
	/**
	 * @param string $sql
	 * @return Select $this
	 */
	final function removeFilter($sql) {
		if (isset($this->filters[$sql])) unset($this->filters[$sql]);
		return $this;
	}
	
	final private function _appendWhere(&$sql)
	{
		if (! count($this->filters))
		{
			return array();
		}
		$bound_vars = $and = array();
		foreach ($this->filters as $key => $val)
		{
			if (is_string($key))
			{
				$and[] = $key;
				if (null !== $val)
				{
					$val = (array) $val;
				} else {
					$val = array(null);
				}
				$bound_vars = array_merge($bound_vars, $val);
			} else {
				$and[] = $val;
			}
		}
		
		$sql .= ' WHERE (' . implode(') AND (', $and) . ')';
		return $bound_vars;
	}
	
	final protected function _appendGroupBy(&$sql)
	{
		if (null !== $this->groupBy)
			$sql .= ' GROUP BY ' . implode(', ', (array) $this->groupBy);
	}
	
	final protected function _appendHaving(&$sql)
	{
		if (null !== $this->having)
			$sql .= ' HAVING (' . implode(') AND (', (array) $this->having) . ')';
	}
	
	final protected function _appendOrder(&$sql)
	{
		if (empty($this->order))
			return ;
		
		$normalized = array();
		foreach ((array) $this->order as $key => $val)
		{
			if (is_numeric($key))
			{
				$normalized[] = $val;
			} else {
				$normalized[] = $key . ' ' . $val;
			}
		}
		$sql .= ' ORDER BY ' . implode(', ', $normalized);
	}
	
	final protected function _appendLimit(&$sql)
	{
		if (! $this->pageNumber)
		{
			return ;
		}
		
		$sql .= ' LIMIT ' . $this->pageSize . ' OFFSET ' . (($this->pageNumber - 1) * $this->pageSize);
	}
	
	protected function statement($sql, $data)
	{
		$s = $this->getConnection()->prepare($sql);
		$s->execute($data);
		call_user_func_array(array($s, 'setFetchMode'), $this->fetchMode);
		return $s;
	}
	/**
	 * @return PDO
	 */
	function getConnection()
	{
		if ($this->connection)
		{
			return $this->connection;
		} elseif (self::$defaultConnection) {
			return self::$defaultConnection;
		} else {
			return Naf::pdo();
		}
	}

	/**
	 * Iterates Select to get all data as an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		$this->setFetchMode(PDO::FETCH_ASSOC);

		$result = array();
		foreach ($this as $row) {
			$result[] = $row;
		}

		return $result;
	}
}
