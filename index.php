<?php

	include_once("config.php");
	include_once("functions.php");
	include_once("head.php");

	if (isset($_GET['search'])) {$search = trim($_GET['search']);} else {$search = "";}
	if (isset($_GET['clear'])) {redirect("./");}
	if (isset($_GET['byDate'])) {
		$byDate = trim($_GET['byDate']);
		$byDate_ph = $byDate;
		$logDateRegEx = "/".$byDate."/";
	} else {
		$byDate = "All Dates";
		$byDate_ph = "All Dates";
		$logDateRegEx = "/.*/";
	}
	if (isset($_GET['byType'])) {
		$byType = trim($_GET['byType']);
		$byType_ph = $byType;
		$logTypeRegEx = "/".$byType."/";
	} else {
		$byType = "All Log Types";
		$byType_ph = "All Log Types";
		$logTypeRegEx = "/.*/";
	}

	$hMS = hMSAuthenticate();
	$logFolder = $hMS->Settings->Directories->LogDirectory;

	$logDate_array = array();
	$logFile_array = array();
	$logType_array = array();
	if (is_dir($logFolder)) {
		if ($handle = opendir($logFolder)) {
			while(($file = readdir($handle)) !== FALSE) {
				if (preg_match("/\.log$/",$file)) {
					$fileNoDate = preg_replace('/_(([0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9]))\.log|\.log/','',$file);
					if (!in_array($fileNoDate, $logType_array, true)){
						array_push($logType_array, $fileNoDate);
					}
					if ((preg_match("/^".$byType."_(([0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9]))\.log|^".$byType."\.log/",$file)) || ($byType === "All Log Types")) {
						preg_match('/(([0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9]))/',$file,$matches);
						if (isset($matches[0])){
							if (!in_array($matches[0], $logDate_array, true)){
								array_push($logDate_array, $matches[0]);
							}
						}
					}
					$logFile_array[] = $file;
				}
			}
			closedir($handle);
		}
	}
	rsort($logDate_array);



	echo "
	<div class='section'>
		<form action='".$_SERVER['PHP_SELF']."' method='get'>
			<select name='byType' onchange='this.form.submit()'>
				<option value='".$byType."'>".$byType_ph."</option>
				<option value='All Log Types'>All Log Types</option>";

	foreach ($logType_array as $logType) {
		echo "
				<option value='".$logType."'>".$logType."</option>";
	}
	echo "
			</select>
			<select name='byDate' onchange='this.form.submit()'>
				<option value='".$byDate."'>".$byDate_ph."</option>
				<option value='All Dates'>All Dates</option>";

	foreach ($logDate_array as $logDate) {
		echo "
				<option value='".$logDate."'>".$logDate."</option>";
	}
	echo "
			</select>
			<input type='text' name='search' placeholder='Search' value='".$search."'>
			<input type='submit' value='Search'>
			<input type='submit' name='clear' value='Clear'>
		</form>";

	if (empty($search)) {
		echo "No search term provided.";
	} else {
		$start = microtime(true);
		$regex = "/\w*?".$search."\w*/i";
		$results = array();
		$logIterator = 0;
		$lineIterator = 0;
		$fileSize = 0;
		$fileCount = 0;

		foreach ($logFile_array as $logFile) {
			$logFileTypeBase = preg_replace('/_(([0-9]{4})-(1[0-2]|0[1-9])-(3[01]|0[1-9]|[12][0-9]))\.log$|\.log$/','',$logFile);
			$logFileDateBase = preg_replace('/\.log$/','',$logFile);
			if ((($byType === $logFileTypeBase) || ($byType === "All Log Types")) && ((preg_match($logDateRegEx,$logFileDateBase)) || ($byDate === "All Dates"))) {
				$fileName = $logFolder.DIRECTORY_SEPARATOR.$logFile;
				$logLineIterator = 0;
				$lineCounter = 0;
				$data = array();

				if (file_exists($fileName)) {
					$fileSize = $fileSize + filesize($fileName);
					$encoding = detect_utf_encoding($fileName);
					$file = fopen($fileName, "r");
					if ($file) {
						$fileCount++;
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
		}

		$end = microtime(true);
		$time = number_format(($end - $start), 2);

		if ($lineIterator === 0) {
			echo "
	<br>
	<div class='resultsFrame'>
		No Results.
	</div>";
		} else {
			echo "
	<br><b>".number_format($lineIterator)." results</b> found among ".$fileCount." files totalling ".formatBytes($fileSize)." searched in ".$time." seconds<br><br>
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
	}
	echo "
	<br>
	</div><!-- END section -->";

	include_once("foot.php");
?>