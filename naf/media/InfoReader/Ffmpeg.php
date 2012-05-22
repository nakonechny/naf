<?php
namespace naf\media;

use naf\util\ShellCmd;
use naf\media\ShellCmdWrapper;

class InfoReader_Ffmpeg extends ShellCmdWrapper implements InfoReader
{
	protected $defaultCommand = 'ffmpeg';
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
		$cmd->addOption('-i', $filename);
		$infoText = $cmd->exec(true);// need to suppress errors due to ffmpeg producing error in case no output file is specified
		if ((! preg_match('~Input\\s#0,\s([^,]+)~m', $infoText, $matches1)) || 
			(! preg_match('~\s+Duration\:\s+(\d{2}\:\d{2}\:\-?\d{1,2}\.\d{1,2})(?:,\s+start\:\s+\d+\.\d+)?,\s+bitrate\:\s+\-?(\d+|N/A)(?:\s+kb/s)?\s*~m', $infoText, $matches2)))
		{
			throw new Exception("Unable to read movie info from $infoText");
		}
		
		$i->setFormat($matches1[1]);
		$i->setDuration($matches2[1]);
		
		$bitrate = (int) $matches2[2];
		if (! $bitrate) $bitrate = 200;
		$i->setBitrate($bitrate);
		
		if (preg_match('~.+Audio\:\s+([a-zA-Z0-9\-\_]+),\s+(\d+)\s+Hz(?:,\s+(?:stereo|mono))(?:,\s+[a-zA-Z0-9_\-])?,\s+(\d+)\s+kb/s~m', $infoText, $matches))
		{
			$i->setHasAudio(true);
			$i->setAudioCodec($matches[1]);
			$i->setSamplingRate($matches[2]);
			$i->setAudioBitrate($matches[3]);
		}

		if (preg_match('~\n([^\n]+Video\:\s+(\S+)[^\n]+)\n~m', $infoText, $matches)) {
			$videoInfo = $matches[1];
			$i->setHasVideo(true);
			$i->setVideoCodec($matches[2]);
			if (preg_match('~(\d{2,})x(\d+)~', $videoInfo, $matches)) {
				$i->setPixelSize($matches[1], $matches[2]);
			}
			if (preg_match('~(\d+\.\d+)\s+fps\(r\)~', $videoInfo, $matches)) {
				$i->setFps($matches[1]);
			}
		}
		
		return $i;
	}
}