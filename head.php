<?php
	include_once("config.php");
	include_once("functions.php");

	if (isset($_COOKIE['username']) && isset($_COOKIE['password'])) {
		if (!(($_COOKIE['username'] === $user_name) && ($_COOKIE['password'] === md5($pass_word)))) {
			redirect("login.php?ruri=".urlencode($_SERVER["REQUEST_URI"]));
		}
	} else {
		redirect("login.php?ruri=".urlencode($_SERVER["REQUEST_URI"]));
	}
?>

<!DOCTYPE html> 
<html>
<head>
<title>hMailServer Log Searcher</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Style-Type" content="text/css">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">
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
	.loglineright {margin-left:90px;}
	.logGroupContainer {padding-bottom: 10px;}
	.logGroupHeader {font-weight:bold;text-decoration-line: underline;text-decoration-style: dotted;text-decoration-thickness: 1px;background:rgba(102, 255, 102, 0.25)}

	@media only screen and (max-width: 629px) {
		.logline {padding-bottom: 10px;}
	}
</style>
</head>
<body>

<?php include("header.php") ?>

<div class="wrapper">