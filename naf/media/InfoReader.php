<?php
namespace naf\media;

interface InfoReader {
	/**
	 * Read media file information.
	 *
	 * @param string filename
	 * @return Info
	 */
	function info($filename);
}