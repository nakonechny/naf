<?php
namespace naf\media;

use naf\util\ShellCmd;

class Converter_Mencoder_LeaveAsIs extends Converter_Mencoder_Format
{
	/**
	 * Configure shell-command.
	 *
	 * @param ShellCmd $c
	 */
	function configure(ShellCmd $c)
	{
		$c->addOption('-ovc', $this->info->getVideoCodec());
		$c->addOption('-oac', $this->info->getAudioCodec());
	}
}