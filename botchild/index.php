<?php


// Перевірка підключення
	require $_SERVER['DOCUMENT_ROOT'] . '/core/core.php';
	try {
		if (isset($_SERVER['REQUEST_URI'])) {
			$url = explode('/', $_SERVER['REQUEST_URI']);
			switch (true) {
				case ($url[1] == 'api') :
					new ControllerApi($url);
					break;
				case ($url[1] == 'webhook') :
					ob_start();
					new ControllerWebhook($url);
					ob_end_flush();
					break;
                case($url[1] == 'mongo') :
                    new ControllerDBApi();
                    break;
				default :
					header("HTTP/1.1 404 Not Found");
			}
		}
	} catch (Throwable $error) {
		new LoggerLog($_REQUEST, 'index_log');
		new LoggerLog($error, 'index_log');
	}



