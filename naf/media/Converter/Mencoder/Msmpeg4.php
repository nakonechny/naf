<?php
namespace naf\media;

use naf\util\ShellCmd;

class Converter_Mencoder_Msmpeg4 extends Converter_Mencoder_Format
{
	/**
	 * Configure shell-command
	 *
	 * @param ShellCmd $c
	 */
	function configure(ShellCmd $c)
	{
		$c->addOption('-ovc', 'lavc');
		
		$vbitrate = $this->info->getBitrate();
		if (! $vbitrate) $vbitrate = 64;// 64 is default value of ffmpeg. low quality, low size.
		$c->addOption('-lavcopts', 'vcodec=wmv2:vbitrate=' . $vbitrate);
		
		$c->addOption('-oac', $this->info->getAudioCodec());
	}
}