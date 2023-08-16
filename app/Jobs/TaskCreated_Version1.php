<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Junges\Kafka\Consumers\ConsumerBuilder;
use Junges\Kafka\Facades\Kafka;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\KafkaConsumer;
use RdKafka\TopicConf;

class TaskCreated_Version1 implements ShouldQueue
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
        $conf->set('auto.commit.interval.ms', 9999999);
        $conf->set('auto.offset.reset', 'largest');
//        $conf->set("auto.commit.interval.ms", 1e3);
//        $conf->set('enable.auto.commit', 'true');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe(['TaskLifeStyleVersion2']);

//        $message = $consumer->consume(2000);
//
//        dd($message);

//        $topic = $consumer->newTopic("TaskLifeStyleVersion2");


      //  while(true) {
            $message = $consumer->consume(20000);

            switch($message->err) {

                case RD_KAFKA_RESP_ERR_NO_ERROR:

                    $json = json_decode($message->payload);

                    DB::table('task')
                        ->insert([
                            'name' => $json->name,
                            'description' => $json->description,
                            'price' => $json->price,
                            'userId' => $json->userId,
                            'title' => $json->name,
                            'jira_id' => $json->jira_id,
                        ]);
                    // }
                    $consumer->commit();
//                    $consumer->close();
                    //  dd($topic);
                break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    $consumer->close();
                    // Не ошибка, просто достигнут конец очереди
                break;
                default:
                    error_log("Error consuming message TaskCreated2: " . $message->errstr() . "\n");

                    // Здесь можно добавить обработку ошибок, например, запись в лог
                    //   echo "Error consuming message: " . $message->errstr() . "\n";
                break;
            }
        }
   // }
}
