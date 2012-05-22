<?php 
namespace naf\log;

class TextFileLog_Message extends TextFileLog
{
	/**
	 * @param mixed $message
	 * @return bool
	 */
	public function write($message)
	{
		$this->appendLine($this->getTimestamp() . ' ' . $message);
		return $this->flush();
	}
}
