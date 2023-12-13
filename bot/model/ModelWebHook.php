<?php
	use unreal4u\TelegramAPI\Telegram\Types\KeyboardButton;

	class ModelWebHook extends CoreDbConnect
	{

		public function getButtonContact()
		{
			$keyboardButton = new KeyboardButton();
			$keyboardButton->text = ControllerLang::trans('Отправить номер');
			$keyboardButton->request_contact = true;
			$result[][] = $keyboardButton;
			return $result;
		}

		public function getUser(string $id, $table = 'bot_users')
		{
			$sql = "SELECT * FROM `" .$table. "` WHERE `chat_id` = '" . $id . "'";
			return $this->connect($sql, 'r');
		}

		public function setUser(array $dbSave, $table = 'bot_users')
		{

            if (trim(mb_substr($dbSave['number_phone'],0,3))=="+38")
            {
                $dbSave['lang']='ua';
            }
            elseif (trim(mb_substr($dbSave['number_phone'],0,2))=="+7")
            {
                $dbSave['lang']='ru';
            }
            else
            {
                $dbSave['lang']='en';
            }
		    $sql = "INSERT INTO `" .$table. "` (`chat_id`, `number_phone`, `name`, `lang`) VALUES ('" . $dbSave['chat_id'] . "', '" . $dbSave['number_phone'] . "', '" . $dbSave['name'] . "','" . $dbSave['lang'] . "')";

			return $this->connect($sql);
		}

		public function setLanguageByUserId($id_user, $lang, $table = 'bot_users')
        {
            $sql = "UPDATE `" .$table. "` SET lang = '$lang' WHERE user_id = '$id_user'";
            return $this->connect($sql);
        }

	}

