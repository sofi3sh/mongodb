<?php


	class ControllerViberBot
	{
        const DBTABLE = 'bot_viber_users';

        /**
		 *  Конфиги
		 * @auth_token,
		 * @send_name имя бота
		 * @is_log записавать логи или нет
		 * @webhook url
		 * */

		private $is_log = true;
		private $sender_id;
		private $sender_name;
		private $api;
		private $db;
		private $view;
		private $request;
		private $activeUser;




		function __construct(array $url)
		{
			header("HTTP/1.1 200 OK");
		    try {
                require_once $_SERVER['DOCUMENT_ROOT'] . '/model/ModelWebHook.php';
			    require_once $_SERVER['DOCUMENT_ROOT'] . '/view/ViewViberSendMessage.php';
			    $this->api = new ApiIfurn();
			    $this->db = new ModelWebHook();
                $this->getRequest();
			    $this->activeUser = $this->db->getUser($this->sender_id, $this::DBTABLE);

                ControllerLang::$bot = 'viber_bot';
                $langArray = explode('-', $this->request->sender->language);
                ControllerLang::$lang = (!empty($this->activeUser->lang)) ? $this->activeUser->lang : array_shift($langArray);

                $keyBot = substr(end($url), 0, strpos(end($url), "?"));
                $this->view = new ViewViberSendMessage($keyBot);
                $this->view->setUserId($this->sender_id);
                $this->index();
            } catch (Throwable $e) {
		        new LoggerLog($e, 'error_log');
            }


		}

		public function index()
		{

			$input = $this->request;
			if ($input->event == "subscribed") {
//				$this->sendMsgText($sender_id, "Спасибо, что подписались на нас!");
			} elseif (
                $input->event == "conversation_started" ||
                (!isset($this->activeUser->status) && empty($input->message->contact))
            )
            {
				new LoggerLog($this->request, 'trice_log');
				$this->view->addButton(ControllerLang::trans("<b>Send number</b>"), 'reply', 'share-phone');
				$this->view->sendMsg(ControllerLang::trans("We are glad to welcome you! Please provide your phone number so we can identify you."));
			} elseif ($input->event == "message") {
				///Нужна проверка зарегестрирован ли?

				if (!empty($input->message->contact)) {
					$this->getNumber($input->message->contact);
				} else {
					//Обработка других запросов и колл бєков
					$this->getAnswersToMessage($input->message);
				}

			}
		}
        
        private function getAnswersToMessage($message)
        {
            $text = mb_strtolower($message->text);

            switch (true) {
                case ($text == 'привет') :
                    $this->view->sendMsgText("Здаров!");
                    break;
                case ($text == 'reply-orders') :
                    $this->getUserOrderStatus();
                    break;
                case ($text == 'menu-language') :
                    $this->getUserMenuLanguage();
                    break;
                case (stripos($text, 'reply-orders_') !== false) :
                    $funcValue = explode('_', $text);
                    $this->getReplyOrders($funcValue[1]);
                    break;
                case (stripos($text, 'set-language_') !== false) :
                    $funcValue = explode('_', $text);
                    $this->setUserLanguageByCode($funcValue[1]);
                    break;


                default :
                    $this->view->addButton(ControllerLang::trans("<b>Orders list</b>"), 'reply-orders');
                    $this->view->addButton(ControllerLang::trans("<b>Set Language</b>"), 'menu-language');
                    $this->view->sendMsgText(ControllerLang::trans('I don\'t understand you'));
            }
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

			$dataRequest = [
				'phone' =>  $this->activeUser->number_phone,
				'count' => 1
			];

			$this->api->sendRequest($dataRequest);
			$countOrders = $this->api->dataResp;
			if (empty($this->api->error) && !empty($countOrders)) {
				foreach ($statusArray as $key => $status) {
					$this->view->addButton("<b>" . $status . " (". (($countOrders->$key) ?? '0') .")</b>", 'reply-orders_' . $key, 'reply' , 3);
				}
			}
			$this->view->sendMsg($textMessage);

		}

        private function getUserMenuLanguage()
        {
            $languages = [
                'ua' => 'ua',  
                'en' => 'en',  
                'ru' => 'ru',  
            ];
            $textMessage = ControllerLang::trans("Select language:");
            foreach ($languages as $key => $lang) {
                $this->view->addButton("<b>$lang</b>", 'set-language_' . $key, 'reply', 3);

            }
            
            $this->view->sendMsg($textMessage);
        }
        
        private function setUserLanguageByCode($iso)
        {
            ControllerLang::$lang = $iso;
            $this->db->setLanguageByUserId($this->activeUser->user_id, $iso, $this::DBTABLE);
            $textMessage = ControllerLang::trans("Thank you, now I will communicate with you in English");
            $this->view->addButton(ControllerLang::trans("<b>Orders list</b>"), 'reply-orders');
            $this->view->sendMsg($textMessage);
        }

		private function getReplyOrders($status)
		{
			$dataRequest = [
				'phone' => $this->activeUser->number_phone,
				'status' => $status
			];


			$this->api->sendRequest($dataRequest);
			$api = $this->api;
			if (empty($api->error) && !empty($api->dataResp)) {
				$textMessage = ControllerLang::trans('You have %count% orders', ['count' => $api->dataResp->count]);
                $textMessage .= "\n";
				foreach ($api->dataResp->data as $order) {
                    $textMessage .= ControllerLang::trans(
                        "*Order from* %date%:\nOrder number: %order_code%,\nEstimated readiness date: %order_plan_of_production%,\nOrder details: https://q.ifurn.pro/v/p/%order_code%\n\n",
                        [
                            'date' => date('Y-m-d', strtotime($order->date)),
                            'order_code' => $order->code,
                            'order_plan_of_production' => date('Y-m-d', strtotime($order->plan_of_production))
                        ]
                    );
				}
				$this->view->addButton(ControllerLang::trans("<b>Orders list</b>"), 'reply-orders');
				$this->view->addButton(ControllerLang::trans("<b>Set Language</b>"), 'menu-language');
				$this->view->sendMsg($textMessage);
			}

		}


		// Слушаем что получили от Viber ( Если нужно будет добавить какие-то дейсвие тут нужно просто ловить запрос и обработать )

		private function getRequest()
		{
			$request = file_get_contents("php://input");
			$input = json_decode($request);

			new LoggerLog($input, 'viber_in');

			if ($input->event == 'webhook') {
				$webhook_response['status'] = 0;
				$webhook_response['status_message'] = "ok";
				$webhook_response['event_types'] = 'delivered';
				echo json_encode($webhook_response);
				die;
			} elseif (!empty($input)) {
				$this->request = $input;
				$this->sender_id = $input->sender->id ?? '';
				$this->sender_name = $input->sender->name ?? '';
			}
		}

		private function getNumber($contact)
		{
            $this->view->addButton(ControllerLang::trans("<b>Orders list</b>"), 'reply-orders');
            $this->view->addButton(ControllerLang::trans("<b>Set Language</b>"), 'menu-language');
			if(isset($this->activeUser->status)){
				$this->view->sendMsgText(ControllerLang::trans(
                    'You are already registered with a number %number%',
                    ['number' =>  $this->activeUser->number_phone]
                ));
			} elseif (count((array) $contact) === 1) {
				$dbSave = [
					'chat_id' => $this->sender_id,
					'number_phone' => $this->phoneMask($contact->phone_number),
					'name' => $this->sender_name,
                    'lang' => ControllerLang::$lang,
				];
				$this->db->setUser($dbSave, $this::DBTABLE);
				$this->view->sendMsgText(ControllerLang::trans(
                    'Thank you, you will now receive information about orders.'
                ), 'text');
			} else {
                $this->view->clearButton();
                $this->view->addButton(ControllerLang::trans("<b>Send number</b>"), 'reply', 'share-phone');

				$this->view->sendMsgText( ControllerLang::trans(
                        'You are deceiving me %number% is not your number',
                        ['number' => $this->phoneMask($contact->phone_number)]
                    ));
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

		// Записать вебхук ( Если нужно будет поменять адрес просто поменяйте параметр URL в контролере )
		public function actionSetWebhook()
		{
			$jsonData =
				'{
                "auth_token": "' . $this->auth_token . '",
                "url": "' . $this->webhook . '",
                "event_types": ["subscribed", "unsubscribed", "delivered", "message", "seen"]
            }';

			$ch = curl_init('https://chatapi.viber.com/pa/set_webhook');

			echo '<pre>';
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));;

			$response = curl_exec($ch);

			$err = curl_error($ch);
			curl_close($ch);
			if ($err) {
				echo($err);
			} else {
				echo($response);
			}
		}
    }