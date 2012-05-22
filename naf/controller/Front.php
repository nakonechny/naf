<?php
namespace naf\controller;

use \Exception;
use \Naf;
use \naf\view\PhpNative;
use \naf\view\Ajax;
use \naf\view\AjaxError;
use \naf\err\NotFoundError;

class Front
{
	const RENDER_VIEW = true;
	const DONT_RENDER_VIEW = false;
	
	/**
	 * Controllers directory paths
	 *
	 * @var array
	 */
	static protected $controllersPath;

	/**
	 * Views directory paths
	 *
	 * @var array
	 */
	static protected $viewsPath = array();

	/**
	 * Current controller
	 *
	 * @var string
	 */
	static private $controller;

	/**
	 * @param string | array $path
	 */
	static public function setControllersPath($path)
	{
		self::$controllersPath = array();
		foreach ((array) $path as $dir)
			self::$controllersPath[] = rtrim($dir, '/') . '/';
	}

	/**
	 * @param string | array $path
	 */
	static public function setViewsPath($path)
	{
		self::$viewsPath = array();
		foreach ((array) $path as $dir)
			self::$viewsPath[] = rtrim($dir, '/') . '/';
	}

	/**
	 * Check whether $controller is the current controller
	 *
	 * @param string $controller
	 * @param int $accuracy max difference in levels
	 * @return bool
	 */
	static public function isCurrent($controller, $accuracy = 0)
	{
		$controller = rtrim($controller, './ ') . '/';
		if (0 !== strpos(self::getController() . '/', $controller))
			return false;
		elseif (0 == $accuracy)
			return true;
		else
			return $accuracy > (substr_count(self::getController() . '/', '/') - substr_count($controller, '/'));
	}

	/**
	 * @return string
	 */
	static public function getController()
	{
		if (! self::$controller) {
			self::$controller = Naf::getRouter()->getController();
		}
		
		return self::$controller;
	}

	static public function setController($controller)
	{
		self::$controller = $controller;
	}

	static public function dispatch()
	{
		self::performController(self::getController(), self::RENDER_VIEW);
	}

	/**
	 * Perform action $controller
	 *
	 * @param string $controller
	 * @param bool $doRenderView Whether to render view immediately
	 */
	static function performController($controller, $doRenderView = true)
	{
		$not_found_controller = null;
		while (true)
		{
			try
			{
				foreach (self::$controllersPath as $dir)
				{
					if (is_file($controllerFilename = $dir . $controller . '.php'))
					{
						include $controllerFilename;
						if ($doRenderView)
						{
							$response = Naf::response();
							$view = $response->getView();
							if ($view === null)
							{
								$view = $controller;
								$response->setView($view);
							}
							if ($view) {
								$viewEngine = new PhpNative($response);
								$viewEngine->setScriptPath(static::$viewsPath);
								$viewEngine->render($view);
							}
						}
						
						return ;
					}
				}

				if ($controller == $not_found_controller)
					die('Action ' . $controller . ' not found. Additionally, the exception-handler action could not be found.');
				else
				{
					$not_found_controller = $controller;
					throw new NotFoundError();
				}
			}
			catch (naf\controller\Exception\Stop $e)
			{
				break;
			}
			catch (naf\controller\Exception\Forward $e)
			{
				if ($e->replace()) {
					static::setController($e->where());
					Naf::response()->setView($e->where());
				}
				$controller = $e->where();
				
				continue;
			}
			catch (\Exception $e)
			{
				$handler_controller = self::getExceptionHandlerController($e);
				if ($handler_controller)
				{
					Naf::response()->exception = $e;
					Naf::response()->setView($handler_controller);
					static::setController($handler_controller);
					$controller = $handler_controller;
					
					continue;
				}
				else
				{
					throw $e; // unhandled ones are still may be caught by final catchers
				}
			}
		}
	}

	/**
	 * @param Exception $e
	 * @access private
	 */
	static private function getExceptionHandlerController(Exception $e)
	{
		foreach ((array) Naf::config('exception.controllers') as $class => $controller)
		{
			if ($e instanceof $class)
			{
				return $controller;
			}
		}

		return null;
	}

	/**
	 * Handle a POST request.
	 *
	 * @param string $controller
	 * @param string $trigger
	 * @param bool $doRenderView
	 * @return bool
	 * @see handleSpecialRequest()
	 */
	static function handlePostRequest($controller, $trigger = null, $doRenderView = true)
	{
		return self::handleSpecialRequest('POST', $controller, $trigger, $doRenderView);
	}

	/**
	 * Handle a special request
	 *
	 * @param string $method REQUEST_METHOD
	 * @param string $controller Action to be performed
	 * @param string $trigger When set to NULL - ignored. Otherwise,
	 * 					the action $controller will be performed ONLY in the case
	 * 					that $trigger key is present in the appropriate _REQUEST superglobal
	 * 					( $_GET for GET request, $_POST for POST )
	 * @param bool $doRenderView
	 * @return bool whether the action was performed
	 */
	static function handleSpecialRequest($method, $controller, $trigger = null, $doRenderView = true)
	{
		$method = strtoupper($method);
		if ($method != $_SERVER['REQUEST_METHOD'])
			return false;

		if (null !== $trigger)
		{
			$request = ('GET' == $method) ? $_GET : $_POST;
			if (!isset($request[$trigger]))
				return false;
		}

		if ($controller) {
			self::performController($controller, $doRenderView);
		}

		return true;
	}
}