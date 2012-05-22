<?php

/**
 * Naf is "Not A Framework"
 *
 *
 * @copyright Victor Bolshov <crocodile2u@gmail.com>
 *
 * version $Id: Naf.php 271 2008-11-01 14:06:33Z vbolshov $
 */

if (! defined('NAF_ROOT'))
{
	define('NAF_ROOT', __DIR__);
}

spl_autoload_register(array('Naf', 'autoload'));

class Naf {
	
	/**
	 * @var array
	 */
	static private $settings = array();
	
	/**
	 * Autoloaded libraries a mapped here
	 *
	 * @var array (LIBRARY-NAME => LIBRARY-ROOT-FOLDER)
	 */
	static private $autoload_map = array('naf' => NAF_ROOT);
	/**
	 * Default library root
	 *
	 * @var string
	 */
	static private $defaultLibraryRoot = NAF_ROOT;
	
	/**
	 * @var naf\core\Response
	 */
	static private $response;
	
	/**
	 * @var PDO
	 */
	static private $pdo;
	
	/**
	 * parameters that need to persist between requests.
	 * these params will be attached automagically to URLs generated by url(), urlXml() methods
	 *
	 * @var array
	 */
	static private $persistentUrlParams = array();

	/**
	 * @var naf\controller\Router;
	 */
	static private $router;
	
	/**
	 * Load configuration file $filename. It should have $settings variable
	 * of type array in it.
	 *
	 * @param string $filename
	 */
	static function loadConfig($filename)
	{
		$settings = null;
		
		include $filename; // config file defines local array $settings
		
		if ($settings) {
			self::importConfig($settings);
		}
	}
	static function loadLibraryMap($map)
	{
		self::$autoload_map = array_merge(self::$autoload_map, $map);
	}
	/**
	 * Get the root folder for a certain library that has been registered with loadLibraryMap
	 *
	 * @param string $library_name
	 * @return string or bool FALSE when the library fails to be found
	 */
	static function getLibraryRoot($library_name)
	{
		if (isset(self::$autoload_map[$library_name]))
		{
			return self::$autoload_map[$library_name];
		} else {
			return false;
		}
	}
	/**
	 * @return array
	 */
	static function exportConfig()
	{
		return self::$settings;
	}
	static function importConfig($settings)
	{
		self::$settings = array_merge(self::$settings, $settings);
		if (isset($settings['autoload_map']))
		{
			self::loadLibraryMap($settings['autoload_map']);
		}
	}
	/**
	 * @param string $key
	 * @param mixed $value
	 */
	static function registerPersistentUrlParameter($key, $value = null)
	{
		self::$persistentUrlParams[$key] = (null === $value) ? @$_GET[$key] : $value;
	}
	/**
	 * generate URL
	 *
	 * @param string $controller
	 * @param array $params
	 * @param boolean $xml
	 * @return string
	 */
	static function url($controller, $params = array(), $xml = false)
	{
		return self::getRouter()->assemble(
				$controller,
				array_merge(self::$persistentUrlParams, $params),
				$xml
		);
	}
	/**
	 * generate URL for use in XML documents
	 *
	 * @param string $path
	 * @param array $params
	 * @return string
	 */
	static function urlXml($path, $params = array())
	{
		return self::url($path, $params, true);
	}
	/**
	 * get current URL with some GET variables [optionally] replaced with new values
	 *
	 * @param array $params
	 * @param boolean $xml
	 * @return string
	 */
	static function currentUrl($params = array(), $xml = false)
	{
		return self::url("", array_merge($_GET, $params), $xml);
	}
	/**
	 * get current URL for use in XML documents with some GET variables [optionally] replaced with new values
	 *
	 * @param array $params
	 * @return string
	 */
	static function currentUrlXml($params = array())
	{
		return self::currentUrl($params, true);
	}

	static function setDefaultLibraryRoot($dir)
	{
		self::$defaultLibraryRoot = $dir;
	}
	
