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
        $respuesta = ["status" => 1, "msg" => ""];
        //Comprobar los permisos
        if($req->user->role =='Admin'){
            $respuesta['msg'] = "Permisos validados"; 
            return $next($req);
            
        }else{
            $respuesta['msg'] = "No cuenta con permisos para ejecutar esta funcion";   
        }
        return response()->json($respuesta);
    }
}