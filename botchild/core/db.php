<?php
	class CoreDbConnect{

//		const BDSERVER = 'domfurni.mysql.tools';
//		const NAMEPASS = array('name'=>'domfurni_ifurnremove','password'=>'_haX7^Aj34');
		const BDSERVER = 'bot.ifurn.pro';
		const NAMEPASS = array('name'=>'bot_bot','password'=>'5O<]Y^9dWoBv');



		function connect($sql,$work = ''){
//			new LoggerLog($sql,'db_send');
			$mysqli = new mysqli(self::BDSERVER,self::NAMEPASS['name'],self::NAMEPASS['password'],self::NAMEPASS['name']);
			if ($mysqli->connect_errno) {
				printf("Не удалось подключиться: %s\n", $mysqli->connect_error);
				exit();
			};
			$mysqli->set_charset("utf8");
			$result = $mysqli->query($sql);
			if($mysqli->error){
				new LoggerLog($sql,'db_error');
				new LoggerLog($mysqli->error,'db_error');
				return array(
				'text'=>"ERROR SQL: ".$mysqli->error,
				'error'=>$mysqli->error
			);
			}
			if ($work == 'r'){
				$data = $result->fetch_object();
			} elseif ($work == 'all'){
				$data = array();
				while ($row = mysqli_fetch_assoc($result)) {
					$data[] = $row;
				}
			}else{
				$data = $mysqli->affected_rows;
			}
			$mysqli->close();
			return $data;
		}

        function connectToMongoDB($collection_name, $db = 0) {
                    try {
                        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
                        $database = $mongoClient->selectDatabase("childbase");
                        if($db !== 0 ) {
                            return $database;
                        }
                        return $database->selectCollection($collection_name);
                    }
                    catch (MongoDB\Driver\Exception\Exception $e) {
                        return [
                            'error' => 'Помилка підключення до MongoDB: ' . $e->getMessage()
                        ];
                }
        }

	}