	/**
	 * Autoload
	 *
	 * @param string $class
	 * @return bool
	 */
	static function autoload($class)
	{
		$class = str_replace('\\', '_', $class);
		if (false === ($p = strpos($class, '_')))
		{
			$libraryName = $class;
		} else {
			$libraryName = substr($class, 0, $p);
		}

		if (array_key_exists($libraryName, self::$autoload_map))
		{
			$root = self::$autoload_map[$libraryName];
		} else {
			$root = self::$defaultLibraryRoot;
		}

		if (is_file($filename = rtrim($root, "/") . "/" . str_replace('_', '/', $class) . '.php'))
		{
			include_once $filename;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Get configuration entry
	 *
	 * @param string $name
	 */
	static function config($name)
	{
		return array_key_exists($name, self::$settings) ? self::$settings[$name] : null;
	}
	
	static function errorHandler($errno, $errstr)
	{
		if ($errno & error_reporting())
		{
			throw new Exception("PHP error " . $errstr);
		}
	}
	
	static function exceptionHandler(Exception $exception)
	{
		$handlers = (array) self::config('exception.final_catchers');
		foreach ($handlers as $exceptionClass => $handlerClass)
		{
			if ($exception instanceof $exceptionClass)
			{
				$handler = new $handlerClass($exception);
				return $handler->run();
			}
		}
	}

	/**
	 * Setup Naf application
	 */
	static function setup()
	{
//		set_error_handler(array(__CLASS__, 'errorHandler'));
//		set_exception_handler(array(__CLASS__, 'exceptionHandler'));
	}
	
	/**
	 * @return PDO
	 */
	static function pdo()
	{
		if (is_object(self::$pdo))
		{
			return self::$pdo;
		} else {
			// @todo lazy connection w/help of Naf_Proxy
			self::$pdo = new PDO(
				self::$settings['database']['dsn'],
				self::$settings['database']['username'],
				self::$settings['database']['password'],
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
			);
			foreach ((array) @self::$settings['database']['startup_queries'] as $sql)
			{
				self::$pdo->exec($sql);
			}
			if ('mysql' == self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME))
			{
				self::$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
			}
			return self::$pdo;
		}
	}
	
	/**
	 * $map is an array with trigger names as keys and post-handlers as elements.
	 * A trigger is a $_POST key that must be present in $_POST for the corresponding action
	 * to happen. Only the first trigger that is met in the $_POST array, is processed,
	 * all the others are ignored.
	 *
	 * $trigger is also the name of the post-handler class method to be executed.
	 *
	 * Example: $_POST = array('save' => 'Ok');
	 * the call to LNaf\handlePost(array('save' => 'SomeSaviour')); - will create
	 * an instance of SomeSaviour, which MUST implement method named 'save'
	 *
	 * @param array $map (TRIGGER => action,..)
	 */
	static function handlePost($map, $view = 'ajax')
	{
		if ('POST' != $_SERVER['REQUEST_METHOD'])
		{
			return false;
		}
		
		foreach ($map as $trigger => $handler)
		{
			if (array_key_exists($trigger, $_POST))
			{
				if (is_string($handler))
				{
					$handler = new $handler();
				}
				
				$handler->$trigger($_POST);
				self::response()->setView($view);
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Assert request is done using POST method
	 * @param callback $callback called on method mismatch, defaults to exit()
	 */
	static function assertPost($callback = null)
	{
		if ('POST' != $_SERVER['REQUEST_METHOD'])
		{
			if ($callback)
				call_user_func($callback);
			else
				exit();
		}
	}
	
	/**
	 * Get Response model
	 *
	 * @return naf\core\Response
	 */
	static function response()
	{
		if (is_object(self::$response))
		{
			return self::$response;
		} else {
			return self::$response = new naf\core\Response();
		}
	}
	
	static function forceAjaxResponse()
	{
		self::response()->ajaxResponseForced = true;
		self::response()->setAjaxData(null);
	}

	/**
	 * @return naf\controller\Router;
	 */
	static public function getRouter()
	{
		if (! self::$router) {
			self::$router = new naf\controller\Router_Path();
		}
		return self::$router;
	}

	static public function setRouter($router)
	{
		self::$router = $router;
	}
	
	/**
	 * @param string $action If not specified, the current action is used
	 * @param array $params
	 * @return string
	 */
	static public function redirect($action = null, array $params = array())
	{
		self::redirectUrl(self::url($action, $params));
	}

	/**
	 * @param string $url
	 * @return void
	 */
	static public function redirectUrl($url)
	{
		header('Location: ' . $url);
		exit();
	}
}