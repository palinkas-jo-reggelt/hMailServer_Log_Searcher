<?php

	include_once("config.php");

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

	if (isset($_GET['search'])) {$search = trim($_GET['search']);} else {$search = "";}
	if (isset($_GET['clear'])) {header("Location: ./index.php");}

	if (!isset($_COOKIE['username']) || !isset($_COOKIE['password']) || (!(($_COOKIE['username'] === $user_name) && ($_COOKIE['password'] === md5($pass_word))))) {
		if ($_SERVER["REQUEST_URI"] != "/index.php") {
			header("Location: ./index.php");
		}

		$passwordfail = false;

		$output = array();
		$splits = explode('/', $_SERVER["REQUEST_URI"]);
		$count = count($splits);
		for ($i = 1; $i < ($count - 1); $i++) {
			$output[] = "/".$splits[$i];
		}
		$folder = implode($output);
		if (!$folder){$path = "/";} else {$path = $folder;}

		if (isset($_POST['submit'])) {
			if (($_POST['username'] === $user_name) && ($_POST['password'] === $pass_word)) {
				if (isset($_POST['rememberme'])) {
					setcookie('username', $_POST['username'], strtotime( '+'.$cookie_duration.' days' ), $path, $_SERVER["HTTP_HOST"]);
					setcookie('password', md5($_POST['password']), strtotime( '+'.$cookie_duration.' days' ), $path, $_SERVER["HTTP_HOST"]);
				} else {
					setcookie('username', $_POST['username'], false, $path, $_SERVER["HTTP_HOST"]);
					setcookie('password', md5($_POST['password']), false, $path, $_SERVER["HTTP_HOST"]);
				}
				header("Location: ./index.php");
			} else {
				$passwordfail = true; 
			}
		}

		echo "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Log In</title>
	<meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css'>
    <style type='text/css'>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 350px; padding: 20px; }
    </style>
</head>
<body>
    <div class='wrapper'>
        <h2>Log In</h2>
        <form action='".$_SERVER['PHP_SELF']."' method='post'>
            <div class='form-group'>
                <label>Username</label>
                <input type='text' name='username' class='form-control'>
            </div>    
            <div class='form-group'>
                <label>Password</label>
                <input type='password' name='password' class='form-control'>
            </div>
            <div class='form-group'>
                <label>Remember Me </label>
                <input type='checkbox' name='rememberme' value='1'>
            </div>
            <div class='form-group'>
                <input type='submit' name='submit' class='btn btn-primary' value='Submit'>
            </div>
        </form>";
		if ($passwordfail){
			echo "<script>";
			echo "alert('Username/Password Invalid');";
			echo "</script>";
		}
		echo "
    </div>    
