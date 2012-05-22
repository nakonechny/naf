<?php

/**
 * Class encapsulation for HTTP response (including AJAX response)
 */

namespace naf\core;

class Response {

	/**
	 * View template to render
	 *
	 * @var string
	 */
	protected $_view;
	
	/**
	 * Response data
	 *
	 * @var mixed[] associative array
	 */
	protected $_data = array();
	
	/**
	 * @var string
	 */
	protected $_title;
	
	/**
	 * @var array
	 */
	protected $_keywords = array();
	/**
	 * @var array
	 */
	protected $_description;
	
	/**
	 * @var string
	 */
	protected $_status = '200 OK';
	/**
	 * @var string
	 */
	protected $_contentType = 'text/html';
	/**
	 * @var string
	 */
	protected $_charset = 'utf-8';
	/**
	 * @var string
	 */
	protected $_language = 'en';
	/**
	 * @var string
	 */
	protected $_lastModified;
	
	/**
	 * @param string $view
	 */
	function setView($view)
	{
		$this->_view = $view;
	}
	/**
	 * @return string
	 */
	function getView()
	{
		return $this->_view;
	}
	
	/**
	 * @param string $title
	 */
	function setTitle($title)
	{
		$this->_title = $title;
	}
	/**
	 * @return string
	 */
	function getTitle()
	{
		return $this->_title;
	}
	
	/**
	 * @param array | string $keywords
	 */
	function addKeywords($keywords)
	{
		$this->_keywords = array_merge($this->_keywords, (array) $keywords);
	}
	/**
	 * @param bool $asString
	 * @return array | string
	 */
	function getKeywords($asString = false)
	{
		if ($asString)
			return implode(', ', $this->_keywords);
		
		return $this->_keywords;
	}
	
	/**
	 * @param string $description
	 */
	function setDescription($description)
	{
		$this->_description = $description;
	}
	/**
	 * @return string
	 */
	function getDescription()
	{
		return $this->_description;
	}
	
	/**
	 * @param string $contentType
	 */
	function setContentType($contentType)
	{
		$this->_contentType = $contentType;
	}
	/**
	 * @return string
	 */
	function getContentType()
	{
		return $this->_contentType;
	}
	
	/**
	 * @param string $language
	 */
	function setLanguage($language)
	{
		$this->_language = $language;
	}
	/**
	 * @return string
	 */
	function getLanguage()
	{
		return $this->_language;
	}
	
	/**
	 * @param string $status
	 */
	function setStatus($status)
	{
		$this->_status = $status;
	}
	/**
	 * Send status header
	 */
	function exposeStatus()
	{
		$this->header("HTTP/1.0 " . $this->_status);
	}
	
	/**
	 * @param string $charset
	 */
	function setCharset($charset)
	{
		$this->_charset = $charset;
	}
	/**
	 * @return string
	 */
	function getCharset()
	{
		return $this->_charset;
	}
	
	/**
	 * @param string $datetime
	 */
	function setLastModified($datetime)
	{
		if (($time = strtotime($datetime)) > $this->_lastModified)
			$this->_lastModified = $time;
	}
	
	/**
	 * Send Content-Type header
	 */
	function exposeContentType()
	{
		$this->header("Content-Type: " . $this->_contentType . "; charset=" . $this->_charset);
	}
	
	/**
	 * Send Content-Language header
	 */
	function exposeLanguage()
	{
		$this->header("Content-Language: " . $this->_language);
	}
	
	/**
	 * Send Last-Modified header
	 */
	function exposeLastModified()
	{
		if (null === $this->_lastModified) return;
		$this->header("Last-Modified: " . gmdate('D, d M Y H:i:s', $this->_lastModified) . " GMT");
	}
	
	/**
	 * Set response to AJAX-request
	 *
	 * @param array $errorList
	 * @param mixed $data
	 */
	function setAjaxResponse($errorList, $data = null)
	{
		$this->_data['ajax'] = array(
			'errorList' => ($errorList === null) ? null : (array) $errorList,
			'error_list' => ($errorList === null) ? null : (array) $errorList,
			'data' => $data);
	}
	/**
	 * @param array $errorList
	 */
	function setAjaxError($errorList)
	{
		$this->setAjaxResponse($errorList);
	}
	/**
	 * @param mixed $data
	 */
	function setAjaxData($data)
	{
		$this->setAjaxResponse(null, $data);
	}
	
	function get($name, $default = null)
	{
		if (array_key_exists($name, $this->_data))
			return $this->_data[$name];
		else
			return $default;
	}
	
	function __get($name)
	{
		if (array_key_exists($name, $this->_data))
			return $this->_data[$name];
	}
	
	function __set($name, $value)
	{
		$this->_data[$name] = $value;
	}
	
	function export()
	{
		return $this->_data;
	}
	
	private function header($h)
	{
		if (! headers_sent())
		{
			header($h);
		}
	}
}