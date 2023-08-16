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

class BalanceUpdated implements ShouldQueue
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
        $conf->set("bootstrap.servers", 'kafka:19092');
        $conf->set('metadata.broker.list', 'kafka:19092');
        $conf->set('enable.auto.commit', 'false');
        $conf->set('auto.commit.interval.ms', 9999999);
        $conf->set('auto.offset.reset', 'latest');
        $conf->set("debug", 'consumer,topic,fetch');

        $consumer = new KafkaConsumer($conf);
        $consumer->subscribe(['BalanceUpdated']);

        $consumer->newTopic("BalanceUpdated");


            $message = $consumer->consume(200);

            switch($message->err) {

                case RD_KAFKA_RESP_ERR_NO_ERROR:

                $json = json_decode($message->payload);

                DB::table('balance')
                        ->where('userId', '=', $json->userId)
                        ->update(['balance' => $json->balance]);
               $consumer->commit();
                    //  dd($topic);
                break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    $consumer->close();
                    // Не ошибка, просто достигнут конец очереди
                break;
                default:
                    error_log("Error consuming message BalanceUpdate: " . $message->errstr() . "\n");

                    // Здесь можно добавить обработку ошибок, например, запись в лог
                    echo "Error consuming message: " . $message->errstr() . "\n";
                break;
            }
        }
   // }
}
