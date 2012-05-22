<?php

/**
 * Simple timer.
 * 
 * $Id: Timer.php 184 2008-04-07 09:18:44Z vbolshov $
 * 
 * @package naf.util
 * @subpackage Timer
 * @copyright Victor Bolshov <crocodile2u@gmail.com>
 */

namespace naf\util;

class Timer {
	/**
	 * @var float
	 */
	private $_start;
	
	function __construct($start = null)
	{
		$this->_reset($start);
	}
	
	/**
	 * Get elapsed time in microseconds
	 *
	 * @param bool reset whether to reset timer to current microtime
	 * @return int
	 */
	function elapsed($reset = true)
	{
		$start = $this->_start;
		
		if ($reset)
		{
			$this->_reset();
		}
		
		return round(1000 * (microtime(true) - $start), 1);
	}
	
	protected function _reset($time = null)
	{
		$this->_start = (null === $time) ? microtime(true) : $time;
	}
}