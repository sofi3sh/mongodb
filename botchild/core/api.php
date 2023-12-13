<?php

	class ApiIfurn
	{
		private $key = ')2@4XZ*7B9hhhiEL!p5&4hX3!bb*!6ZT';
		private $urlApi = 'https://test-api.ifurn.pro/api/external/';
		private $action = 'bot_get_orders';

		public $error = '';
		public $dataResp = [];

		function __construct()
		{

		}

		public function sendRequest($data)
		{
			if(!isset($data['bot_key'])) {
				$data['bot_key'] = $this->key;
			}

			new LoggerLog($data, 'send_api');

			$ch = curl_init($this->urlApi);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('action: ' . $this->action));
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			$this->getResponse(curl_exec($ch));

			$err = curl_error($ch);
			curl_close($ch);

			if (!empty($err)) {
				new LoggerLog($err,'error_log');
				return $this->error .= $err;
			}

		}

		private function getResponse($response)
		{
			$response = json_decode($response);

			new LoggerLog($response, 'get_api');

			if(isset($response->data)){
				$this->dataResp = $response->data;
			}
			if(!empty($response->error)){
				$this->error .= $response->error;
			}
		}

	}