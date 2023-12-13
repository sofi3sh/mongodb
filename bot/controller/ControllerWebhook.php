<?php


	class ControllerWebhook
	{

		/**
		 * ControllerWebhook constructor.
		 * @param array $url
		 */
		public function __construct(array $url)
		{
			new LoggerLog("GET REQUEST", 'webhook_log');
			new LoggerLog($url, 'webhook_log');
			if ($count = array_search('webhook', $url)) {
				try {
					$class = "Controller" . ucfirst(strtok($url[$count+1], '?'))  . "Bot";
					include_once $_SERVER['DOCUMENT_ROOT'] . "/controller/" . $class . ".php";
					if (!class_exists($class, false)) {
						throw new LogicException("Unable to load class: $class");
					}
					new LoggerLog(json_decode(file_get_contents('php://input'), true), 'webhook_log');
					$result = new $class($url);

				} catch (Exception $error) {
					new LoggerLog($error, 'webhook_log');
				}

			}
		}


	}

