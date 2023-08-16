<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\KafkaConsumer;
use RdKafka\TopicConf;
use Symfony\Component\HttpKernel\Log\Logger;

class TaskCreated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    /**
     * Execute the job.
     */
    public function handle() {

        $conf = new Conf();
        $conf->set('group.id', 'mygroup');
        $conf->set("bootstrap.servers", 'kafka:19092');
        $conf->set('metadata.broker.list', 'kafka:19092');
        $conf->set('enable.partition.eof', 'true');
        $conf->set('auto.offset.reset', 'earliest');
        $conf->set('log_level', (string) LOG_DEBUG);
        $conf->set('debug', 'all');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe(['TaskLifeStyleVersion1']);

//        $topic = $consumer->newTopic("TaskLifeStyleVersion1");


       // while (true) {
            $consumer->newTopic('TaskLifeStyleVersion1');
            $message = $consumer->consume(200);

            switch($message->err) {

                case RD_KAFKA_RESP_ERR_NO_ERROR:

                $json = json_decode($message->payload);

                DB::table('task')
                ->insert([
                    'name' => $json->name,
                    'description' => $json->description,
                    'price' => $json->price,
                    'userId' => $json->userId,
                ]);

               $consumer->commit($message);
                    //  dd($topic);
                break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
//                    $consumer->close();
                    // Не ошибка, просто достигнут конец очереди
                break;
                default:
                    error_log("Error consuming message TaskCreated: " . $message->errstr() . "\n");
                    // Здесь можно добавить обработку ошибок, например, запись в лог
                    echo "Error consuming message: " . $message->errstr() . "\n";
                break;
            }
        }
    //}
}
