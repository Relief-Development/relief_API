<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class ValidateUserPermission
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
        $respuesta = ["status" => 1, "msg" => ""];

        if ($req->user->role == 'Usuario' || $req->user->role == 'Masajista') {
            return $next($req);
        } else {
            $response['status'] = 4;
            $response['msg'] = "No tienes permisos para ejecutar esta función";
        }
        return response()->json($respuesta);
    }
}
