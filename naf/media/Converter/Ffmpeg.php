<?php
namespace naf\media;

use naf\util\ShellCmd;

class Ffmpeg extends Converter
{
	protected $defaultCommand = 'ffmpeg';
	
	/**
	 * Convert media according to specifications in $this->outputInfo.
	 *
	 * @param string $filename - passed by reference because sometimes there is a need to change the filename,
	 * 								or otherwise the backend will produce an error
	 * @throws Exception
	 */
	function convert(&$filename, $start = 0, $duration = null)
	{
		$c = new ShellCmd($this->command);
		
		$format = $this->outputInfo->getFormat();
		if ($format)
		{
			$ext = substr($filename, strrpos($filename, '.') + 1);
			if ($ext != $format)
				$filename .= '.' . $format;
		} else {
			$ext = substr($this->source, strrpos($this->source, '.') + 1);
			$filename .= '.' . $ext;
		}

		$c->addOption('-i', $this->source);
		$c->setTarget($filename);
		
		$c->addOptionIf($start, '-ss', $start);
		$c->addOptionIf($duration, '-t', $duration);
		
		if (($width = $this->outputInfo->getWidth()) && ($height = $this->outputInfo->getHeight()))
		{
			$c->addOption('-s', $width . 'x' . $height);
		}
		
		$bitrate = $this->outputInfo->getBitrate();
		$c->addOptionIf($bitrate, '-b', (int) $bitrate . "k");
		
		try {
			$c->exec();
		} catch (\Exception $e) {
			throw new Exception("Ffmpeg call failed: " . $e->getMessage()
					. "\nCommand: " . $c->getLastCommannd());
		}
	}
}