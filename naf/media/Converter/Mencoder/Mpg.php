<?php
namespace naf\media;

use naf\util\ShellCmd;

class Converter_Mencoder_Mpg extends Converter_Mencoder_Format
{
	/**
	 * Configure shell-command
	 *
	 * @param ShellCmd $c
	 */
	function configure(ShellCmd $c)
	{
		$c->addOption('-ovc', 'lavc');
		
		/* @todo: this code for bitrate detection should be encapsulated in a method */
		$vbitrate = $this->info->getBitrate();
		if (! $vbitrate) $vbitrate = 100;// 100 is orten good enough and small enough
		$c->addOption('-lavcopts', 'vcodec=mpeg1video:vbitrate=' . $vbitrate);
		
		$c->addOption('-of', 'lavf');
		/* have to use this ugly option due to a bug in mencoder */
		$c->addOption('-lavfopts', 'format=mpg:i_certify_that_my_video_stream_does_not_use_b_frames');
		
		$c->addOption('-oac', $this->info->getAudioCodec());
	}
	
	/**
	 * Get filename for the format.
	 * Mencoder expects the output mpg file to have a mpg extension
	 *
	 * @override
	 * @param string $filename
	 * @return string
	 */
	function filename($filename)
	{
		if ('mpg' !== $this->extension($filename))
			$filename .= '.mpg';
		return $filename;
	}
}