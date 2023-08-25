<?php

namespace App\Console;

use App\Jobs\Accounting;
use App\Jobs\BalanceUpdated;
use App\Jobs\TaskCreated;
use App\Jobs\TaskCreated_Version1;
use App\Jobs\TaskStatusUpdate;
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

           // while (true) {
                $message = $consumer->consume(120*1000);
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $json = json_decode($message->payload);

                        DB::table('balance')
                            ->where('userId', '=', $json->userId)
                            ->update(['balance' => $json->balance]);

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
              //  }

            }})->everySecond();

        $schedule->call(function () {
            $conf = new \RdKafka\Conf();

            $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
                switch ($err) {
                    case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                        echo "Assign: ";
                        var_dump($partitions);
                        $kafka->assign($partitions);
                        return true;
                    break;

                    case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                        echo "Revoke: ";
                        var_dump($partitions);
                        $kafka->assign(NULL);
                        return true;
                    break;

                    default:
                        throw new \Exception($err);
                }
            });

            $conf->set('group.id', 'mygroup');

            $conf->set('metadata.broker.list', 'kafka:19092');

            $conf->set('auto.offset.reset', 'earliest');

            $consumer = new \RdKafka\KafkaConsumer($conf);

            $consumer->subscribe(['TaskLifeStyleVersion1']);

            echo "Waiting for partition assignment... (make take some time when\n";
            echo "quickly re-joining the group after leaving it.)\n";

                $message = $consumer->consume(120*1000);
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        $json = json_decode($message->payload);
                        $balance = DB::table('balance')
                            ->where('userId','=', $json->userId)
                            ->get('balance')
                            ->first();

                        DB::table('balance')
                            ->where('userId', '=', $json->userId)
                            ->update(['balance' => ($balance->balance) - $json->price]);

                        $consumer->commit($message);
                    return true;
                    case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                        echo "No more messages; will wait for more\n";
                        return true;
                    break;
                    case RD_KAFKA_RESP_ERR__TIMED_OUT:
                        echo "Timed out\n";
                        return true;
                    break;
                    default:
                        throw new \Exception($message->errstr(), $message->err);
                    break;
          //      }

            }})->everyFiveSeconds();

        $schedule->call(function () {
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

            // while (true) {
            $message = $consumer->consume(120*1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $json = json_decode($message->payload);

                    DB::table('accounting;')
                        ->insert([
                            'userId' => $json->userId,
                            'sum' => "+  $json->price"
                        ]);

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
                //  }

            }})->everySecond();

        $schedule->call(function () {
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

            $consumer->subscribe(['TaskLifeStyleVersion1, TaskLifeStyleVersion2']);

            echo "Waiting for partition assignment... (make take some time when\n";
            echo "quickly re-joining the group after leaving it.)\n";

            // while (true) {
            $message = $consumer->consume(120*1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $json = json_decode($message->payload);

                    DB::table('accounting;')
                        ->insert([
                            'userId' => $json->userId,
                            'sum' => "-  $json->price"
                        ]);

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
                //  }

            }})->everySecond();

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
