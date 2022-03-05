<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\Notification;
use App\Models\User;

class UsersController extends Controller
{
    public function login(Request $req)
    {

        $response = ["status" => 1, "msg" => "", "token" => "", "profile" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $username = $data->username;
        $user = User::where('username', '=', $username)->first();
        $token = $user->api_token;

        if ($user) {
            if (Hash::check($data->password, $user->password)) { //Comprobar la contraseña

                do {
                    $token = Hash::make($user->id . now());
                } while (User::where('api_token', $token)->first());
                // if ($token) {
                    $user->api_token = $token;
                    $user->save();

                    $profile = DB::table('users')
                        ->select(['name', 'email', 'username', 'role'])//falta dirección
                        ->where('users.api_token', 'like', $token)
                        ->get();

                    $response['msg'] = "Login correcto.";
                    $response['token'] =  $user->api_token;
                    $response['profile'] = $profile;
                // } else {
                //     $response['status'] = 0;
                //     $response['msg'] = "Token no generado";
                // }
            } else {
                $response['status'] = 3;
                $response['msg'] = "La contraseña no es correcta";
            }
        } else {
            $response['status'] = 2;
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
            'password' => 'required|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{6,}/',
            'role' => 'required|in:Usuario,Masajista,Admin'
        ]);

        if ($validator->fails()) {
            $response['status'] = 0;
            $response['msg'] = "Datos no pasaron el validador";//$validator->errors();
        } else {
            $data = $req->getContent();
            $data = json_decode($data);

            try {
                $user = new User();

                $user->name = $data->name;
                $user->email = $data->email;
                $user->username = $data->username;
                $user->password = Hash::make($data->password);
                $user->role = $data->role;

                $user->save();
                $response['msg'] = "Usuario registrado correctamente"; //. $user->id;
            } catch (\Exception $e) {
                $response['status'] = 0;
                $response['msg'] = "Se ha producido un error: " . $e->getMessage();
            }
        }
        return response()->json($response);
    }

    public function recoverPassword(Request $req)
    {
        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $email = $req->email;
        $user = User::where('email', '=', $data->email)->first();

        if ($user) {
            $user->api_token = null;

            $password = "aAbBcCdDeEfFgGhHiIjJkKlLmMnNñÑoOpPqQrRsStTuUvVwWxXyYzZ0123456789";
            $passwordCharCount = strlen($password);
            $passwordLength = 8;
            $newPassword = "";

            for ($i = 0; $i < $passwordLength; $i++) {
                $newPassword .= $password[rand(0, $passwordCharCount - 1)];
            }

            Mail::to($user->email)->send(new Notification($newPassword));
            $user->password = Hash::make($newPassword);
            $user->save();
            $response['msg'] = "Nueva contraseña generada. Revisa tu correo";
        } else {
            $response['status'] = 2;
            $response['msg'] = "Usuario no encontrado";
        }

        return response()->json($response);
    }
}
