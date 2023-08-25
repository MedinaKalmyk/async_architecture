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

        $conf = new \RdKafka\Conf();

        $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    echo "Assign: ";
                    var_dump($partitions);
                    $kafka->assign($partitions);
                break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    echo "Revoke: ";
                    var_dump($partitions);
                    $kafka->assign(NULL);
                break;

                default:
                    throw new \Exception($err);
            }
        });

        $conf->set('group.id', 'mygroup');

        $conf->set('metadata.broker.list', 'kafka:19092');

        $conf->set('auto.offset.reset', 'earliest');

        $consumer = new \RdKafka\KafkaConsumer($conf);

        $consumer->subscribe(['TaskDone']);

        echo "Waiting for partition assignment... (make take some time when\n";
        echo "quickly re-joining the group after leaving it.)\n";

            $message = $consumer->consume(2000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $json = json_decode($message->payload);
                     DB::table('task')
                    ->where('id', '=', $json->taskId)
                    ->update(['status' => 'done']);
                    $consumer->commit($message);
                break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                break;
            }
        }

}
