<?php

	use \unreal4u\TelegramAPI\HttpClientRequestHandler;
    use \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook;
    use \unreal4u\TelegramAPI\TgLog;
	use \unreal4u\TelegramAPI\Telegram\Methods\SendMessage;
	use \unreal4u\TelegramAPI\Telegram\Types\ReplyKeyboardMarkup;
	use \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

	use React\EventLoop\Factory;

	class ViewTelegramSendMessage
	{
		public $text;
		public $keyboard;
		public $inline_keyboard;
		public $chatId;
		private $botToken; //Ifurn
//		private $botToken = '2008949501:AAHzoNgoDnYwDsiCdMHmb3_Gh9uRPajRj8Q'; //Ifurn
//		https://api.telegram.org/bot2008949501:AAHzoNgoDnYwDsiCdMHmb3_Gh9uRPajRj8Q/setWebhook?url=https://bot.ifurn.pro/webhook/telegram
//		private $botToken = '1967624991:AAGWVeIh98iDbX2JSQot-YCx2tS4-T7PdWE'; //Abris

        public function __construct($botToken)
        {
            $this->botToken = $botToken;
        }

        public function sendText($keyboard = false)
		{
			$loop = Factory::create();
			$tgLog = new TgLog($this->botToken, new HttpClientRequestHandler($loop));
			$sendMessage = new SendMessage();

			if (isset($this->chatId)) {
				$sendMessage->chat_id = $this->chatId;
			} else {
				return false;
			}

			if (isset($this->text)) {
				$sendMessage->text = str_replace(["<br>", "<hr>"], "\n", $this->text);
				if(mb_strlen($sendMessage->text) > 10000){
					$sendMessage->text = mb_substr($sendMessage->text, 1, 3900) . "\n Продолжение следует...";
				}
				$sendMessage->parse_mode = 'HTML';
			} else {
				return false;
			}

			if (!empty($this->keyboard)) {
				$sendMessage->reply_markup = new ReplyKeyboardMarkup();
				$sendMessage->reply_markup->one_time_keyboard = true;
				$sendMessage->reply_markup->resize_keyboard = true;
				$sendMessage->reply_markup->keyboard = $this->keyboard;
				$this->keyboard = '';
			}
			if(!empty($this->inline_keyboard)) {
				$sendMessage->reply_markup = new Markup($this->inline_keyboard);
				$this->inline_keyboard = '';
			}

//			new LoggerLog($sendMessage, 'error_telegram_log');
			$promise = $tgLog->performApiRequest($sendMessage);
			$loop->run();
			$promise->then(
				function ($response) {
					$this->answerMessage[] = $response;
				},
				function (\Exception $exception) {
					new LoggerLog('Exception TEXT ' . get_class($exception) . ' caught, message: ' . $exception->getMessage(), 'error_telegram_log');
					return false;
					// Onoes, an exception occurred...

				}
			);
			return true;
		}

		public function Sendkeyboard($keyboard) {
			$this->keyboard = $keyboard;
		}

		public static function setWebHook($botToken)
        {
            $loop = Factory::create();
            $setWebhook = new SetWebhook();
            $setWebhook->url = 'https://api.telegram.org/bot' . $botToken . '/setWebhook?url=https://bot.ifurn.pro/webhook/telegram/' . $botToken;
            $tgLog = new TgLog($botToken, new HttpClientRequestHandler($loop));
            $promise = $tgLog->performApiRequest($setWebhook);
            $loop->run();
            $result = false;
            $promise->then(
                function ($response) use (&$result) {
                    $result = $response->data;
                },
                function (\Exception $exception) {
                    new LoggerLog('Exception TEXT ' . get_class($exception) . ' caught, message: ' . $exception->getMessage(), 'error_telegram_log');
                    return false;
                    // Onoes, an exception occurred...

                }
            );
            return $result;
        }
	}