</body>
</html>";

	// else cookies already set and matching user/password combo
	} else {

		echo "
<!DOCTYPE html> 
<html>
<head>

<title>hMailServer Super Log</title>
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
<meta http-equiv='Content-Style-Type' content='text/css'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<style>.wrapper {max-width: ".$viewport_width."px;}</style>
<link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
<style>
	body {background: #fefefe;font-family: 'Roboto';font-size: 12pt;}
	h1 {display: block;	font-size: 2em;	margin: 0;	font-weight: bold;}

	.wrapper {position: relative;margin: 0px auto 30px auto;padding-top: 10px;}
	.clear {clear: both;}
	.section {padding: 5px 0 15px 0;margin: 10px;display: block;}
	.section a:link, a:visited {color: black;text-decoration: none;}
	.section a:hover, a:active {color: red;text-decoration: underline;}
	.footer {width: 100%;text-align: center;font-size: 8pt;}

	.resultsFrame {font-family: consolas;font-size: 12px;border:1px solid black;border-radius:5px;padding:10px;max-height:70vh;overflow:scroll;overflow-x:hidden;}
	.logline {display: block;padding-bottom: 0;line-break: auto;word-break: break-all;}
	.loglineleft {float:left;}
	.loglineright {margin-left:50px;}
	.logGroupContainer {padding-bottom: 10px;}
	.logGroupHeader {font-weight:bold;text-decoration-line: underline;text-decoration-style: dotted;text-decoration-thickness: 1px;background:rgba(102, 255, 102, 0.25)}

	@media only screen and (max-width: 629px) {
		.logline {padding-bottom: 10px;}
	}
</style>
</head>
<body>
<div class='wrapper'>
	<h1>hMailServer Log Search</h1>
	<div class='section'>
		<form action='".$_SERVER['PHP_SELF']."' method='get'>
			<input type='text' name='search' placeholder='Search' value='".$search."'>
			<input type='submit' value='Search'>
			<input type='submit' name='clear' value='Clear'>
		</form><br>";

		if (!empty($search)) {
			$start = microtime(true);
			$hMS = new COM("hMailServer.Application");
			$hMS->Authenticate("Administrator", $hMSAdminPass);
			$logFolder = $hMS->Settings->Directories->LogDirectory;

			$logfile_array = array();
			if (is_dir($logFolder)) {
				if ($handle = opendir($logFolder)) {
					while(($file = readdir($handle)) !== FALSE) {
						if (!preg_match("/^[\.]+$/",$file)) {
							$logfile_array[] = $file;
						}
					}
					closedir($handle);
				}
			}

			$regex = "/\w*?".$search."\w*/i";
			$results = array();
			$logIterator = 0;
			$lineIterator = 0;
			$fileSize = 0;
			$fileCount = count($logfile_array);

			foreach ($logfile_array as $logFile) {
				$fileName = $logFolder.'\\'.$logFile;
				$logLineIterator = 0;
				$lineCounter = 0;
				$data = array();

				if (file_exists($fileName)) {
					$fileSize = $fileSize + filesize($fileName);
					$encoding = detect_utf_encoding($fileName);
					$file = fopen($fileName, "r");
					if ($file) {
						while(!feof($file)) {
							$line = fgets($file);
							if (!is_null($encoding)) {
								if ($lineCounter > 0) {
									$line = preg_replace('/[^\x00-\x7F]/', '', $line);
								}
								if ($encoding == "UTF-16LE") {
									$line = mb_convert_encoding ($line, "UTF-8", "UTF-16");
								} else {
									$line = mb_convert_encoding ($line, "UTF-8", $encoding);
								}
								$lineCounter++;
							}
							if (preg_match("/".$search."/iu",$line)) {
								$line = cleanString($line);
								$line = cleanNonUTF8($line);
								$lineIterator++;
								$line = preg_replace($regex, "<span style='background:yellow;font-weight:bold;'>$0</span>", $line);

								if (!isset($results[$logIterator])) {
									$results[$logIterator][0] = array($logFile, $logLineIterator);
								}
								$results[$logIterator][0][1] = $logLineIterator + 1;
								$results[$logIterator][1][] = array($lineIterator, $line);

								$logLineIterator++;
							}
						}
						fclose($file);
					} else {
						echo "Error opening log file: ".$logFile."<br>";
					}
					$logIterator++;
				} else {
					echo "Log file not found: ".$logFile."<br>";
				}
			}

			$end = microtime(true);
			$time = number_format(($end - $start), 2);

			if ($lineIterator === 0) {
				echo "No Results.";
			} else {
				echo "
		<b>".number_format($lineIterator)." results</b> found among ".$fileCount." files totalling ".formatBytes($fileSize)." searched in ".$time." seconds<br><br>
		<div class='resultsFrame'>";
				foreach ($results as $result) {
					echo "
			<div class='logGroupHeader'>".$result[0][0]." : ".$result[0][1]." Results</div>";
					foreach ($result[1] as $lineresult) {
						echo "
			<div class='logline'>
				<div class='loglineleft'>".number_format($lineresult[0]).".</div>
				<div class='loglineright'>".$lineresult[1]."</div>
			</div>";
					}
					echo "<br>";
				}
				echo "
		</div><!-- END resultsFrame -->";
			}
		} else {
			echo "No search term provided.";
		}

		echo "
	</div><!-- END section -->
	<div class='footer'>
		Pálinkás jó reggelt kívánok!<br>";
		$versionGitHub = file_get_contents('https://raw.githubusercontent.com/palinkas-jo-reggelt/hMailServer_Log_Searcher/main/VERSION');
		$versionLocal = file_get_contents('VERSION');
		if ((float)$versionLocal < (float)$versionGitHub) {
			echo "
		Upgrade to version ".trim($versionGitHub)." available at <a href='https://github.com/palinkas-jo-reggelt/hMailServer-SQL-Log'>GitHub</a>";
		}
		echo "
	</div> <!-- END footer -->
</div> <!-- END wrapper -->
</body>
</html>";

	}
?>