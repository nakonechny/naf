<?php

/**
 * Validator chain - a chain-of-responsibilities pattern implementation.
 * 
 * $Id: Chain.php 184 2008-04-07 09:18:44Z vbolshov $
 * 
 * @package naf\util
 */

namespace naf\util;

use \SplObjectStorage;

class Validator_Chain {
	/**
	 * @var SplObjectStorage
	 */
	protected $_validatorStorage;
	
	/**
	 * @var Naf_Validator_Result
	 */
	protected $_result;
	
	function __construct()
	{
		$this->_validatorStorage = new SplObjectStorage();
		foreach (func_get_args() as $o)
			$this->addValidator($o);
		
		$this->_result = new Validator_Result();
	}
	
	function addValidator(Naf_Validator $validator)
	{
		$this->_validatorStorage->attach($validator);
	}
	
	/**
	 * @return Naf_Validator_Result
	 */
	function check($input)
	{
		foreach ($this->_validatorStorage as $validator)
			if (! $validator->check($input)->ok())
				return $this->_result = $validator->result();
		
		return $this->_result;
	}
	
	/**
	 * @return Naf_Validator_Result
	 */
	function result()
	{
		return $this->_result;
	}
}