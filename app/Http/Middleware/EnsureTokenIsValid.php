<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $tokens =  DB::table('users')
            ->get('api_token');;
        //    dd($tokens);
        foreach ($tokens as $token)
        {
            if ($request->bearerToken() == $token->api_token) {
                return $next($request);
            }

        }
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized',
        ], 401);

    }
}
