<?php
/**
 * Form_Validator reloads validation methods of Validator to make it more suitable for html forms
 *
 * Differencies are:
 * 1. it passes all steps of validation (required, equals and rules) no matter if one of them fails;
 * 2. at every step it checks fields that have not failed before
 *	  (i.e. break validation chain for failed ones)
 */
namespace naf\util;

class Form_Validator extends Validator
{
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
		
		$this->_failRequired($input);
		$this->_failEquals($input);
		$this->_failRules($input);
		
		return $this->_result;
	}
	
	protected function _failRequired($input)
	{
		foreach ($this->_required as $key => $message)
			if (empty($input[$key])) {
				$this->_result->addError($key, $message);
				$this->_validated[$key] = true; // set 'break validation chain' flag
			}
		
		return ! $this->_result->ok();
	}
	
	protected function _failEquals($input)
	{
		foreach ($this->_equals as $spec)
		{
			if ($this->_validated[$spec[1]]) {
				continue; // skip values that allready failed validation
			}
			
			if (@$input[$spec[0]] != @$input[$spec[1]]) {
				$this->_result->addError($spec[1], $spec[2]);
				$this->_validated[$spec[1]] = true; // set 'break validation chain' flag
			}
		}
		
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
				if ($this->_validated[$key]) {
					continue; // skip values that allready failed validation
				}
				
				if (FILTER_VALIDATE_BOOLEAN == $this->_rules[$index][$key]['filter'])
				{
					$tmp_output[$key] = (bool) $value;
				}
				elseif (false === $value)
				{
					$this->_result->addError($key, $this->_messages[$index][$key]);
					$this->_validated[$key] = true; // set 'break validation chain' flag
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
}