<?php
namespace naf\media;

abstract class Snapshot extends ShellCmdWrapper
{
	/**
	 * Temp dir
	 *
	 * @var string
	 */
	protected $tmpDir;
	
	/**
	 * Source movie filename
	 *
	 * @var string
	 */
	protected $source;
	
	/**
	 * Snapshot size in pixels
	 *
	 * @var int
	 */
	protected $width, $height;
	
	/**
	 * Constructor
	 *
	 * @param string $command
	 * @param string $source
	 * @param string $tmpDir
	 * @throws naf\media\Exception
	 */
	function __construct($command, $source, $tmpDir = '/tmp')
	{
		parent::__construct($command);
		$this->source = $source;
		$this->tmpDir = $tmpDir;
	}
	
	/**
	 * Save a snapshot
	 *
	 * @param string $filename Specify NULL to force return of the picture contents
	 * @param int | float | string $start either number of seconds from start or time string HH:MM:SS.F
	 * @return string | void depending on $filename being NULL
	 * @throws Exception
	 */
	abstract function save($filename, $start = 0);
	
	/**
	 * @param int $width
	 * @param int $height
	 */
	final function setSize($width, $height)
	{
		$this->width = $width;
		$this->height = $height;
	}
}