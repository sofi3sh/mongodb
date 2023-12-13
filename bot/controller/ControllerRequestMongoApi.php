<?php

	include_once $_SERVER['DOCUMENT_ROOT'] . '/model/ModelApi.php';


	class ControllerRequestMongoApi
	{
		private $api_key = '3c6e0b8a9c15224a8228b9a98ca1531d';
		private $response;
		private $db;

        private $data;

		function __construct($params,$method,$func)
		{
			$this->db = new ModelApi();
            $collection = $this->db->connectToMongoDB('cron_time');
            $timestamp = $collection->findOne();
            $new_cron_time = (int)$timestamp['cron_time'] + 172800;
            if($new_cron_time < round(microtime(true))) {
                $collection->updateOne(['_id' => $timestamp['_id']],  ['$set' => ['cron_time' => $new_cron_time]]);
            }
            $params['cron_time'] = $timestamp['cron_time'];
            $this->data = array(
                "key"    =>  $this->api_key,
                "method" =>  $func,
                "params" =>  $params,
            );
            if (method_exists($this, $method)) {
                $this->$method($this->data);
            } else {
                $this->response = [
                    'status' => 'ERROR: METHOD NOT FOUND',
                    'data' => []
                ];
            }

		}

        private function sendApi($data) {
            $curl = curl_init();
            $url = 'http://botchild/mongo';
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data)
            ]);

            $response = curl_exec($curl);
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE); // Отримання HTTP-статусу
            curl_close($curl);

            if ($response === false) {
                $error = curl_error($curl);
                return [
                    'status' => 'ERROR',
                    'data' => $error
                ];
            } elseif ($http_status !== 200) {
                return [
                    'status' => 'error',
                    'data' => 'HTTP Status: ' . $http_status
                ];
            } else {
                return [
                    'status' => 'success',
                    'data' => json_decode($response, true)
                ];
            }
        }
        private function script() {
            $main = $this->db->getOrdersMongo('orders');
            $cron = $this->sendApi($this->data);
            $foundMatch = false;
            if(isset($cron['error']) || !isset($cron['data']) ) {
                var_dump($cron);
                return false;
            }

            foreach ($cron['data'] as $item_child) {
                foreach ($main as $item_main) {
                    if (!isset($item_child['is_archive'])) {
                        if($item_main['md5'] === $item_child['md5']) {
                            var_dump($item_child['_id']);
                            var_dump("UPDATE");
                            echo "<br>";
                            echo "<br>";
                            $this->db->updateOrderMongo('orders',$item_child);
                            $foundMatch = true;
                            break;
                        }
                    }
                }
                if (!$foundMatch) {
                    if(isset($item_child['is_archive'])) {
                        $this->db->OrderToArchiveMongo($item_child);
                    } else {
                        unset($item_child['order']);
                        var_dump($item_child);
                        var_dump("CREATE (OR NOT NECESSARY");
                        echo "<br>";
                        echo "<br>";
                        $this->db->createOrderMongo('orders',$item_child);
                        $this->db->getUpdatedByCronMongo('orders', $item_child, 'creating');
                    }
                } else {
                    $foundMatch = false;
                }

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

