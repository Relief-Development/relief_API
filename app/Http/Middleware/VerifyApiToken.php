<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class VerifyApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $req, Closure $next)
    {
        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        if (isset($data->api_token)) {
            $user = User::where('api_token', $data->api_token)->first();
            if ($user) {
                $response["msg"] = "Api token válido";
                $req->user = $user;
                return $next($req);
            } else {
                $response["msg"] = "Token inválido";
            }
        } else {
            $response["status"] = 5;
            $response["msg"] = "Token no ingresado";
        }

        return response()->json($response);
    }
}
