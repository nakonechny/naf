<?php
namespace naf\log;

abstract class TextFileLog
{
	protected $filename;
	private $buffer;

	/**
	 * @param string $filename 
	 */
	public function __construct($filename)
	{
		$this->filename = $filename;
	}

	protected function appendLine($string = '')
	{
		$this->buffer .= $string . "\n";
	}

	/**
	 * @return bool
	 */
	protected function flush()
	{
		$is_written = file_put_contents($this->filename, $this->buffer, FILE_APPEND);
		$this->buffer = '';

		return $is_written;
	}

	protected function getTimestamp()
	{
		return date('Y-m-d H:i:s');
	}

	abstract public function write($string);
}