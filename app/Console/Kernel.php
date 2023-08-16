<?php

namespace App\Console;

use App\Jobs\Accounting;
use App\Jobs\BalanceUpdated;
use App\Jobs\TaskCreated;
use App\Jobs\TaskCreated_Version1;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {

        $schedule->call(function () {

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

            while (true)
            {
                $message = $consumer->consume(200);

                switch($message->err) {

                    case RD_KAFKA_RESP_ERR_NO_ERROR:

                        $json = json_decode($message->payload);

                        DB::table('accounting')
                            ->insert([
                                'userId' => 1,
                                'sum' => $json->transaction,
                            ]);
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

        })->everyMinute();

        $schedule->call(function () {

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

            while (true)
            {
                $message = $consumer->consume(200);

                switch($message->err) {

                    case RD_KAFKA_RESP_ERR_NO_ERROR:

                        $json = json_decode($message->payload);

                        DB::table('accounting')
                            ->insert([
                                'userId' => 1,
                                'sum' => $json->transaction,
                            ]);
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

        })->everyMinute();

        $schedule->call(function () {
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
    })->everyMinute();

        $schedule->call(function () {
            $conf = new Conf();
            $conf->set('group.id', 'mygroup');
            $conf->set("bootstrap.servers", 'kafka:19092');
            $conf->set('metadata.broker.list', 'kafka:19092');
            $conf->set('enable.partition.eof', 'true');
            $conf->set('auto.offset.reset', 'earliest');
            $conf->set('log_level', (string) LOG_DEBUG);
            $conf->set('debug', 'all');

            $consumer = new KafkaConsumer($conf);
            $consumer->subscribe(['ReAssignTasks']);

//        $topic = $consumer->newTopic("TaskLifeStyleVersion1");

            while (true) {
                $consumer->newTopic('ReAssignTasks');
                $message = $consumer->consume(200);

                switch($message->err) {

                    case RD_KAFKA_RESP_ERR_NO_ERROR:

                        $json = json_decode($message->payload);

                        foreach ($json->tasks as $task) {
                            DB::table('task')
                                ->where('id', '=', $task->id)
                                ->update(['userId' => array_rand($json->userId, 1)]);
                            $consumer->commit();
                        }

                        $consumer->commit($message);
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

        })->everyMinute();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
