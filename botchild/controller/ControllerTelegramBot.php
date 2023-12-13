<?php

	use \unreal4u\TelegramAPI\Telegram\Types\Update;

	class ControllerTelegramBot
	{
		public $postData;
		public $view;
		public $db;

		private $user = [];
		public $sendMessage = false;
		private $api = [];
		private $botToken = '2008949501:AAHzoNgoDnYwDsiCdMHmb3_Gh9uRPajRj8Q';


		/**
		 * ControllerWebhook constructor.
		 * @param array $url
		 */
		public function __construct(array $url)
		{
			if(empty(json_decode(file_get_contents('php://input'), true))){
				header("HTTP/1.1 404 Not Found");
				exit();
			}


			$this->api = new ApiIfurn();

			require_once $_SERVER['DOCUMENT_ROOT'] . '/model/ModelWebHook.php';

			$this->postData = new Update(json_decode(file_get_contents('php://input'), true));
			new LoggerLog($this->postData, 'telegram_log');
            if (!empty($this->postData)) {
				try {
					if(!empty($this->postData->callback_query->from->id)) {
						$chatId = $this->postData->callback_query->from->id;
                        $langBot = $this->postData->callback_query->from->language_code;
					} else {
						$chatId = $this->postData->message->chat->id;
                        $langBot = $this->postData->message->from->language_code;
					}

					$this->view = new ViewTelegramSendMessage($this->botToken);
					$this->view->chatId = $chatId;
					$this->db = new ModelWebHook();
					$this->user = $this->db->getUser($chatId);

                    ControllerLang::$bot = 'telegram_bot';
					ControllerLang::$lang = (!empty($this->user->lang)) ? $this->user->lang : $langBot;
                    new LoggerLog(ControllerLang::$lang, 'log_lang');
					$this->index($this->postData);
				} catch (Throwable $t) {
					new LoggerLog("Ошибка конструктора ControllerTelegramBot: $t", 'telegram_log');
				}
			}
		}
		public function index($data)
		{
			/**
			 * Список возможных команд
			 */
			switch (true) {
				/**
				 * Старт
				 */
				case ($data->message->text == '/start') :
					if (!empty($this->user->number_phone)){
                        $this->view->text = ControllerLang::trans(
                            'You are already registered with a number %number%',
                            ['number' => $this->user->number_phone]
                        );
						$this->sendMenuUser();
					} else {

						$this->view->Sendkeyboard($this->db->getButtonContact());
						$this->view->text = ControllerLang::trans('You are welcome! Send your phone number for ordering information.');
					}
					break;
				/**
				 * Отправили нам контакт
				 */
				case (!empty($data->message->contact)) :
					if(!$this->checkIdNumber()){
						$this->view->Sendkeyboard($this->db->getButtonContact());
						$this->view->text = ControllerLang::trans(
						    'You are deceiving me %number% is not your number',
                            ['number' => $this->phoneMask($data->message->contact->phone_number)]
                        );
					}elseif (!empty($this->user->number_phone)){
                        $this->view->text = ControllerLang::trans(
                            'You are already registered with a number %number%',
                            ['number' => $this->user->number_phone]
                        );
					} else {
						$status = $this->setNewUser($data->message->contact);
						if ($status) {
                            $this->view->text = ControllerLang::trans(
                                'Thank you, you will now receive information about orders.'
                            );
							$this->sendMenuUser();
						} else {
                            $this->view->text = ControllerLang::trans(
                                'Something went wrong( Please try again later'
                            );
						}
					}
					break;
				case (empty($this->user->number_phone)) :
					$this->view->Sendkeyboard($this->db->getButtonContact());
                    $this->view->text = ControllerLang::trans(
                        'Please send your number by clicking on the button <b>Send number</b> under the input line'
                    );
					break;
				/**
				 * Текстовое:сообщение Меню и расширеные функции
				 */
				case ($data->message->text == '/menu') :
					$this->sendMenuUser();
					break;
				/**
				 * Коллбєк (нажатие на кнопки)
				 */
				case ($data->callback_query->data) :
					$this->getAnswerServer();
					//В дате храница название вызываемой метода
					$nameFunc = $data->callback_query->data;
					$this->$nameFunc();
					break;

				case (stripos($data->message->text, 'order_')) :
					$funcValue = explode('_', $data->message->text);
					$this->getOrderStatus($funcValue[1]);
					break;
				default :
                    $this->view->text = ControllerLang::trans(
                        'I do not understand'
                    );
					$this->sendMenuUser();
					break;
			}
			$this->view->sendText();
		}

		private function setNewUser($contact)
		{
			if (!empty($contact->phone_number)) {

				$numberPhone = $this->phoneMask($contact->phone_number);
				$dbSave = [
					'chat_id' => (int) $this->postData->message->chat->id,
					'number_phone' => (string) $numberPhone,
					'name' => (string) $contact->first_name . ' ' . $contact->last_name,
					'lang' => (string) $this->postData->message->from->language_code,
				];

				$this->db->setUser($dbSave);

				return true;
			}
		}

		private function phoneMask(string $number)
		{
			$number = strrev(preg_replace('/[^0-9]/', '', $number));
			$result = sprintf("%s-%s-%s )%s( %s+",
				substr($number, 0, 2),
				substr($number, 2, 2),
				substr($number, 4, 3),
				substr($number, 7, 3),
				substr($number, 10)
			);
			return strrev($result);
		}
		private function checkIdNumber()
		{
			if($this->postData->message->contact->user_id != $this->postData->message->chat->id) {
				return false;
			}
			return true;
		}

		/**
		 * Методы Текста в чат боте
		 */
		private function sendMenuUser()
		{
			$textMessage = ControllerLang::trans('Here\'s what I can do:');

			$buttonMessage = [
				'inline_keyboard' => [
					[
						['text' => '📑' . ControllerLang::trans(
                                'My orders'
                            ),
                            'callback_data' => 'getUserOrderStatus'],
						['text' => "🌐" . ControllerLang::trans(
                            'Languages'
                            ),
                            'callback_data' => 'getLanguagesMenu']
					],
				]
			];

			$this->view->text = $textMessage;
			$this->view->inline_keyboard = $buttonMessage;
		}

		private function getUserOrders($status)
		{
			$this->sendMenuUser();
			$telephone = $this->user->number_phone;
			$dataRequest = [
				'phone' => $telephone,
				'status' => $status
			];

			$this->api->sendRequest($dataRequest);
			$api = $this->api;


			if (empty($api->error) && !empty($api->dataResp)) {
				$textMessage = ControllerLang::trans("You have %count% orders", ['count' => $api->dataResp->count]);
                $textMessage .= "\n";
				foreach ($api->dataResp->data as $order) {
				    $textMessage .= ControllerLang::trans(
				        "<b>Order from %date%:</b>\nOrder number: %order_code%,\nEstimated readiness date: %order_plan_of_production%,\nOrder details: <a href='https://q.ifurn.pro/v/p/%order_code%'>More</a>\n\n",
                        [
                            'date' => date('Y-m-d', strtotime($order->date)),
                            'order_code' => $order->id,
                            'order_plan_of_production' => date('Y-m-d', strtotime($order->plan_of_production))
                        ]
                    );
				}
			}
            new LoggerLog($textMessage, 'send_text.txt');
			$this->view->text = $textMessage;

		}

		private function getUserOrderStatus()
		{
			$textMessage = ControllerLang::trans("Select order status:");

			$statusArray = [
				2 => '👌' . ControllerLang::trans("Confirmed"),
				3 => '⚡' . ControllerLang::trans("Confirmed for production"),
				4 => '🦾' . ControllerLang::trans("In work"),
				5 => '✅' . ControllerLang::trans("Completed"),
				6 => '📦' . ControllerLang::trans("Ready for shipment"),
				7 => '🚛' . ControllerLang::trans("Shipped"),
				8 => '❌' . ControllerLang::trans("Canceled"),
			];

			$telephone = $this->user->number_phone;
			$dataRequest = [
				'phone' => $telephone,
				'count' => 1
			];

			$this->api->sendRequest($dataRequest);
			$countOrders = $this->api->dataResp;


			foreach ($statusArray as $key => $value) {
				$buttonMessage['inline_keyboard'][][] =
						[
							'text' => $value . ' (' . (($countOrders->$key) ?? '0') . ')',
							'callback_data' => 'getUserOrders_'.$key
						];
			}

			$this->view->text = $textMessage;
			$this->view->inline_keyboard = $buttonMessage;
		}

		private function getLanguagesMenu()
        {
            $this->view->text = "🌐" . ControllerLang::trans("Select Language:");
            $this->view->inline_keyboard['inline_keyboard'][] = [
                [
                    'text' => ControllerLang::trans("ua"),
                    'callback_data' => 'setUserLanguage_ua'
                ],
                [
                    'text' => ControllerLang::trans("en"),
                    'callback_data' => 'setUserLanguage_en'
                ],
                [
                    'text' => ControllerLang::trans("ru"),
                    'callback_data' => 'setUserLanguage_ru'
                ],
            ];
        }

        private function setUserLanguage($lang)
        {
            if($this->db->setLanguageByUserId($this->user->user_id, $lang)) {
                ControllerLang::$lang = $lang;
                $this->sendMenuUser();
                $this->view->text =  ControllerLang::trans("Thank you, now I will communicate with you in English");
            }
        }

		public function __call($name, $arguments)
		{
			$funcNameArr = explode('_', $name);
			if(method_exists($this, $funcNameArr[0])){
				$func = $funcNameArr[0];
				$arr = $funcNameArr[1];
				$this->$func($arr);
			}
		}
		private function getOrderStatus($order)
		{
			$this->view->text = ControllerLang::trans("Follow this link");
			$this->view->inline_keyboard = [
				'inline_keyboard' => [
					[
						['text' => ControllerLang::trans("Information about order"), 'url' => 'https://q.ifurn.pro/v/p/' . $order],
					],
				]
			];

		}
		private function getAnswerServer()
		{
			$return = array(
				'status' => 200
			);
			print_r(json_encode($return));
			header('Connection: close');
			header('Content-Length: '.ob_get_length());

		}
	}