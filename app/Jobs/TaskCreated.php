<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\TopicConf;

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
        var_dump('Hello');
        $conf = new Conf();;
        $conf->set("bootstrap.servers", 'kafka:19092');
        $conf->set('metadata.broker.list', 'kafka:19092');
        $conf->set('group.id', 'group_1');

        $rk = new Consumer($conf);
        $topicConf = new TopicConf();
        $topicConf->set('auto.commit.interval.ms', 9999999);
        $topicConf->set('auto.offset.reset', 'smallest');
        $topicConf->set("auto.commit.interval.ms", 1e3);

        $partition = 0;

        $topic = $rk->newTopic("TaskCreated", $topicConf);

        $conf->set('enable.auto.commit', 'false');
        $topic->consumeStart($partition, RD_KAFKA_OFFSET_BEGINNING);

        $msg = $topic->consume($partition, 200);

        if(!empty($msg)) {

            $topic->offsetStore($partition, $msg->offset);

            $a = explode('0', $msg->payload);

            $json = json_decode($a[0]);

            DB::table('task')
                ->insert([
                    'name' => $json->name,
                    'description' => $json->description,
                    'price' => $json->price,
                    'userId' => $json->userId,
                ]);


        }
    }
}
