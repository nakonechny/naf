<?php
namespace naf\media;

use naf\util\ShellCmd;

abstract class Converter_Mencoder_Format
{
	/**
	 * @var Info
	 */
	protected $info;
	
	/**
	 * Constructor
	 *
	 * @param Info $i
	 */
	final function __construct(Info $i)
	{
		$this->info = $i;
	}
	/**
	 * Configure shell-command
	 *
	 * @param ShellCmd $c
	 */
	abstract function configure(ShellCmd $c);
	
	/**
	 * Get filename for the format.
	 * Usually can be left unchanged
	 *
	 * @param string $filename
	 * @return string
	 */
	function filename($filename)
	{
		return $filename;
	}
	
	protected function extension($filename)
	{
		return substr($filename, strrpos($filename, '.') + 1);
	}
}