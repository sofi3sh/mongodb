<?php

class ViewViberSendMessage
{
    private $chatId;
    private $buttons = [];
    private $auth_token;

//		private $send_name = "iFurnPro";

    function __construct($auth_token)
    {
        $this->auth_token = $auth_token;
    }

    public function clearButton()
    {
        $this->buttons = [];
    }

    public function addButton($text, $ActionBody, $ActionType = 'reply', $columns = 6)
    {
        return $this->buttons[] =
            [
                "ActionType" => $ActionType,
                "ActionBody" => $ActionBody,
                "Text" => $text,
                "TextSize" => "regular",
                "Columns" => $columns,
                "Rows" => 1,
                "BgColor" => "#FFFFFF",
            ];
    }

    // Готовим message
    public function sendMsg($text, $type = 'text', $tracking_data = Null, $arr_asoc = Null)
    {
        $auth_token = $this->auth_token;

        $data['auth_token'] = $auth_token;
        $data['receiver'] = $this->chatId;
        $data['type'] = 'text';
        $data['text'] = $text;
        $data['min_api_version'] = 3;
        if (!empty($this->buttons)) {
            $data['keyboard'] = [
                "DefaultHeight" => false,
                "BgColor" => "#FFFFFF",
                "Buttons" => $this->buttons,
            ];
        }


        if ($tracking_data != Null) {
            $data['tracking_data'] = $tracking_data;
        }
        if ($arr_asoc != Null) {
            foreach ($arr_asoc as $key => $val) {
                $data[$key] = $val;
            }
        }

        return $this->sendReq($data);
    }

    public function sendMsgText($text, $tracking_data = Null)
    {
        return $this->sendMsg($text, "text", $tracking_data);
    }

    public function setUserId($userId)
    {
        $this->chatId = $userId;
    }

    /**
     * @data готовый данные который будем скидавать на вайбер вебхук
     * Метод нужен для отправки
     * */
    private function sendReq($data)
    {
        $request_data = json_encode($data);
        new LoggerLog($data, 'viber_aut');

        $ch = curl_init("https://chatapi.viber.com/pa/send_message");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        new LoggerLog($response, 'answer_log');
        if ($err) {
            return $err;
        } else {
            return $response;
        }
    }

    // Записать вебхук ( Если нужно будет поменять адрес просто поменяйте параметр URL в контролере )
    public static function setWebHook($botToken)
    {
        $jsonData =
            '{
                "auth_token": "' . $botToken . '",
                "url": "https://bot.ifurn.pro/webhook/viber/' . $botToken . '",
                "event_types": ["subscribed", "unsubscribed", "delivered", "message", "seen"]
            }';

        $ch = curl_init('https://chatapi.viber.com/pa/set_webhook');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);
        if (json_decode($response)->status == 0) {
            return true;
        } else {
            return false;
        }
    }
}