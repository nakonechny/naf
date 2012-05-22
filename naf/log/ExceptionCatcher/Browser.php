<?php
/**
 * Browser-logger. Display exception info in a (quite convinient) browser interface
 * 
 * $Id: BrowserLog.php 188 2008-04-08 07:03:13Z vbolshov $
 * 
 * @package naf\log
 */
namespace naf\log;

class ExceptionCatcher_Browser extends ExceptionCatcher
{
	/**
	 * Displays exception information
	 */
	function run()
	{
		echo '<h2>' . get_class($this->exception) . ':</h2>';
		echo '<h1>' . $this->exception->getMessage() . '</h1>';
		echo <<<SCRIPT
<script>
function exception_browser_toogle(id) {
	var style = document.getElementById(id).style
	if (style.display == 'block')
		style.display = 'none'
	else
		style.display = 'block'
	
	return false
}
</script>
SCRIPT;
		echo '<ol>';
		
		foreach ($this->exception->getTrace() as $num => $trace)
		{
			$text  = empty($trace['file']) ? 'Unknown file' : $trace['file'];
			$text .= ', ';
			$text .= empty($trace['line']) ? 'Unknown line' : $trace['line'];
			echo '<li>';
			echo '<a href="#" onclick="return exception_browser_toogle(\'m' . $num . '\')" >' . $text . '</a>';
			echo '<pre id="m' . $num . '" style="display:none;">';
			var_dump($trace);
			echo '</pre>';
			echo '</li>';
		}
		
		echo '</ol>';
	}
}