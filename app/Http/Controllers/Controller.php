<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Junges\Kafka\Facades\Kafka;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

}
