<?php

namespace App\Http\Controllers;
use App\Jobs\BalanceUpdate;
use App\Jobs\BalanceUpdated;
use App\Jobs\TaskAssign;
use App\Jobs\TaskCreated;
use App\Jobs\TaskCreated_Version1;
use App\Jobs\TaskStatusUpdate;
use App\Kafka\KafkaQueue;
use Illuminate\Console\Application;
use Illuminate\Console\View\Components\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use RdKafka\Conf;
use RdKafka\Consumer;
use RdKafka\Producer;
use RdKafka\TopicConf;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('test', ['except' => ['login','register']]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Auth::user();


        DB::table('users')
            ->where('id', '=', $user->getAuthIdentifier())
            ->update(['api_token' => Str::random(80)]);


        return response()->json([
            'status' => 'success',
            'token' => (DB::table('users')
                ->where('id', '=', $user->getAuthIdentifier())
                ->get('api_token'))[0]->api_token,
        ]);

    }

    public function register(Request $request){

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);


        $user = DB::table('users')->insert([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'api_token' => Str::random(80),
        ]);

        $id = DB::table('users')
            ->where('email','=',$request->email)
            ->get('id')
            ->first();

        DB::table('balance')->insert([
            'userId' => $id->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'authorisation' => [
                'type' => 'bearer',
            ]
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'authorisation' => [
                'token' => Auth::refresh(),
                'type' => 'bearer',
            ]
        ]);
    }

    /**
     * @throws \Exception
     */
    public function createTask(Request $request) {

        $id = [];
        $users = DB::table('users')
            ->get('id');

        foreach($users->all() as $user) {
            $id[] = $user->id;
        }

        $key = array_rand($id, 1);

        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
        ]);

        if(count(explode('[', $request->name)) == 1) {

            $data = [
                'event_id' => (string)rand(),
                'event_version' => 1,
                'name' => $request->name,
                'description' => $request->description,
                'price' => (string)rand(10, 90),
                'userId' => (string)$users->all()[$key]->id
            ];

            $object = (object)$data;


            $schemaJson = json_decode(file_get_contents('1.json', true));

            // Validate
            $validator = new \JsonSchema\Validator;
            $validator->validate($object,
                $schemaJson
            );

            if($validator->isValid()) {
                $conf = new Conf();;
                $conf->set("bootstrap.servers", 'kafka:19092');
                $conf->set('metadata.broker.list', 'kafka:19092');
                $conf->set('api.version.request', 'false');

                $producer = new Producer($conf);

                $tc = new TopicConf();

                $topicConf = new \RdKafka\TopicConf();
                $topicConf->set('message.timeout.ms', (string) 30000);
                $topicConf->set('request.required.acks', (string) -1);
                $topicConf->set('request.timeout.ms', (string) 5000);

                $topic = $producer->newTopic("TaskLifeStyleVersion1",  $topicConf);

                $json = json_encode($data);

                for ($i = 0; $i < 1000; $i++) {
                    $key = $i % 10;
                    $topic->produce(RD_KAFKA_PARTITION_UA, 0, $json, (string) $key);

                    // trigger callback queues
                    $producer->poll(1);
                }

                $producer->flush(5000);

                TaskCreated::dispatchSync();

                $this->updateBalance($object->userId, $object->price);

                echo "Message published\n";

                return response()->json([
                    'status' => 'success',
                ]);


            } else {
                echo "JSON does not validate. Violations:\n";
                foreach($validator->getErrors() as $error) {
                    echo sprintf("[%s] %s\n", $error['property'], $error['message']);
                }
            }
        } //        dd($validator);

        else {

            $data = [
                'event_id' => (string)rand(),
                'event_version' => 2,
                'name' => $request->name,
                'title' => $request->name,
                'jira_id' => explode(']', explode('[', $request->name)[1])[0],
                'description' => $request->description,
                'price' => (string)rand(10, 90),
                'userId' => (string)$users->all()[$key]->id
            ];

            $object = (object)$data;

            $schemaJson = json_decode(file_get_contents('2.json', true));

            // Validate
            $validator = new \JsonSchema\Validator;
            $validator->validate($object,
                $schemaJson
            );

            if($validator->isValid()) {
                $conf = new Conf();;
                $conf->set("bootstrap.servers", 'kafka:19092');
                $conf->set('metadata.broker.list', 'kafka:19092');
                $conf->set('api.version.request', 'false');

                $producer = new Producer($conf);

                $tc = new TopicConf();

                $topic = $producer->newTopic("TaskLifeStyleVersion2");

                $json = json_encode($data);

                $topic->produce(RD_KAFKA_PARTITION_UA, 0, $json);

                $producer->flush(2000);

                TaskCreated_Version1::dispatchSync();
//                TaskCreated_Version1::dispatch()->onQueue('default');
                    $this->updateBalance($object->userId, $object->price);

                echo "Message published\n";

                return response()->json([
                    'status' => 'success',
                ]);


            } else {
                echo "JSON does not validate. Violations:\n";
                foreach($validator->getErrors() as $error) {
                    echo sprintf("[%s] %s\n", $error['property'], $error['message']);
                }
            }

        }
        return false;
    }

    public function updateBalance($userId, $taskPrice)
    {
        $balance = DB::table('balance')
            ->where('userId','=', $userId)
            ->get('balance')
            ->first();

        $data = [
            'event_id' => (string)rand(),
            'event_version' => 1,
            'balance' => ($balance->balance) - $taskPrice,
            'transaction' => "- $taskPrice",
            'userId' => $userId
        ];

        $conf = new Conf();;
        $conf->set("bootstrap.servers", 'kafka:19092');
        $conf->set('metadata.broker.list', 'kafka:19092');
        $conf->set('api.version.request', 'false');

        $producer = new Producer($conf);

        $topic = $producer->newTopic("BalanceUpdated");


        $json = json_encode($data);


        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $json);

        $producer->flush(2000);

        BalanceUpdated::dispatchSync();

        echo "Message published\n";

        return response()->json([
            'status' => 'success',
        ]);

    }

    public function getTasks(Request $request)
    {

        $user = DB::table('users')
            ->where('api_token','=',$request->bearerToken())
            ->get()
            ->first();
        $id = $user->id;

        $tasks = DB::table('task')
            ->where('userId','=',$id)
            ->get();


        return $tasks->all();
    }

    public function getBalance(Request $request)
    {
        $user = DB::table('users')
            ->where('api_token','=',$request->bearerToken())
            ->get()
            ->first();
        $id = $user->id;

        $balance = DB::table('balance')
            ->where('userId','=',$id)
            ->get();


        return $balance->all();

    }

    public function taskDone(Request $request)
    {

        $request->validate([
            'taskId' => 'required|string',
        ]);

        $user = DB::table('users')
            ->where('api_token','=',$request->bearerToken())
            ->get()
            ->first();
        $id = $user->id;

        $balance = DB::table('balance')
            ->where('userId','=', $id)
            ->get('balance')
            ->first();

        $taskPrice = DB::table('task')
            ->where('id','=', $request->taskId)
            ->get('price')
            ->first();


        $price = $taskPrice->price;
        $data = [
            'event_id' => (string)rand(),
            'event_version' => 1,
            'taskId' => $request->taskId,
            'balance' => ($balance->balance) + ($price),
            'transaction' => "+ $price",
            'userId' => $id,
        ];

        $conf = new Conf();;
        $conf->set("bootstrap.servers", 'kafka:19092');
        $conf->set('metadata.broker.list', 'kafka:19092');


        $producer = new Producer($conf);

        $topic = $producer->newTopic("TaskDone");


        $json = json_encode($data);


        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $json);

        $producer->flush(5000);


        TaskStatusUpdate::dispatchSync();


        $topic = $producer->newTopic("BalanceUpdated");


        $json = json_encode($data);


        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $json);

        $producer->flush(2000);


        BalanceUpdated::dispatchSync();


        return true;

    }

    public function getTransactions(Request $request)
    {
        $user = DB::table('users')
            ->where('api_token','=',$request->bearerToken())
            ->get()
            ->first();
        $id = $user->id;

        $accounting = DB::table('accounting')
            ->where('userId','=',$id)
            ->get();

        return $accounting->all();

    }

    public function reAssignAllTask(Request $request)
    {
        $tasks = DB::table('task')
            ->where('status','=','assign')
            ->get()->all();

        $id = [];
        $users = DB::table('users')
            ->get('public_user_id');

        foreach($users->all() as $user) {
            $id[] = $user->public_user_id;
        }

        $data = [
            'event_id' => (string)rand(),
            'event_version' => 1,
            'tasks' => $tasks,
            'userId' => $id,
        ];
       // $key = array_rand($id, 1);

        $conf = new Conf();;
        $conf->set("bootstrap.servers", 'kafka:19092');
        $conf->set('metadata.broker.list', 'kafka:19092');


        $producer = new Producer($conf);

        $topic = $producer->newTopic("ReAssignTasks");


        $json = json_encode($data);


        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $json);

        $producer->flush(5000);


        TaskAssign::dispatch();

        return true;

    }


}
