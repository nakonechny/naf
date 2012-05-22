<?php

namespace naf\util;

final class PhpIni {
	static function postMaxSize()
	{
		return self::byteSize('post_max_size');
	}
	static function uploadMaxSize()
	{
		return min(self::byteSize('upload_max_filesize'), self::postMaxSize());
	}
	static private function byteSize($setting)
	{
		$sSize = ini_get($setting);
		$sUnit = substr($sSize, -1);
		$iSize = (int) substr($sSize, 0, -1);
		switch (strtoupper($sUnit))
		{
			case 'Y' : $iSize *= 1024; // Yotta
			case 'Z' : $iSize *= 1024; // Zetta
			case 'E' : $iSize *= 1024; // Exa
			case 'P' : $iSize *= 1024; // Peta
			case 'T' : $iSize *= 1024; // Tera
			case 'G' : $iSize *= 1024; // Giga
			case 'M' : $iSize *= 1024; // Mega
			case 'K' : $iSize *= 1024; // kilo
		};
		return $iSize;
	}
}