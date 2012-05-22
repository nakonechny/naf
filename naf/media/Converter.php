<?php
namespace naf\media;

abstract class Converter extends ShellCmdWrapper
{
	/**
	 * @var Info
	 */
	protected $outputInfo;
	
	/**
	 * @var string
	 */
	protected $source;
	
	/**
	 * Constructor
	 *
	 * @param string $source
	 * @throws Fault
	 */
	function __construct($command, $source)
	{
		parent::__construct($command);
		
		if (! is_file($source) || ! is_readable($source))
			throw new Fault("File is unreadable or does not exist");
		
		$this->source = $source;
		$this->outputInfo = new Info();
	}
	
	/**
	 * __magic__ call: wrap non-existant methods.
	 * Delegates to $this->outputInfo.
	 *
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	final function __call($name, $args)
	{
		if (method_exists($this->outputInfo, $name))
			return call_user_func_array(array($this->outputInfo, $name), $args);
		
		throw new Fault("Method $name does not exist");
	}
	
	/**
	 * Convert media according to specifications in $this->outputInfo.
	 *
	 * @param string $filename - passed by reference because sometimes there is a need to change the filename,
	 * 								or otherwise the backend will produce an error
	 * @throws Exception
	 */
	abstract function convert(&$filename, $start = 0, $duration = null);
}