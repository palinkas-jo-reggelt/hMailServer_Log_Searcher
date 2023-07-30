<?php

	include_once("config.php");
	include_once("functions.php");
	include_once("head.php");

	if (isset($_GET['search'])) {$search = trim($_GET['search']);} else {$search = "";}
	if (isset($_GET['clear'])) {header("Location: ./index.php");}

	echo "
	<div class='section'>
		<form action='".$_SERVER['PHP_SELF']."' method='get'>
			<input type='text' name='search' placeholder='Search' value='".$search."'>
			<input type='submit' value='Search'>
			<input type='submit' name='clear' value='Clear'>
		</form><br>";

	if (!empty($search)) {
		$start = microtime(true);
		$hMS = hMSAuthenticate();
		$logFolder = $hMS->Settings->Directories->LogDirectory;

		$logfile_array = array();
		if (is_dir($logFolder)) {
			if ($handle = opendir($logFolder)) {
				while(($file = readdir($handle)) !== FALSE) {
					if (preg_match("/\.log$/",$file)) {
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
	<br>
	</div><!-- END section -->";

	include_once("foot.php");
?>