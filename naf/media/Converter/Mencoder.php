<?php
namespace naf\media;

use naf\util\ShellCmd;

class Converter_Mencoder extends Converter
{
	protected $defaultCommand = 'mencoder';
	
	/**
	 * Constructor
	 *
	 * @param string $command
	 * @param string $source
	 * @throws Exception
	 */
	function __construct($command, $source)
	{
		parent::__construct($command, $source);
		$this->outputInfo->setAudioCodec('copy')
			->setVideoCodec('copy');
	}
	
	/**
	 * @param string $filename
	 * @param int | string $start
	 * @param int | string $duration
	 * @throws Exception
	 */
	function convert(&$filename, $start = 0, $duration = null)
	{
		$c = new ShellCmd($this->command);
		
		if (($width = $this->outputInfo->getWidth()) && ($height = $this->outputInfo->getHeight()))
		{
			// We have to resize the movie.
			if (! $this->outputInfo->getFormat())
			{
				/* Output format not specified. By default, we should use 'copy',
					but we're not able actually, since if video-stream is copied (not encoded!), 
					then no scaling will be applied. Force format. */
				$ext = substr($this->source, strrpos($this->source, '.') + 1);
				$this->outputInfo->setFormat($ext);
			}
			
			$c->addOption('-vf', 'scale=' . $width . ':' . $height);
		}
		
		$cfg = $this->createConfigurator();
		$filename = $cfg->filename($filename);
		$c->addOption('-o', $filename);
		$cfg->configure($c);
		
		$c->addOptionIf($start, '-ss', $start);
		$c->addOptionIf($duration, '-endpos', $duration);
		$c->setTarget($this->source);
		
		try {
			$c->exec();
		} catch (\Exception $e) {
			throw new Exception("Mplayer call failed: " . $e->getMessage()
					. "\nCommand: " . $c->getLastCommannd());
		}
	}
	
	/**
	 * Create command-line configurator
	 *
	 * @return Converter_Mencoder_Format
	 */
	function createConfigurator()
	{
		$format = ucfirst(strtolower($this->outputInfo->getFormat()));
		if (! strlen($format))
			$format = 'LeaveAsIs';
		
		$class = __CLASS__ . '_' . $format;
		if (class_exists($class, false) || is_file(dirname(__FILE__) . '/Mencoder/' . $format . '.php'))
			return new $class($this->outputInfo);
		
		throw new Exception("Could not convert to '$format': no such converter.");
	}
}