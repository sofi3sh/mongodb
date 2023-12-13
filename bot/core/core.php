<?php
	include_once $_SERVER['DOCUMENT_ROOT'] . '/core/db.php';
	include_once $_SERVER['DOCUMENT_ROOT'] . '/core/telegram.php';
	include_once $_SERVER['DOCUMENT_ROOT'] . '/core/api.php';

	include_once $_SERVER['DOCUMENT_ROOT'] . '/controller/ControllerApi.php';
	include_once $_SERVER['DOCUMENT_ROOT'] . '/controller/ControllerRequestMongoApi.php';
	include_once $_SERVER['DOCUMENT_ROOT'] . '/controller/ControllerWebhook.php';
	include_once $_SERVER['DOCUMENT_ROOT'] . '/controller/ControllerLang.php';

	require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';


	class LoggerLog {
		private $logStatus = true;
		function __construct($log, $name = 'log') {
			if ($this->logStatus) {
				$myFile = $_SERVER['DOCUMENT_ROOT'] . '/log/' . $name . '.txt';
				$fh = fopen($myFile, 'a') or die('can\'t open file');
				fwrite($fh, date('Y-m-d G:i:s') . "\n");

				if ((is_array($log)) || (is_object($log))) {
					$updateArray = print_r($log, TRUE);
					fwrite($fh, $updateArray . "\n");
				} else {
					fwrite($fh, $log . "\n");
				}
				fclose($fh);
			}
		}
	}
