<?php
namespace naf\media;

use naf\util\ShellCmd;
use naf\media\ShellCmdWrapper;

class InfoReader_Mplayer extends ShellCmdWrapper implements InfoReader
{
	/**
	 * Read media file information.
	 *
	 * @param string filename
	 * @return Info
	 */
	function info($filename)
	{
		$i = new Info($filename);
		
		$cmd = new ShellCmd($this->command);
		$cmd->addOption('-identify');
		$cmd->addOption('-frames', 0);
		$cmd->addOption('-ao', 'null');
		$cmd->addOption('-vo', 'null');
		$cmd->setTarget($filename);
		
		$infoText = $cmd->exec();
		
		$w = $h = 0;
		foreach (preg_split("/\r\n|\r|\n/", $infoText) as $line) {
			if (preg_match('/^ID_([^\=]+)=(.+)$/', $line, $matches))
			{
				$key = $matches[1];
				$value = $matches[2];
				if (0 === strpos($key, 'VIDEO'))
					$i->setHasVideo(true);
				elseif (0 === strpos($key, 'AUDIO'))
					$i->setHasAudio(true);
				
				switch ($key)
				{
					case "VIDEO_WIDTH":
						$w = (int) $value;
						break;
					case "VIDEO_HEIGHT":
						$h = (int) $value;
						break;
					case "VIDEO_FPS":
						$i->setFps($value);
						break;
					case "AUDIO_BITRATE":
						$i->setAudioBitrate($value / 1000);
						break;
					case "AUDIO_RATE":
						$i->setSamplingRate($value);
						break;
					case "LENGTH":
						$i->setDuration($value);
						break;
					case "AUDIO_CODEC":
						$i->setAudioCodec($value);
						break;
					case "VIDEO_CODEC":
						$i->setVideoCodec($value);
						break;
					default:
						break;
				}
			}
			elseif (preg_match('/^([^\s]+) file format detected/', $line, $matches))
			{
				$i->setFormat($matches[1]);
			}
		}
		
		if ($i->hasVideo())
		{
			$i->setPixelSize($w, $h);
			
			$duration = $i->getDuration();
			if (0 == $duration)// experimentally it has been found that 5 seconds will be ok here...
				$duration = 5;
			
			$i->setBitrate(((filesize($filename) / 1024) * 8) / $duration);
		}
		
		return $i;
	}
}