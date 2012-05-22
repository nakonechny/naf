<?php
namespace naf\controller;

abstract class Router
{
	static protected $params_separator_http = '&';
	static protected $params_separator_xml = '&amp;';

	/**
	 * @var string
	 */
	protected $controller;

	/**
	 * @param string $controller
	 * @param array $params
	 * @param boolean $is_xml
	 * @return string
	 */
	abstract public function assemble($controller, $params, $is_xml);

	/**
	 * @param string $url
	 * @return string controller name
	 */
	abstract protected function resolveController();

	/**
	 * @return string
	 */
	public function getController()
	{
		if (null === $this->controller) {
			$this->controller = $this->resolveController();
		}
		
		return $this->controller;
	}

	protected function getParamsSeparator($is_xml)
	{
		return $is_xml ? static::$params_separator_xml : static::$params_separator_http;
	}
}