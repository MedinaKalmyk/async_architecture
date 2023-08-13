<?php

namespace App\Http\Controllers;
use App\Jobs\TaskCreated;
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
    public function createTask(Request $request)
    {
        $id = [];
        $users = DB::table('users')
            ->get('id');

        foreach ($users->all() as $user)
        {
            $id[] = $user->id;
        }
        $key = array_rand($id, 1);

        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
        ]);

        $conf = new Conf();;
        $conf->set("bootstrap.servers", 'kafka:19092');
        $conf->set('metadata.broker.list', 'kafka:19092');
        $conf->set('group.id', 'group_1');



        $producer = new Producer($conf);

        $tc = new TopicConf();


        $topic = $producer->newTopic("TaskCreated");


        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' =>  (string) rand(10,90),
            'userId' => (string) $users->all()[$key]->id,
        ];


        $json = json_encode($data);

        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $json);

        $producer->flush(200);


        echo "Message published\n";



        TaskCreated::dispatch();

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


}
