<?php
namespace naf\controller\Exception;
use \Exception;

/**
 * This exception is thrown once it is required to switch to new Controller
 */
class Forward extends Exception
{
	/**
	 * @var string
	 */
	private $action;
	
	/**
	 * @var string
	 */
	private $replaceAction;
	
	/**
	 * Constructor
	 *
	 * @param string $action
	 * @param bool $replaceAction
	 */
	public function __construct($action, $replaceAction = true)
	{
		$this->action = $action;
		$this->replaceAction = $replaceAction;
	}
	
	/**
	 * Where are we forwarded ?
	 *
	 * @return string Forwarded action
	 */
	public function where()
	{
		return $this->action;
	}
	
	/**
	 * Must we replace the current action ?
	 *
	 * @return bool
	 */
	public function replace()
	{
		return $this->replaceAction;
	}
}