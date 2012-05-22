<?php
namespace naf\util;

/**
 * Encapsulation for a shell-command.
 * 
 * Takes care of escaping args,
 * 
 * also captures STDOUT and STDERR - separately!
 *
 */

class ShellCmd
{
	/**
	 * STDERR output is captured in a file in a temp dir
	 *
	 * @var string
	 */
	private static $tmpDir = '/tmp/';
	
	/**
	 * @var string
	 */
	private $command;
	
	/**
	 * Command options
	 *
	 * @var string[]
	 */
	private $opts = array();
	
	/**
	 * Target - the last option for the command,
	 * which is often the target file name
	 *
	 * @var string
	 */
	private $target;
	
	/**
	 * Last (executed!) command details
	 *
	 * @var string
	 */
	private $lastCommand, $lastResponse, $lastError;
	
	/**
	 * Constructor
	 *
	 * @param string $command
	 */
	function __construct($command)
	{
		$this->command = $command;
	}
	
	/**
	 * @param string $option
	 * @param string $value
	 */
	function addOption($option, $value = null)
	{
		if (null !== $value)
		{
			if (is_numeric($value))
				$option .= " " . $value;
			else
				$option .= " " . escapeshellarg($value);
		}
		
		$this->opts[$option] = $option;
	}
	
	/**
	 * Add an option but ONLY if $condition evaluates to TRUE.
	 * 
	 * @param bool $condition
	 * @param string $option
	 * @param string $value
	 */
	function addOptionIf($condition, $option, $value = null)
	{
		if ($condition) $this->addOption($option, $value);
	}
	
	/**
	 * @param string $target
	 */
	function setTarget($target)
	{
		$this->target = escapeshellarg($target);
	}
	
	/**
	 * Execute the command
	 *
	 * @param bool $suppressErrors When set to TRUE, Exception won't be thrown upon error in command execution
	 * @return string either STDOUT or STDERR output, whatever is not empty (of course, it defaults to STDOUT)
	 */
	function exec($suppressErrors = false)
	{
		// $logfile is where the STDERR output is captured
		$logfile = self::$tmpDir . uniqid();
		
		$this->lastCommand = $this->command . " " . 
			implode(" ", $this->opts) . " " . 
			$this->target;

		ob_start();
		passthru($this->lastCommand . " 2>" . escapeshellarg($logfile), $err);// NOTE THE 2>$logfile
		$this->lastResponse = ob_get_clean();
		
		$this->lastError = @file_get_contents($logfile);
		@unlink($logfile);
		
		if ($err && ! $suppressErrors)
			throw new ShellCmd\Fault("Command " . $this->lastCommand . " produced an error: " . $this->lastError);
		
		return strlen($this->lastResponse) ? $this->lastResponse : $this->lastError;
	}
	
	function getLastCommand()
	{
		return $this->lastCommand;
	}
	function getLastResponse()
	{
		return $this->lastResponse;
	}
	function getLastError()
	{
		return $this->lastError;
	}
	
	static function setTmpDir($dir)
	{
		$dir = rtrim($dir);
		
		if (! is_dir($dir))
			throw new ShellCmd\Fault("temporary directory is not a directory");
		if (! is_writable($dir))
			throw new ShellCmd\Fault("temporary directory is not writable");
		
		self::$tmpDir = $dir . '/';
	}
}