<?php

namespace naf\controller;

class Router_Path extends Router
{
	public function assemble($path, $params, $is_xml)
	{
		$query = http_build_query($params, null, $this->getParamsSeparator($is_xml));
		if (strlen($query))
		{
			return $path . '?' . $query;
		} elseif (strlen($path)) {
			return $path;
		} else {
			return '?';
		}
	}

	protected function resolveController()
	{
		return substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '.'));
	}
}