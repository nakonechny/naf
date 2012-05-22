<?php

/**
 * For long-lasting tasks, that the user invokes using Web-interface,
 * it might be useful for the user to stay informed about the state
 * of the task that is being performed (then, at least, the user will know that the
 * process is not hung up)
 *
 * Naf_Progress is a simple backend for task-state monitor.
 * 
 * $Id: Progress.php 184 2008-04-07 09:18:44Z vbolshov $
 * 
 * @package naf\util
 * @subpackage Progress
 * @copyright Victor Bolshov <crocodile2u@gmail.com>
 */

namespace naf\util;
use naf\util\Progress\Fault;

class Progress {
	/**
	 * @var string
	 */
	private $taskId;
	/**
	 * @var string
	 */
	private $tmpdir = '/tmp';
	private $destroy;
	private $start;
	
	function __construct($taskId)
	{
		$this->taskId = $taskId;
		$this->start = time();
	}
	
	function setDestroy($newValue)
	{
		$this->destroy = $newValue;
	}
	
	/**
	 * @return Naf_ProgressBarBackend_State
	 */
	function getState()
	{
		if (is_file($f = $this->filename()))
		{
			@list($percent, $status, $elapsed) = explode("||", file_get_contents($f), 3);
		} else {
			// seems like task is already complete
			$percent = 100;
			$status = '';
			$elapsed = 10;
		}
		
		return array('percent' => $percent, 'status' => $status, 'elapsed' => $elapsed);
	}
	
	/**
	 * @param int $percentDone
	 * @param string $statusString
	 */
	function update($percentDone, $statusString)
	{
		if (null === $this->destroy)
		{
			$this->destroy = true;
		}
		file_put_contents($this->filename(), $percentDone . '||' . $statusString . '||' . (time() - $this->start));
	}
	
	/**
	 * @param string $dir
	 * @throws naf\util\Progress\Fault
	 */
	function setTmpDir($dir)
	{
		if (! is_string($dir))
		{
			throw new Fault("Argument 1 is expected to be a string, " . gettype($dir) . " given");
		}
		if (! is_dir($dir))
		{
			throw new Fault("$dir is not a directory");
		}
		if (! is_writable($dir))
		{
			throw new Fault("$dir is not writable");
		}
		
		$this->tmpdir = rtrim($dir, '/ ');
	}
	
	private function filename()
	{
		return $this->tmpdir . '/' . $this->taskId . '.npbb';
	}
	
	/**
	 * delete the temporary file if necessary
	 */
	function __destruct()
	{
		if ($this->destroy && is_file($f = $this->filename()))
		{
			@unlink($f);
		}
	}
}