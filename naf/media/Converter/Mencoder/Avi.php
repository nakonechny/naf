<?php
namespace naf\media;

use naf\util\ShellCmd;

class Converter_Mencoder_Avi extends Converter_Mencoder_Format
{
	/**
	 * Configure shell-command
	 *
	 * @param ShellCmd $c
	 */
	function configure(ShellCmd $c)
	{
		$f = $this->info->getFormat();
		$c->addOptionIf($f, '-of', $f);
		$c->addOption('-ovc', $this->info->getVideoCodec());
		$c->addOption('-oac', $this->info->getAudioCodec());
	}
}