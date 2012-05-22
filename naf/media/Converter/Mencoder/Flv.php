<?php
namespace naf\media;

use naf\util\ShellCmd;

class Converter_Mencoder_Flv extends Converter_Mencoder_Format
{
	/**
	 * Configure shell-command
	 *
	 * @param ShellCmd $c
	 */
	function configure(ShellCmd $c)
	{
		$c->addOption('-of', 'lavf');
		/* have to add this ugly option due to mencoder bug */
		$c->addOption('-lavfopts', 'i_certify_that_my_video_stream_does_not_use_b_frames');
		$c->addOption('-ovc', 'lavc');
		
		$vbitrate = $this->info->getBitrate();
		if (! $vbitrate) $vbitrate = 64;// 64 is default value of ffmpeg. low quality, low size.
		$c->addOption('-lavcopts', 'vcodec=flv:vbitrate=' . $vbitrate . ':mbd=2:mv0:trell:v4mv:cbp:last_pred=3');
		
		$c->addOption('-oac', 'mp3lame');
		$c->addOption('-lameopts', 'abr:br=56');
		$c->addOption('-srate', '44100');// @todo: take this value from info
	}
	
	/**
	 * Get filename for the format.
	 * Mencoder expects the output flv file to have a flv extension
	 *
	 * @override
	 * @param string $filename
	 * @return string
	 */
	function filename($filename)
	{
		if ('flv' !== $this->extension($filename))
			$filename .= '.flv';
		return $filename;
	}
}