<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\Notification;
use App\Models\User;
use App\Models\Favorite;
use App\Models\Massage;
use App\Models\Therapist;
use App\Models\Service;

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
            if (Hash::check($data->password, $user->password)) {

                do {
                    $token = Hash::make($user->id . now());
                } while (User::where('api_token', $token)->first());
                $user->api_token = $token;
                $user->save();

                $profile = DB::table('users')
                    ->select(['name', 'email', 'username', 'role']) //falta dirección
                    ->where('users.api_token', 'like', $token)
                    ->get();

                $response['status'] = 1;
                $response['msg'] = "Login correcto.";
                $response['token'] =  $user->api_token;
                $response['profile'] = $profile;
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
            $response['msg'] = "Los datos no pasaron el validador";
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
                $response['status'] = 1;
                $response['msg'] = "Usuario registrado correctamente";
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
            $response['status'] = 1;
            $response['msg'] = "Nueva contraseña generada. Revisa tu correo";
        } else {
            $response['status'] = 2;
            $response['msg'] = "Usuario no encontrado";
        }

        return response()->json($response);
    }

    public function addFavorites(Request $req)
    {
        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $user = User::find($req->user->id);
        $therapist = Therapist::find($data->therapist_id);

        try {
            if ($user) {
                if ($therapist) {
                    $favorites = Favorite::join('users', 'users.id', '=', 'favorites.user_id')
                        ->join('therapists', 'therapists.id', '=', 'favorites.therapist_id')
                        ->attach($user->therapists()->$therapist)
                        ->get();
                    //$user->therapists()->attach($therapist); //syncWithoutDetaching or sync
                    $response['status'] = 1;
                    $response['msg'] = $favorites . "Masajista añadido a favoritos";
                } else {
                    $response['status'] = 6;
                    $response['msg'] = "Masajista no encontrado";
                }
            } else {
                $response['status'] = 2;
                $response['msg'] = "Usuario no encontrado";
            }
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }

        return response()->json($response);
    }

    public function getFavorites(Request $req)
    {
        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);
        
        $user = User::find($req->user->id);
        $favorites = $req->favorites;

        try {
            if ($user) {
                if (!$favorites->isEmpty()) {
                    $response["status"] = 1;
                    $response["msg"] = "Listado de favoritos";
                    $response['favorites'] = $favorites;
                } else {
                    $response["status"] = 7;
                    $response["msg"] = "No tienes favoritos";
                }
            } else {
                $response["status"] = 2;
                $response['msg'] = "Usuario no encontrado";
            }
        } catch (\Exception $e) {
            $response["status"] = 0;
            $response["msg"] = "Se ha producido un error" . $e->getMessage();
        }


        return response()->json($response);
    }

    public function search(Request $req)
    {
        $response = ['status' => 1, "msg" => ""];

        try {
            if ($req->has('search')) {
                $massages = Massage::select('id', 'name')
                    ->where('massages.name', 'like', '%' . $req->input('search') . '%')
                    ->get();
                $response['status'] = 1;
                $response['massages'] = $massages;

                // $services = Service::join('massages', 'massages.id', '=', 'services.massage_id')
                //     ->join('therapists', 'therapists.id', '=', 'services.therapist_id')
                //     ->where('massages.name', 'like', '%' . $req->input('search') . '%')
                //     ->select('massages.name', 'therapists.username')
                //     ->orderBy('therapists.username', 'ASC')
                //     ->get();
                // $response['status'] = 1;
                // $response['services'] = $services;
            }
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }
        return response()->json($response);
    }
}
