<?php
namespace naf\media;
use naf\util\ShellCmd;

class Snapshot_Mplayer extends Snapshot
{
	function save($filename, $start = 0)
	{
		$c = new ShellCmd($this->command);
		$c->setTarget($this->source);
		$c->addOption('-vo', 'jpeg:outdir=' . $this->tmpDir);
		$c->addOption('-nosound');
		$c->addOption('-frames', 2);
		$c->addOptionIf($start, '-ss', $start);
		
		$c->addOptionIf(($this->width && $this->height), '-vf', 'scale=' . $this->width . ':' . $this->height);
		
		try {
			$c->exec();
		} catch (ShellCmd\Fault $e) {
			throw new Fault("Snapshot failed! " . $e->getMessage());
		}
		
		@unlink($this->tmpDir . '/00000001.jpg');
		$image = $this->tmpDir . '/00000002.jpg';
		if ($filename)
		{
			rename($image, $filename);
			return ;
		} else {
			$return = file_get_contents($image);
			@unlink($image);
			return $return;
		}
	}
}