<?php
	function hMSAuthenticate(){
		global $hMSAdminPass;
		$hMS = new COM("hMailServer.Application");
		$hMS->Authenticate("Administrator", $hMSAdminPass);
		return $hMS;
	}

	function redirect($url) {
		if (!headers_sent()) {    
			header('Location: '.$url);
			exit;
		} else {
			echo '<script type="text/javascript">';
			echo 'window.location.href="'.$url.'";';
			echo '</script>';
			echo '<noscript>';
			echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
			echo '</noscript>'; exit;
		}
	}

	// https://www.php.net/manual/en/function.mb-detect-encoding.php#91051
	// Unicode BOM is U+FEFF, but after encoded, it will look like this.
	define ('UTF32_BIG_ENDIAN_BOM'   , chr(0x00) . chr(0x00) . chr(0xFE) . chr(0xFF));
	define ('UTF32_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE) . chr(0x00) . chr(0x00));
	define ('UTF16_BIG_ENDIAN_BOM'   , chr(0xFE) . chr(0xFF));
	define ('UTF16_LITTLE_ENDIAN_BOM', chr(0xFF) . chr(0xFE));
	define ('UTF8_BOM'               , chr(0xEF) . chr(0xBB) . chr(0xBF));

	function detect_utf_encoding($fileName) {
		$text = file_get_contents($fileName);
		$first2 = substr($text, 0, 2);
		$first3 = substr($text, 0, 3);
		$first4 = substr($text, 0, 3);

		if ($first3 == UTF8_BOM) return 'UTF-8';
		elseif ($first4 == UTF32_BIG_ENDIAN_BOM) return 'UTF-32BE';
		elseif ($first4 == UTF32_LITTLE_ENDIAN_BOM) return 'UTF-32LE';
		elseif ($first2 == UTF16_BIG_ENDIAN_BOM) return 'UTF-16BE';
		elseif ($first2 == UTF16_LITTLE_ENDIAN_BOM) return 'UTF-16LE';
	}

	// https://github.com/coax/hmailserver-webadmin/blob/master/hMailAdmin/include/log_functions.php#L100-L104
	function cleanString($str) {
		$search = array("\r\n", "'", '"', '<', '>', '[nl]', '{em}', '{/em}','\n');
		$replace = array('', '', '', '&lt;', '&gt;', '<br>', '<em>', '</em>','<br>');
		return str_replace($search, $replace, $str);
	}

	// https://github.com/coax/hmailserver-webadmin/blob/master/hMailAdmin/include/log_functions.php#L106-L120
	function cleanNonUTF8($str) {
		$regex = <<<'END'
	/
	  (
		(?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
		|   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
		|   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
		|   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3
		){1,100}                        # ...one or more times
	  )
	| .                                 # anything else
	/x
	END;
		return preg_replace($regex, '$1', $str);
	}

	// https://stackoverflow.com/questions/2510434/format-bytes-to-kilobytes-megabytes-gigabytes#2510459
	function formatBytes($size, $precision = 1) {
		$base = log($size, 1024);
		$suffixes = array('Bytes', 'KB', 'MB', 'GB', 'TB');   
		return round(pow(1024, $base - floor($base)), $precision).' '.$suffixes[floor($base)];
	}
?>
