<?php

namespace App\Kafka;

use Illuminate\Queue\Connectors\ConnectorInterface;
use RdKafka\Conf;

//use RdKafka\Conf;

class KafkaConnector implements ConnectorInterface {


    public function connect(array $config) {
        $con = new Conf();
        $con->set("bootstrap.servers", 'kafka:19092');
        $con->set('metadata.broker.list', 'kafka:19092');

        $produser = new \RdKafka\Producer($con);

        $con->set("group.id", 'group_1');
        $con->set("metadata.broker.list", 'kafka:19092');


        $con->set("auto.offset.reset", 'earliest');

        $consumer = new \RdKafka\KafkaConsumer($con);


        return new KafkaQueue($consumer, $produser);
    }
}
