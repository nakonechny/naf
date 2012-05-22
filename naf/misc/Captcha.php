<?php

/**
 * ASCII-Captcha.
 * 
 * This is actually a simplified ASCIIArtist by Sebastian R�bke <sebastian@sebastian-r.de>:
 *
 * ==============================================================================
 *  
 * Copyright (c) 2004, Sebastian R�bke <sebastian@sebastian-r.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice, this 
 *   list of conditions and the following disclaimer. 
 * - Redistributions in binary form must reproduce the above copyright notice, 
 *   this list of conditions and the following disclaimer in the documentation 
 *   and/or other materials provided with the distribution. 
 * - Neither the name of Sebastian R�bke nor the names of its contributors may be
 *   used to endorse or promote products derived from this software without 
 *   specific prior written permission. 
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE 
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
 * GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT 
 * OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * Credits: Florian Sch�fer, Maxi Kellner, Andrea Spacca, Alastair Battrick
 * Requirements: PHP >= 4.3.x with GD support for the desired image format
 * 
 * @author   Sebastian R�bke <asciiartist@sebastian-r.de>
 * @version  1.4
 * @link     http://www.sebastian-r.de/asciiart/
 * @package  ASCIIArtist
 * @license  BSD
 * 
 * ==============================================================================
 * 
 * While keeping the main idea,
 * I have got rid of color mode, and simplified the code much.
 * 
 * Best regards,
 * Victor Bolshov <crocodile2u@yandex.ru>
 * 
 */

namespace naf\misc;

class ASCIICaptcha {

	private $font, $text, $secret;
	
	/**
    * The replace characters from dark to light used by render modes 0 and 1 
    * (current version can handle 9 privateiations)
    *
    * @private      array
    * @access   private
    */
    private $charmap = array ('WXEKSZB', 
    	'8@OCa', 
    	'$#!|L%', 
    	'*~', 
    	'+x', 
    	':;i', 
    	'.\'', 
    	',', 
    	",.%~                                                                                                         ");
    	/* the last entry in charmap is actually adding some noise to the result */
    
    /**
     * Length for each charmap entry
     *
     * @var int
     */
    private $charmapLen = array();
	
	/**
	 * Constructor.
	 *
	 * @param array | string $fonts .ttf font(s)
	 * @param string $secret Secret key for the hash
	 * @param string[optional] $text when omitted, the text will be auto-generated
	 */
	function __construct($fonts, $secret, $text = null)
	{
		$this->font = is_array($fonts) ? $fonts[array_rand($fonts)] : $fonts;
		$this->secret = $secret;
		
		if (null === $text)
			$this->text = $this->generateText();
		else
			$this->text = $text;

		if (rand(0, 1))
			$this->charmap = array_reverse($this->charmap);
		
		foreach ($this->charmap as $n => $c)
    		$this->charmapLen[$n] = strlen($c) - 1;
	}

	private function generateText()
	{
		return substr(uniqid(), -6);
	}
	
	function hash()
	{
		return md5($this->secret . $this->text);
	}
	
	/**
	 * Check user input for being correct
	 *
	 * @param string $userInput
	 * @param string $hash
	 * @return bool
	 */
	function validate($userInput, $hash)
	{
		return $this->hash($userInput) == $hash;
	}
	
	/**
	 * get the ASCII-representation of captcha text
	 * 
	 * @return string
	 */
	function export()
	{
		$w = 200;// image width
		$h = 44;// image height
		$fs = 32;// font-size
		$top = 34;// text top (left bottom corner of text box)
		$resolution = 2;

		$im = imagecreatetruecolor($w, $h);
		imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
		imagettftext($im, $fs, 0, 10, $top, imagecolorallocate($im, 0, 0, 0), $this->font, $this->text);
		
		$output = "";
		for ($y = 0; $y < $h; $y += $resolution)
        {
            for ($x = 0; $x < $w; $x += $resolution)
            {
				$rgb = imagecolorsforindex($im, imagecolorat($im, $x, $y));
				$brightness = $rgb["red"] + $rgb["green"] + $rgb["blue"];
				$index = round($brightness / 100);
				$output .= $this->charmap[$index]{rand(0, $this->charmapLen[$index])};
            }
            $output .= "\n";
        }
		
		return $output;
	}
}