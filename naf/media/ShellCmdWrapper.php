<?php
namespace naf\media;

abstract class ShellCmdWrapper
{
	/**
	 * Shell command to invoke (i. e. mencoder or ffmpeg or smth)
	 *
	 * @var string
	 */
	protected $command;
	
	/**
	 * Default command (one that is used when nothing is specified in the constructor)
	 *
	 * @var string
	 */
	protected $defaultCommand;
	
	/**
	 * Constructor
	 *
	 * @param string $command
	 */
	function __construct($command = null)
	{
		if (null === $command)
			$this->command = $this->defaultCommand;
		else
			$this->command = $command;
	}
}