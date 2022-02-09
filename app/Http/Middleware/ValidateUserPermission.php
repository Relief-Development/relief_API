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
        //Comprobar los permisos
        if($req->user->role =='Usuario' || $req->user->role =='Masajista'){
            $respuesta['msg'] = "Permisos validados"; 
            return $next($req);
            
        }else{
            $respuesta['msg'] = "No cuenta con permisos para ejecutar esta funcion";   
        }
        return response()->json($respuesta);
    }
}
