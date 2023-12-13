<?php

	include_once $_SERVER['DOCUMENT_ROOT'] . '/model/ModelApi.php';
	include_once $_SERVER['DOCUMENT_ROOT'] . '/view/ViewTelegramSendMessage.php';
	include_once $_SERVER['DOCUMENT_ROOT'] . '/view/ViewViberSendMessage.php';

	class ControllerApi
	{
		private $keyApi = 'iSY~!tyu2_*u95(7%25SHsj_g6EEE3';
		private $respData;
		private $response;
		private $db;

		private $daysToRemoveMessageFromDb = 3;

		function __construct(array $methods)
		{
			$this->db = new ModelApi();
			$this->respData = json_decode(file_get_contents('php://input'), true);
//			new LoggerLog($this->respData, 'api_log');
			if (empty($this->respData)) {
				header("HTTP/1.1 404 Not Found");
				exit();
			}
            if (!$this->authApi()) {
				header("HTTP/1.1 404 Not Found");
				exit('error key');
			}
			if (is_array($methods)) {
				foreach ($methods as $method) {
					if (empty($method)) {
						continue;
					} elseif (method_exists($this, $method)) {
						$this->$method();
					} else {
						$this->response = [
							'status' => 'false',
							'text' => "Метод не найден"
						];
					}
				}
			}

		}

		private function api()
		{
			return $this->response = [
				'status' => 'ok',
				'data' => []
			];
		}



		private function sendMessage()
		{

			$db = new ModelApi();
			$viewTelegram = new ViewTelegramSendMessage($this->respData['telegram_token']);
			$viewViber = new ViewViberSendMessage($this->respData['viber_token']);
			$data = $this->respData['numbers'];
			if (empty($data)) {
				$this->response['data'][] =[
					'error' => 'Нет номеров'
				];
			}
			$db->deleteOldMessage($this->daysToRemoveMessageFromDb);

			/*
			 * Для безопасности, перед отправкой запроса в БД, делаем проверку номера. Должен соответсвовать шаблону +## (###) ###-##-##
			 */

			foreach ($data as $tel => $message) {

				if ((preg_match('/^\+[0-9]{1}? \(?[0-9]{3}\) [.-]?[0-9]{3}[.-]?[0-9]{2}[.-]?[0-9]{2}$/',$tel))||
							(preg_match('/^\+[0-9]{2}? \(?[0-9]{3}\) [.-]?[0-9]{3}[.-]?[0-9]{2}[.-]?[0-9]{2}$/',$tel))||
							(preg_match('/^\+[0-9]{3}? \(?[0-9]{3}\) [.-]?[0-9]{3}[.-]?[0-9]{2}[.-]?[0-9]{2}$/',$tel)))
				 {
					$numberForDb[] = $tel;
				} else {
					$this->response['data'][] =[
						'number' => (string) $tel,
						'error' => 'Не соответсвует формат номера'
					];
					unset($data[$tel]);
				}
			}

			if (!empty($numberForDb)) {

				$idsChatsTelegram = $db->getIdsChatsByNumber($numberForDb);
				$idsChatsViber = $db->getIdsChatsByNumber($numberForDb, 'bot_viber_users');
				$sendMessages = [];

				foreach ($data as $tel => $value) {
					$sendMessages[$tel]['text'] = $value;
					if (isset($idsChatsTelegram[$tel]['chat_id'])) {
						$sendMessages[$tel]['chat_id'] = $idsChatsTelegram[$tel]['chat_id'];
					} elseif (isset($idsChatsViber[$tel]['chat_id'])) {
						$sendMessages[$tel]['chat_id'] = $idsChatsViber[$tel]['chat_id'];
					}
				}

				foreach ($sendMessages as $tel => $sendMessage) {

					if (isset($sendMessage['chat_id'])) {
						if (ctype_digit($sendMessage['chat_id'])){
							$viewTelegram->text = $sendMessage['text'];
							$viewTelegram->chatId = $sendMessage['chat_id'];
							$status = $viewTelegram->sendText();

							$db->setMessageSend($tel, $sendMessage['text'],(bool) $status, $sendMessage['chat_id'], 'Telegram');
						} else {
							$viewViber->setUserId($sendMessage['chat_id']);
							$status = $viewViber->sendMsgText($sendMessage['text']);

							$db->setMessageSend($tel, $sendMessage['text'],(bool) $status, $sendMessage['chat_id'], 'Viber');
						}


						if ($status) {
							$this->response['data'][] = [
								'number' => (string)$tel,
								'send' => 'ok'
							];
						} else {
							$this->response['data'][] = [
								'number' => (string)$tel,
								'error' => "Ошибка отправки сообщения"
							];
						}

					} else {
						if(empty($sendMessage['text'])){
							$sendMessage['text'] = 'Текст с API не пришёл';
						}
//						$db->setMessageSend($tel, $sendMessage['text'], 0, 'Номер не найден', '');
						$this->response['data'][] =[
							'number' => (string) $tel,
							'error' => 'Номер не найден'
						];
					}
				}
			}

		}

		private function authApi()
		{
			return ($this->respData['key'] == $this->keyApi);
		}

		private function getUsersByPhones()
		{


			$numbers = $this->respData['numbers'];
			if (!empty($numbers)) {
				if (is_array($numbers)) {

					foreach ($numbers as $key => $number){
						if ((preg_match('/^\+[0-9]{2}? \(?[0-9]{3}\) [.-]?[0-9]{3}[.-]?[0-9]{2}[.-]?[0-9]{2}$/',$key))||
                            (preg_match('/^\+[0-9]{1}? \(?[0-9]{3}\) [.-]?[0-9]{3}[.-]?[0-9]{2}[.-]?[0-9]{2}$/',$key))||
                            (preg_match('/^\+[0-9]{3}? \(?[0-9]{3}\) [.-]?[0-9]{3}[.-]?[0-9]{2}[.-]?[0-9]{2}$/',$key))) {
							$numbers[$key] = $key;
							$this->response['error']['1002'][] = (string) $key . " - передали телефон ключем - НЕ НАДО ТАК(((";

						} elseif ((preg_match('/^\+[0-9]{2}? \(?[0-9]{3}\) [.-]?[0-9]{3}[.-]?[0-9]{2}[.-]?[0-9]{2}$/',$key))||
                            (preg_match('/^\+[0-9]{1}? \(?[0-9]{3}\) [.-]?[0-9]{3}[.-]?[0-9]{2}[.-]?[0-9]{2}$/',$key))||
                            (preg_match('/^\+[0-9]{3}? \(?[0-9]{3}\) [.-]?[0-9]{3}[.-]?[0-9]{2}[.-]?[0-9]{2}$/',$key))) { 

							$this->response['error']['1003'][] = (string) $number . " - не соответсвует Формату";
							unset($numbers[$key]);

						}


					}


					$idsChatsTelegram = $this->db->getIdsChatsByNumber($numbers);
					$idsChatsViber = $this->db->getIdsChatsByNumber($numbers, 'bot_viber_users');

					$resultResponse = [];

					foreach ($numbers as $number){
						$resultResponse[] = [
							'number' => $number,
							'telegram' => ($idsChatsTelegram[$number]['chat_id']) ?? 'false',
							'viber' => ($idsChatsViber[$number]['chat_id']) ?? 'false',
						];
					}

					$this->response['data'] = $resultResponse;

				} else {
					$this->response['error']['1002'] = "Передан не массивом";
				}

			} else {
				$this->response['error']['1001'] = "нет параметра";

			}
		}
		public function log($log) {
			$myFile = $_SERVER['DOCUMENT_ROOT'] . '/api_log.txt';
			$fh = fopen($myFile, 'a') or die('can\'t open file');
			if ((is_array($log)) || (is_object($log))) {
				$updateArray = print_r($log, TRUE);
				fwrite($fh, $updateArray."\n");
			} else {
				fwrite($fh, $log . "\n");
			}
			fclose($fh);
		}

		public function setWebHook()
        {
            if(isset($this->respData['telegram_token'])) {
                $webHook = ViewTelegramSendMessage::setWebHook($this->respData['telegram_token']);
                $this->response['data'] = $webHook;
            }
            if(isset($this->respData['viber_token'])) {
                $webHook = ViewViberSendMessage::setWebHook($this->respData['viber_token']);
                $this->response['data'] = $webHook;
            }

        }

		public function __destruct()
		{
			// TODO: Implement __destruct() method.
			if (isset($this->response)){
				header('Content-type: application/json; charset=utf-8');
				echo json_encode($this->response);
			}
		}



	}

