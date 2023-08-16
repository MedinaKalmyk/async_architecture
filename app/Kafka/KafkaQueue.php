<?php

namespace App\Kafka;

use Illuminate\Queue\Queue;
use Illuminate\Contracts\Queue\Queue as QueueConstact ;

class KafkaQueue extends Queue implements QueueConstact{

    protected $consumer, $produser;

    public function __construct($consumer, $produser)
    {
        $this->consumer = $consumer;
        $this->produser = $produser;
    }
    public function size($queue = null) {
        // TODO: Implement size() method.
    }

    public function push($job, $data = '', $queue = null) {

        $topic = $this->produser->newTopic($queue);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, serialize($job));

        $this->produser->flush(5000);
    }

    public function pushRaw($payload, $queue = null, array $options = []) {
        // TODO: Implement pushRaw() method.
    }

    public function later($delay, $job, $data = '', $queue = null) {
        // TODO: Implement later() method.
    }

    public function pop($queue = null) {
        $this->consumer->subscriber([$queue]);

        try {
            $message = $this->consumer->consume(130 * 1000);
            switch($message->err)
            {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    var_dump($message->payload);
                    $job = unserialize($message->payload);
                    $job->handle();
                break;

                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    var_dump("Timeout");
                break;

                default:
                    throw new \Exception($message->errstr(), $message->err);
            }
        } catch(\Exception $e)
        {
            var_dump($e->getMessage());

        }
    }
}
