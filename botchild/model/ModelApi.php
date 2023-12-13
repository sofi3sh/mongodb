<?php
	class ModelApi extends CoreDbConnect
	{

		function __construct()
		{

		}

		public function getIdsChatsByNumber(array $numbers, $table = 'bot_users')
		{
			$sql = "SELECT * FROM `" . $table . "` WHERE `number_phone` IN ( " . (string) sprintf("'%s'", implode('\', \'',$numbers ) ) . ");";
			$ids = $this->connect($sql, 'all');
			$result = [];

			foreach ($ids as $id) {
				$result[$id['number_phone']]['chat_id'] = $id['chat_id'];
			}
			return $result;
		}

		public function setMessageSend(string $tel, string $text, $status, $id_messenger = '', $messenger = '')
		{
			$sql = "INSERT INTO `bot_send_message` (`phone`, `text`, `id_messenger`, `messenger`, `status`) VALUES ('" . $tel . "', '" . addslashes($text) . "', '" . $id_messenger . "', '" . $messenger . "',  '" . $status . "')";
			return $this->connect($sql);
		}

		public function deleteOldMessage(int $days)
		{
			$sql = "DELETE FROM `bot_send_message` WHERE `date_send` <= NOW() - INTERVAL $days DAY";
			return $this->connect($sql);
		}
        ////////////////////    MONGO DB   ////////////////////
        ///////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////


        public function testApi($data) {
            return "connection is OK";
        }
        public function getOrdersMongo($collection_name) {

            $collection = $this->connectToMongoDB($collection_name);
            $results = [];

            foreach ($collection->find() as $document) {
                $data = $document->getArrayCopy();
                $results[] = $data;
            }


            return $results;
        }

        private function findOrderMongo($data) {
            $collection = $this->connectToMongoDB($data['col_name']);
            $result = $collection->findOne([
                '_id' => $data['id']
            ]);
            return $result->getArrayCopy();

        }
        public function getOrdersMongoCron($data) {
            $db = $this->connectToMongoDB($data['col_name'], 1);
            if($data['time'] === "timestamp") {
                $timestamp = $data['cron_time'];
                $start_timestamp = (int)$timestamp;
                $end_timestamp = (int)$timestamp + 172800;
            } else {
                $fiveMinutesAgo = new DateTime('now');
                $fiveMinutesAgo->sub(new DateInterval('PT5M'));
                $start_timestamp = $fiveMinutesAgo->setTime($fiveMinutesAgo->format('H'), $fiveMinutesAgo->format('i'), 0)->getTimestamp();
                $end_timestamp = $fiveMinutesAgo->setTime($fiveMinutesAgo->format('H'), $fiveMinutesAgo->format('i') + 4, 59)->getTimestamp();
            }


            $filter = [
                'record_update' => [
                    '$gt' => $start_timestamp,
                    '$lt' => $end_timestamp
                ]
            ];
            $collection_names = ['orders','archive'];
            $results = [];
            foreach ($collection_names as $col_name) {
                $collection = $db->selectCollection($col_name);
                $cursor = $collection->find($filter);
                foreach ($cursor as $document) {
                    $data_document = $document->getArrayCopy();
                    if($col_name === 'archive') {
                        $data_document['is_archive'] = 1;
                    }

                    $results[] = $data_document;
                }
            }

            return $results;
        }

        public function createOrderMongo($collection_name, $data) {
            $collection = $this->connectToMongoDB($collection_name);
            $collection->insertOne($data);
        }
        public function updateOrderMongo($collection_name, $data) {
            $collection = $this->connectToMongoDB($collection_name);
            $this->getUpdatedByCronMongo($collection_name, $data, 'updating');
            $result = $collection->updateOne(
                ['_id' => $data['_id']],
                ['$set' => $data]
            );
        }
        public function getUpdatedByCronMongo($collection_name,$order, $operation) {
            $cron_collection = $this->connectToMongoDB('updated_by_cron');
            $order_exists = $cron_collection->findOne(['_id' => $order['_id']]);
            if($order_exists) {
                $cron_collection->deleteOne(['_id' => $order['_id']]);
            }
            $order['collection_name'] = $collection_name;
            $order['operation'] = $operation;
            $this->createOrderMongo("updated_by_cron", $order);
        }
        public function OrderToArchiveMongo($order) {
            $db = $this->connectToMongoDB('1', 1);
            $all_collections_name = $db->listCollectionNames();;
            $archive_collection = $this->connectToMongoDB('archive');
            $order_exists = $archive_collection->findOne(['_id' => $order['_id']]);
            if(!$order_exists) {
                var_dump("TO ARCHIVE");
                echo "<br>";
                echo "<br>";
                $this->createOrderMongo('archive', $order);
                foreach ($all_collections_name as $collection_name) {
                    $collection = $this->connectToMongoDB($collection_name);
                    $collection->deleteOne(['_id' => $order['_id']]);
                    $order['collection_name'] = $collection_name;
                    $this->getUpdatedByCronMongo($collection_name, $order, 'archive and deletion');
                }
            } else {
                var_dump("ALREADY ARCHIVED");
                echo "<br>";
                echo "<br>";
            }
            return 1;
        }
	}

