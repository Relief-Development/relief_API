<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersController extends Controller
{
    //LOGIN
    public function login(Request $req)
    {

        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $username = $data->username;
        $user = User::where('username', '=', $username)->first();

        if ($user) {
            if (Hash::check($data->password, $user->password)) { //Comprobar la contraseña

                do { //Si todo correcto generar el api token
                    $token = Hash::make($user->id . now());
                } while (User::where('api_token', $token)->first());

                $user->api_token = $token;
                $user->save();
                $response['msg'] = "Login correcto. Api token generado: " . $user->api_token;
            } else {
                $response['status'] = 0;
                $response['msg'] = "La contraseña no es correcta";
            }
        } else {
            $response['status'] = 0;
            $response['msg'] = "Usuario no encontrado";
        }

        return response()->json($response);
    }

    public function registerUser(Request $req)
    {

        $response = ["status" => 1, "msg" => ""];

        $validator = Validator::make(json_decode($req->getContent(), true), [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:App\Models\User,email|max:70',
            'username' => 'required|unique:App\Models\User,username|max:50',
            'password' => 'required|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{6,}/'
        ]);

        if ($validator->fails()) {
            $response['status'] = 0;
            $response['msg'] = $validator->errors();
        } else {
            $data = $req->getContent();
            $data = json_decode($data);

            try {
                $user = new User();

                $user->name = $data->name;
                $user->email = $data->email;
                $user->username = $data->username;
                $user->password = Hash::make($data->password);
            
                $user->save();
                $response['msg'] = "Usuario guardado con id " . $user->id;
            } catch (\Exception $e) {
                $response['status'] = 0;
                $response['msg'] = "Se ha producido un error: " . $e->getMessage();
            }      
        }
        return response()->json($response);
    }
}
