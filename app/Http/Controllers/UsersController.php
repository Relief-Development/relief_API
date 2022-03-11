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
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{
    public function login(Request $req)
    {
        $response = ["status" => 1, "msg" => "", "token" => "", "profile" => "", "image" => ""];
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

                // $profile = DB::table('users')
                //     ->select(['name', 'email', 'username', 'role', 'created_at']) //falta dirección
                //     ->where('users.api_token', 'like', $token)
                //     ->get();
                $profile = User::where('users.api_token', 'like', $token)
                    ->get();

                if ($user->image != "" && Storage::exists($user->image)) {
                    $image = base64_encode(Storage::get($user->image));
                    //$type = Storage::mimeType($user->image);
                    $response['image'] = $image;
                }

                //agregar foto decodificada si la imagen y el tipo son enviados
                //if (isset($data->image) && $data->image && isset($data->imageType) &&  $data->imageType) {
                // Storage::put($user->username . '_photo.' . $data->imageType, base64_decode($data->image));
                //}

                $response['status'] = 1;
                $response['msg'] = "Login correcto.";
                $response['token'] =  $user->api_token;
                $response['profile'] = $profile;

                //$response['type'] = $type;
            } else {
                $response['status'] = 3;
                $response['msg'] = "Los datos no son válidos"; //La contraseña no es correcta
            }
        } else {
            $response['status'] = 2;
            $response['msg'] = "Los datos no son válidos"; //Usuario no encontrado
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

                //agregar foto decodificada si la imagen y el tipo son enviados
                //if (isset($data->image) && $data->image && isset($data->imageType) &&  $data->imageType) {
                // Storage::put($user->username . '_photo.' . $data->imageType, base64_decode($data->image));
                // $user->image = $user->username . '_photo.' . $data->imageType;
                //}

                if (isset($data->image) && $data->image) {
                    Storage::put($user->username . '_photo', base64_decode($data->image));
                    $user->image = $user->username . '_photo';
                    // Storage::put($user->username . '_photo.' . $data->imageType, base64_decode($data->image));
                    // $user->image = $user->username . '_photo.' . $data->imageType;
                }

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

        $user = User::where('api_token', $req->api_token)->first();
        $therapist = Therapist::find($data->therapist_id);
 
        try {
            if ($user) {
                if ($therapist) {
                    $user->favoriteTherapists()->syncWithoutDetaching($therapist); //no se añade el mismo masajista dos veces
                    $response['status'] = 1;
                    $response['msg'] = "Masajista añadido a favoritos";
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

    public function removeFavorites(Request $req) //POR COMPLETAR (borra todos los favs)
    {
        $response = ["status" => 1, "msg" => "", "token" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $user = User::where('api_token', $req->api_token)->first();
        $favorite = Favorite::where('id', $data->favorite_id)->first();
        //$favorite = Favorite::find($data->favorite_id);
        //$favorite = Favorite::where('id', $favorite_id)->first(); //si lo meto en la ruta
        print($favorite);
        try {
            if ($user) {
                if (!$user->favoriteTherapists->isEmpty()) {
                    // foreach ($user->favoriteTherapists as $therapist) { //lo detacho para no eliminar también el masajista
                    //     $user->favoriteTherapists()->detach($therapist);
                    // }

                    // $favorite->delete();
                    $response['msg'] = $favorite;
                    // $response['status'] = 1;
                    // $response['msg'] = "Masajista eliminado de favoritos";
                }else {
                    $response['status'] = 8;
                    $response['msg'] = "Favorito no encontrado";
                }
            } else {
                $response["status"] = 2;
                $response['msg'] = "Usuario no encontrado";
            }
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }
        return response()->json($response);
    }

    public function addRemoveFavorites(Request $req)
    {


        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $user = User::where('api_token', $data->api_token)->first();
        $therapist = Therapist::find($data->therapist_id);
        $favorite = Favorite::where('user_id', 'like', $user->id && 'therapist_id', 'like', $therapist->id);

        if ($favorite) { //Función para eliminar favorito

            $favoriteToDelete = DB::table('favorites')
                    ->where('favorites.id', '==', $favorite->id)->delete();

        } else { //Función para crear favorito
            
            try {

                $newFavorite = new Favorite();

                $newFavorite->user_id = $data->user_id;
                $newFavorite->therapist_id = $data->therapist_id;
                $newFavorite->save();
                $response['status'] = 1;
                $response['msg'] = "Masajista añadido a favoritos";
                
            } catch (\Exception $e) {
                $response['status'] = 0;
                $response['msg'] = "Se ha producido un error: " . $e->getMessage();
            }
        }
        return response()->json($response);
    }

    public function getFavorites(Request $req)
    {
        $response = ["status" => 1, "msg" => ""];

        $user = User::find($req->user->id);

        try {
            if ($user) {
                if (!$user->favoriteTherapists->isEmpty()) {
                    $response["status"] = 1;
                    $response["msg"] = "Listado de favoritos";
                    $response['favorites'] = $user->favoriteTherapists;
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

                $services = Service::join('massages', 'massages.id', '=', 'services.massage_id')
                    ->join('therapists', 'therapists.id', '=', 'services.therapist_id')
                    ->where('massages.name', 'like', '%' . $req->input('search') . '%')
                    ->orWhere('therapists.name', 'like', '%' . $req->input('search') . '%')
                    ->select('therapists.name')
                    ->orderBy('therapists.name', 'ASC')
                    ->get();
                $response['status'] = 1;
                $response['services'] = $services;
            }
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }
        return response()->json($response);
    }

    public function getTherapistInMap(Request $req) //Por completar con localización
    {
        $response = ["status" => 1, "msg" => ""];

        try {
            if ($req->has('search')) {
                $therapists = DB::table('therapists')
                    ->where('therapists.name', 'like', '%' . $req->input('search') . '%')
                    ->select('therapists.name', 'therapists.location')
                    ->get();
                $response["status"] = 1;
                $response['msg'] = "Masajistas encontrados";
                $response['therapists'] = $therapists;
            }
        } catch (\Exception $e) {
            $response["status"] = 0;
            $response["msg"] = "Se ha producido un error" . $e->getMessage();
        }
        return response()->json($response);
    }

    function editProfile(Request $req)
    {
        $respuesta = ["status" => 1, "msg" => ""];

        $data = $req->getContent();
        $data = json_decode($data);

        //Buscar el email
        //$apitoken = $data->api_token;
        $requestedToken = $data->api_token;
        //Validacion

        try {

            $requestedUser = User::where('api_token', $requestedToken)->first();

            if (isset($data->email)) {

                if ($requestedUser->email == $data->email) {

                    $validator = Validator::make(
                        json_decode($req->getContent(), true),
                        [
                            'name' => 'max:50',
                            //'email' => 'required|email|unique:App\Models\User,email|max:70',
                            //'username' => 'required|unique:App\Models\User,username|max:50',
                            'password' => 'regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{6,}/',
                            //'role' => 'in:Usuario,Masajista,Admin'
                        ]
                    );
                } else {

                    $validator = Validator::make(
                        json_decode($req->getContent(), true),
                        [
                            'name' => 'max:50',
                            'email' => 'required|email|unique:App\Models\User,email|max:70',
                            //'username' => 'required|unique:App\Models\User,username|max:50',
                            'password' => 'regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{6,}/',
                            //'role' => 'in:Usuario,Masajista,Admin'
                        ]
                    );
                }
            } else {

                $validator = Validator::make(
                    json_decode($req->getContent(), true),
                    [
                        'name' => 'max:50',
                        //'email' => 'required|email|unique:App\Models\User,email|max:70',
                        //'username' => 'required|unique:App\Models\User,username|max:50',
                        'password' => 'regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{6,}/',
                        //'role' => 'in:Usuario,Masajista,Admin'
                    ]
                );
            }

            if ($validator->fails()) {

                $respuesta['status'] = 0;
                $respuesta['msg'] = "El correo ingresado ya se encuentra registrado, prueba con otro.";
                //$respuesta['msg'] = $validator->errors();
            } else {

                //Almacenar la nueva informacion del usuario
                if (isset($data->name)  && $data->name) {
                    $requestedUser->name = $data->name;
                }
                if (isset($data->email)  && $data->email) {
                    $requestedUser->email = $data->email;
                }
                if (isset($data->password)  && $data->password) {
                    $requestedUser->password = Hash::make($data->password);
                }
                if (isset($data->lat)  && $data->lat) {
                    $requestedUser->lat = $data->lat;
                }
                if (isset($data->long)  && $data->long) {
                    $requestedUser->long = $data->long;
                }
                if (isset($data->address)  && $data->address) {
                    $requestedUser->address = $data->address;
                }
                if (isset($data->image) && $data->image) {

                    if (Storage::exists($requestedUser->username . '_photo')) {
                        //BORRAMOS LA IMAGEN EXISTENTE
                        Storage::delete($requestedUser->username . '_photo');
                    }
                    Storage::put($requestedUser->username . '_photo', base64_decode($data->image));
                    $requestedUser->image = $requestedUser->username . '_photo';
                }


                $requestedUser->save();
                $respuesta['status'] = 1;
                $respuesta['msg'] = "Se han actualizado los datos del usuario.";

                return response()->json($respuesta);
            }
            //$respuesta['msg'] = "Revise los parametros e intente nuevamente";
        } catch (\Exception $e) {
            $respuesta['status'] = 0;
            $respuesta['msg'] = "Se ha producido un error: " . $e->getMessage();
        }

        $respuesta['msg'] = "Se ha producido un error: " . $e->getMessage();
        return response()->json($respuesta);
    }
}
