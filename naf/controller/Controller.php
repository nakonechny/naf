<?php

/**
 * This is the base class for a Naf-style MVC controller.
 * 
 * $Id: Controller.php 209 2008-05-10 04:42:36Z vbolshov $
 * 
 * @package naf\controller
 * @copyright Victor Bolshov <crocodile2u@gmail.com>
 */

namespace naf\controller;
use \Naf;
use naf\view\Ajax;
use naf\view\AjaxError;

abstract class Controller {
	/**
	 * @var array
	 */
	protected $postRequestHandlers = array();
	
	/**
	 * @var Naf_Response
	 */
	protected $response;
	
	/**
	 * Constructor.
	 * 
	 * DO NOT FORGET TO CALL parent::__construct() in child classes' constructors!
	 */
	function __construct()
	{
		$this->response = Naf::response();
		
		$class = get_class($this);
		foreach ($this->postRequestHandlers as $trigger => $handler)
		{
			if ((! $handler) || (is_string($handler) && ($handler == $class)))
			{
				$this->postRequestHandlers[$trigger] = $this;
			}
		}
	}
	
	/**
	 * Run this controller. This method is marked final,
	 * and it is a template-method (calls $this->doRun() and $this->render())
	 */
	final function run()
	{
		if (is_array($this->postRequestHandlers) && 
			count($this->postRequestHandlers) && 
			Naf::handlePost($this->postRequestHandlers))
		{
			return ;
		}
		
		$this->doRun();
		$this->render($this->response->getView());
	}
	
	/**
	 * Actually run this controller
	 */
	abstract function doRun();
	/**
	 * render view
	 */
	function render($view){}
	
	/**
	 * Render naf-style AJAX response (output some data back to the client).
	 */
	final function renderAjax($data, $forceAjaxResponse = false)
	{
		$v = new Ajax($data, $forceAjaxResponse);
		$v->render();
	}
	
	/**
	 * Render naf-style AJAX response (error).
	 */
	final function renderAjaxError($errorList, $forceAjaxResponse = false)
	{
		$v = new AjaxError($errorList, $forceAjaxResponse);
		$v->render();
	}
}