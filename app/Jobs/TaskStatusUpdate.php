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

class TaskStatusUpdate implements ShouldQueue
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
        $conf->set('group.id', 'mygroup1');
        $conf->set("bootstrap.servers", 'kafka:19092, kafka1:19092');
        $conf->set('metadata.broker.list', 'kafka:19092, kafka1:19092');
//        $conf->set('enable.auto.commit', 'false');
//        $conf->set('auto.commit.interval.ms', 9999999);
//        $conf->set('auto.offset.reset', 'latest');
//        $conf->set("auto.commit.interval.ms", 1e3);
//        $conf->set("statistics.interval.ms", 5000);
        $conf->set("debug", 'consumer,topic,fetch');
//        $conf->set("enable.partition.eof", 'true');
//        $conf->set("max.partition.fetch.bytes", 10240000);

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe(['TaskDone']);


       // while (true) {
            $consumer->newTopic('TaskDone');
            $consumer->subscribe(["TaskDone"]);
            $message = $consumer->consume(2000);

            $consumer->unsubscribe();


            dd($message);


            switch($message->err) {

                case RD_KAFKA_RESP_ERR_NO_ERROR:

                $json = json_decode($message->payload);

                    DB::table('task')
                        ->where('id', '=', $json->taskId)
                        ->update(['status' => 'done']);

               $consumer->commit();
                    //  dd($topic);
                break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "Close";

                    $consumer->close();
                    // Не ошибка, просто достигнут конец очереди
                break;
                default:
                    error_log("Error consuming message TaskCreated: " . $message->errstr() . "\n");
                    // Здесь можно добавить обработку ошибок, например, запись в лог
                    echo "Error consuming message: " . $message->errstr() . "\n";
                break;
            }
        }
   // }
}
