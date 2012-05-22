<?php

namespace naf\view;

class AjaxError {
	private $errorList;
	private $forceAjaxResponse;
	function __construct($errorList, $forceAjaxResponse = false)
	{
		$this->errorList = (array) $errorList;
		$this->forceAjaxResponse = (bool) $forceAjaxResponse;
	}
	function render()
	{
		$result = array(
			'errors' => array_values($this->errorList)
		);
		
		/**
		 * @todo respect charset settings
		 */

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $this->forceAjaxResponse)
		{
			/*
			 *  we are really ajax OR should be ajax anyway
			 */
			header('Content-Type: application/json; Charset=UTF-8');
			echo json_encode($result);
			exit();
		}

		/*
		 * html output
		 */
?><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Errors encountered while processing your request</title>
</head>
<body>
	<h1>Errors encountered while processing your request</h1>
	
	<hr />
	
		<pre><?=implode("\n", $result['errors'])?></pre>
	
	<hr />
	
	<p>Press the &quot;Back&quot; button in your browser to return to the form</p>
	
</body>
</html><?php
		
	}
}