<?php
namespace naf\media;
use naf\util\ShellCmd;

class Snapshot_Ffmpeg extends Snapshot
{
	function save($filename, $start = 0)
	{
		$c = new ShellCmd($this->command);
		$c->addOption('-i', $this->source);
		$c->addOption('-f', 'image2');
		$c->addOption('-vframes', 1);
		$c->addOptionIf($start, '-ss', $start);
		
		$c->addOptionIf(($this->width && $this->height), '-s', $this->width . 'x' . $this->height);
		
		if (null === $filename)
		{
			$c->setTarget('-');// flush picture to STDOUT
			try {
				return $c->exec();
			} catch (ShellCmd\Fault $e) {
				throw new Fault("Snapshot failed! " . $e->getMessage());
			}
		} else {
			$c->setTarget($filename);// save to file
			try {
				$c->exec();
				return ;
			} catch (ShellCmd\Fault $e) {
				throw new Fault("Snapshot failed! " . $e->getMessage());
			}
		}
	}
}