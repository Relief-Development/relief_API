<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Massage ;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class MassagesController extends Controller
{
    //
    public function registerMassage(Request $req)
    {

        $respuesta = ['status' => 1, 'msg' => ''];

        $validator = Validator::make(
            json_decode($req->getContent(), true),
            [
                "name" => ["required", "max:50"],
                "description" => ["required", "max:180"]
            ]
        );

        if ($validator->fails()) {
            $respuesta['status'] = 0;
            $respuesta['msg'] = $validator->errors();
        } else {
            //Generar el nuevo usuario

            $data = $req->getContent();
            $data = json_decode($data);
            $massage = new Massage();

            $massage->name = $data->name;
            $massage->description = $data->description;
            

            try {
                $massage->save();
                $respuesta['msg'] = "Masaje guardado con id " . $massage->id;
            } catch (\Exception $e) {
                $respuesta['status'] = 0;
                $respuesta['msg'] = "Se ha producido un error: " . $e->getMessage();
            }

            return response()->json($respuesta);
        }
    }

}