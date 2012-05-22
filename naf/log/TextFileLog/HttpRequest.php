<?php 
namespace naf\log;

class TextFileLog_HttpRequest extends TextFileLog_Data
{
	public function write($data = null)
	{
		$this->appendLine($this->getTimestamp());
		$this->appendLine(filter_var_array($_SERVER, array(
			'REMOTE_ADDR' 		=> FILTER_SANITIZE_STRING,
			'HTTP_USER_AGENT' 	=> FILTER_SANITIZE_STRING,
			'HTTP_REFERER' 		=> FILTER_SANITIZE_STRING,
		)));
		$this->appendLine(array(
			'GET' => $_GET,
			'POST' => $_POST,
			'COOKIE' => $_COOKIE,
		));
		if ($data) {
			$this->appendLine($data);
		}
		$this->appendLine();
		return $this->flush();
	}
}