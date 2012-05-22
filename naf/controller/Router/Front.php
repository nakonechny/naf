<?php

namespace naf\controller;

class Router_Front extends Router
{
	protected $base = '';

	public function assemble($controller, $params, $is_xml)
	{
		$params['ctrl'] = str_replace('/', '.', $controller);
		$sessionName = session_name();
		if ((! isset($_COOKIE[$sessionName])) &&
			(isset($_GET[$sessionName]) || isset($_POST[$sessionName])) &&
			ini_get('session.use_trans_sid'))
		{
			$params[$sessionName] = session_id();
		}

		return $this->base . '?' . http_build_query($params, null, $this->getParamsSeparator($is_xml));
	}

	/**
	 *
	 * @param string $string
	 */
	public function setBase($string)
	{
		$this->base = $string;
	}

	protected function resolveController()
	{
		if (isset($_REQUEST['ctrl']) && is_string($_REQUEST['ctrl']))
			return $this->_escapeAction($_REQUEST['ctrl']);
		else
			return 'index';
	}

	protected function _escapeAction($name)
	{
		return str_replace('.', '/', str_replace('../', '', trim($name, ' /.')));
	}

}