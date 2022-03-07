<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateAdminPermission
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

        if ($req->user->role == 'Admin') {
            return $next($req);
        } else {
            $response['status'] = 4;
            $response['msg'] = "No tienes permisos para ejecutar esta función";
        }
        return response()->json($response);
    }
}
