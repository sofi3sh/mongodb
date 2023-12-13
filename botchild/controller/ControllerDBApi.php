<?php

	include_once $_SERVER['DOCUMENT_ROOT'] . '/model/ModelApi.php';
class ControllerDBApi
{
    private $response;
    private $db;
    private $api_key = "3c6e0b8a9c15224a8228b9a98ca1531d"; // Змініть на свій ключ

    public function __construct()
    {
        if ($_REQUEST && isset($_REQUEST['key']) && isset($_REQUEST['method'])) {
            $data = $_REQUEST;
            $this->db = new ModelApi();

            $result = $this->ApiMethod($data['key'], $data['method'], $data['params']);
            if ($result) {
                $this->response = $result;
            } else {
                header('HTTP/1.1 405 NOT RESULT');
                $this->response = [
                    'status' => 'ERROR: WRONG REQUEST"',
                    'data' => []
                ];
            }
        } else {
            header('HTTP/1.1 405 NOT RESULT');
            $this->response = [
                'status' => 'ERROR: WRONG REQUEST"',
                'data' => []
            ];
        }
    }

    private function ApiConnection($key)
    {
        return $key === $this->api_key;
    }

    public function ApiMethod($key, $method, $data = 0)
    {

        if (!$this->ApiConnection($key)) {
            return $this->response = [
                'status' => 'ERROR: API CONNECTION',
                'data' => []
            ];
        } elseif (method_exists($this->db, $method)) {
           return $this->db->$method($data);
        } else {
           return $this->response = [
               'status' => 'ERROR: METHOD NOT FOUND',
               'data' => []
            ];
        }
    }

    public function __destruct()
    {
        if (isset($this->response)) {
            header('Content-type: application/json; charset=utf-8');
            echo json_encode($this->response);
        }
    }
}

