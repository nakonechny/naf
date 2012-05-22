<?php

/**
 * Image manipulator
 */

namespace naf\util;
use naf\util\Image\Fault;

class Image {
	
	/**#@+
	 * Image handle
	 * @var resource
	 */
	protected $_source;
	protected $_destination;
	/**#@-*/
	
	/**
	 * Image file name
	 *
	 * @var string
	 */
	protected $_filename;
	
	/**
	 * @var int
	 */
	protected $_width, $_height;
	
	/**#@+
	 * Image handle
	 * @var callback
	 */
	protected $_createImageCallback = 'imagecreatetruecolor';
	protected $_saveImageCallback = 'imagejpeg';
	/**#@-*/
	
	function __construct($filename)
	{
		$this->_filename = $filename;
		list($this->_width, $this->_height) = getimagesize($this->_filename);
		$pathinfo = pathinfo($filename);
		switch (strtolower($pathinfo['extension']))
		{
			case 'jpg':
			case 'jpeg':
				$this->_source = imageCreateFromJpeg($filename);
				break;
			case 'gif':
				$this->_source = imageCreateFromGif($filename);
				$this->_createImageCallback = 'imagecreate';
				break;
			case 'png':
				$this->_source = imageCreateFromPng($filename);
				break;
			default:
				throw new Fault('Unsupported file extension');
				break;
		}
	}
	
	/**
	 * Resize image so that it is inscribed into rectangle
	 *
	 * @param int $width
	 * @param int $height
	 * @return bool Alwayse TRUE
	 * @throws Fault
	 */
	function inscribe($width, $height)
	{
		$this->_createDestinationImage($width, $height);

		if ($this->_width > $this->_height)
		{
			$dstWidth = $width;
			$dstHeight = round($this->_height * ($dstWidth / $this->_width));
			$x = 0;
			$y = round(($height - $dstHeight)/2);
		}
		else
		{
			$dstHeight = $height;
			$dstWidth = round($this->_width * ($dstHeight / $this->_height));
			$x = round(($width - $dstWidth)/2);
			$y = 0;
		}
		
		return $this->_copy($x, $y, 0, 0, $dstWidth, $dstHeight, $this->_width, $this->_height);
	}
	
	function crop($x, $y, $width, $height) {
		$this->_createDestinationImage($width, $height);
		return $this->_copy(0, 0, $x, $y, $width, $height, $width, $height);
	}
	
	function scaleMaxSize($size, $downOnly = true)
	{
		if ($this->_width > $this->_height)
			return $this->scaleWidth($size, $downOnly);
		else
			return $this->scaleHeight($size, $downOnly);
	}
	
	function scaleWidth($newWidth, $downOnly = true)
	{
		if ($downOnly && ($newWidth > $this->_width))
			$newWidth = $this->_width;

		$newHeight = floor($this->_height * ($newWidth / $this->_width));
		return $this->scale($newWidth, $newHeight, $downOnly);
	}
	
	function scaleHeight($newHeight, $downOnly = true)
	{
		if ($downOnly && ($newHeight > $this->_height))
			$newHeight = $this->_height;
		
		$newWidth = floor($this->_width * ($newHeight / $this->_height));
		return $this->scale($newWidth, $newHeight, $downOnly);
	}
	
	function scale($width, $height)
	{
		$this->_createDestinationImage($width, $height);
		$this->_copy(0, 0, 0, 0, $width, $height, $this->_width, $this->_height);
		return true;
	}
	
	protected function _copy($dstX, $dstY, $srcX, $srcY, $dstWidth, $dstHeight, $srcWidth, $srcHeight)
	{
		if (! imagecopyresampled($this->_destination, $this->_source, 
				$dstX, $dstY, $srcX, $srcY, 
				$dstWidth, $dstHeight, $srcWidth, $srcHeight))
		{
			throw new Fault('Failed to resize image');
		}
		
		$this->_updateSize($dstWidth, $dstHeight);
		
		return true;
	}
	
	protected function _updateSize($width, $height)
	{
		$this->_width = $width;
		$this->_height = $height;
	}
	
	/**
	 * Save image to file
	 *
	 * @param string $filename
	 * @return bool Alwayse TRUE
	 * @throws Fault
	 */
	function save($filename = null)
	{
		$this->_checkDestinationImage();
		
		if (null === $filename)
			$filename = $this->_filename;
			
		$ext = substr($filename, strrpos($filename, '.'), strlen($filename));
		switch (strtolower($ext))
		{
			case '.jpg':
			case '.jpeg':
				$callback = 'imagejpeg';
				break;
			case '.png':
				$callback = 'imagepng';
				break;
			case '.gif':
				$callback = 'imagegif';
				break;
			default:
				throw new Fault('Unsupported image type ' . $ext);
				break;
		}
		
		if (! call_user_func_array($callback, array($this->_destination, $filename)))
			throw new Fault('Failed to save image');
		
		imagedestroy($this->_destination);
		
		// Important!!! kills all the intermediate changes!!!
		$this->__construct($this->_filename);
		
		return true;
	}
	
	protected function _createDestinationImage($width, $height)
	{
		if (is_resource($this->_destination))
			$this->_source = $this->_destination;
		
		$this->_destination = call_user_func_array($this->_createImageCallback, array($width, $height));
	}
	
	protected function _checkDestinationImage()
	{
		if (! is_resource($this->_destination))
			throw new Fault('Destination image is not yet created!');
	}
	
}