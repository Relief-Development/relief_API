<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Massage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MassagesController extends Controller
{
    //
    public function registerMassage(Request $req)
    {
        $response = ['status' => 1, 'msg' => ''];

        $validator = Validator::make(
            json_decode($req->getContent(), true),
            [
                "name" => ["required", "max:50"],
                "description" => ["required", "max:280"],
                "image" => ["required"]
            ]
        );

        if ($validator->fails()) {
            $response['status'] = 0;
            $response['msg'] = $validator->errors();
        } else {
            //Generar el nuevo usuario

            $data = $req->getContent();
            $data = json_decode($data);
            $massage = new Massage();

            $massage->name = $data->name;
            $massage->description = $data->description;

            if (isset($data->image) && $data->image) {

                Storage::put($data->name . '_photo', base64_decode($data->image));
                $massage->image = $data->name . '_photo';

                // Storage::put($data->name . '_photo', base64_decode($data->image));
                // $massage->image = 'http://localhost/relief_API/storage/app/'.$data->name.'_photo';
            }

            try {
                $massage->save();
                $response['msg'] = "Masaje guardado con id " . $massage->id;
            } catch (\Exception $e) {
                $response['status'] = 0;
                $response['msg'] = "Se ha producido un error: " . $e->getMessage();
            }
        }
        return response()->json($response);
    }
}
