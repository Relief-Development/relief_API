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

        $email = $data->email;
        $user = User::where('email', '=', $email)->first();
        $token = $user->api_token;

        if ($user) {
            if (Hash::check($data->password, $user->password)) {
                if (!isset($user->api_token)) {
                    do {
                        $token = Hash::make($user->id . now());
                    } while (User::where('api_token', $token)->first());
                    $user->api_token = $token;
                    $user->save();
                }

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
                $response['msg'] = "La contraseña no es correcta"; //La contraseña no es correcta
            }
        } else {
            $response['status'] = 2;
            $response['msg'] = "Usuario no encontrado"; //Usuario no encontrado
        }

        return response()->json($response);
    }

    public function registerUser(Request $req)
    {

        $response = ["status" => 1, "msg" => ""];

        $validator = Validator::make(json_decode($req->getContent(), true), [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:App\Models\User,email|max:70',
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
                $user->password = Hash::make($data->password);
                $user->role = $data->role;

                //agregar foto decodificada si la imagen y el tipo son enviados
                //if (isset($data->image) && $data->image && isset($data->imageType) &&  $data->imageType) {
                // Storage::put($user->username . '_photo.' . $data->imageType, base64_decode($data->image));
                // $user->image = $user->username . '_photo.' . $data->imageType;
                //}

                if (isset($data->image) && $data->image) {
                    Storage::put($user->email . '_photo', base64_decode($data->image));
                    $user->image = $user->email . '_photo';
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

        $email = $data->email;

        $user = User::where('email', $email)->first();
        try {
            if ($user) {

                $user->api_token = null;

                //Generamos nueva contraseña aleatoria
                $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz";
                $characterLength = strlen($characters);
                $newPassword = '';
                for ($i = 0; $i < 8; $i++) {
                    $newPassword .= $characters[rand(0, $characterLength - 1)];
                }
                $user->password = Hash::make($newPassword);
                $user->save();
                Mail::to($user->email)->send(new Notification($newPassword));
                $response['status'] = 1;
                $response['msg'] = "Se ha enviado su nueva contraseña. Por favor, revise su correo.";
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

    public function addRemoveFavorites(Request $req)
    {
        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $user = User::where('api_token', $data->api_token)->first();
        $therapist = Therapist::find($data->therapist_id);
        $favorite = Favorite::where('user_id', '=', $user->id)->where('therapist_id', '=', $therapist->id)->first();

        if ($favorite) { //Función para eliminar favorito

            Favorite::where('id', $favorite->id)->delete();
            $response['status'] = 1;
            $response['msg'] = "Masajista eliminado de favoritos";
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
        $data = $req->getContent();
        $data = json_decode($data);

        $user = User::where('api_token', $data->api_token)->first();

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
        $data = $req->getContent();
        $data = json_decode($data);

        try {
            if ($data->search) {
                $services = Service::join('massages', 'massages.id', '=', 'services.massage_id')
                    ->join('users', 'users.id', '=', 'services.user_id')
                    ->where('massages.name', 'like', '%' . $req->input('search') . '%')
                    ->orWhere('users.name', 'like', '%' . $req->input('search') . '%')
                    ->select('users.name')
                    ->groupBy('users.name')
                    ->orderBy('users.name', 'ASC')
                    ->get();
                $response['status'] = 1;
                $response['msg'] = "Listado de masajistas:";
                $response['services'] = $services;
            }
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }
        return response()->json($response);
    }

    public function listMassages(Request $req) //Ver
    {
        $response = ["status" => 1, "msg" => ""];

        try {
            $massages = DB::table('massages')
                ->select('massages.name')
                ->get();
            $response['status'] = 1;
            $response['msg'] = "Listado de masajes:";
            $response['massages'] = $massages;
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }
        return response()->json($response);
    }

    public function detailMassage(Request $req) 
    {
        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        try {

            $massages = Massage::select('massages.id', 'massages.name', 'massages.description', 'massages.image')
                ->get();
           
            $response['status'] = 1;
            $response['massages'] = $massages;

        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }
        return response()->json($response);
    }

    public function searchTherapistInMap(Request $req)
    {
        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        try {
            if ($data->search) {
                $profile = User::where('users.role', '=', 'Masajista')
                    ->where('users.name', 'like', '%' . $req->input('search') . '%')
                    ->select('users.name', 'users.lat', 'users.long')
                    ->get();
                $response["status"] = 1;
                $response['msg'] = "Masajistas encontrados";
                $response['profile'] = $profile;
            }
        } catch (\Exception $e) {
            $response["status"] = 0;
            $response["msg"] = "Se ha producido un error" . $e->getMessage();
        }
        return response()->json($response);
    }

    public function getTherapistInMap()
    {
        $response = ["status" => 1, "msg" => ""];

        try {
            $profile = User::where('users.role', '=', 'Masajista')
                ->select('users.name', 'users.lat', 'users.long')
                ->get();
            $response["status"] = 1;
            $response['msg'] = "Masajistas encontrados";
            $response['profile'] = $profile;
        } catch (\Exception $e) {
            $response["status"] = 0;
            $response["msg"] = "Se ha producido un error" . $e->getMessage();
        }
        return response()->json($response);
    }

    public function seeProfile(Request $req) //VER
    { 

        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $profile = User::where('api_token', $data->api_token)->first();

        if ($profile) {
            $response["status"] = 1;
            $response['msg'] = "Perfil usuario";
            $response['profile'] = $profile;
        } else {
            $response["status"] = 2;
            $response["msg"] = "Usuario no encontrado";
        }
        return response()->json($response);
    }

    function editProfile(Request $req)
    {
        $response = ["status" => 1, "msg" => ""];

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

                $response['status'] = 0;
                $response['msg'] = "El correo ingresado ya se encuentra registrado, prueba con otro.";
                //$response['msg'] = $validator->errors();
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
                if (isset($data->description)  && $data->description) {
                    $requestedUser->description = $data->description;
                }
                if (isset($data->image) && $data->image) {

                    if (Storage::exists($requestedUser->email . '_photo')) {
                        //BORRAMOS LA IMAGEN EXISTENTE
                        Storage::delete($requestedUser->email . '_photo');
                    }
                    Storage::put($requestedUser->email . '_photo', base64_decode($data->image));
                    $requestedUser->image = $requestedUser->email . '_photo';
                }


                $requestedUser->save();
                $response['status'] = 1;
                $response['msg'] = "Se han actualizado los datos del usuario.";

                return response()->json($response);
            }
            //$response['msg'] = "Revise los parametros e intente nuevamente";
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }

        $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        return response()->json($response);
    }

    function getTherapistForMassage(Request $req)
    {
        $response = ["status" => 1, "msg" => ""];

        $data = $req->getContent();
        $data = json_decode($data);

        $massageId=$data->id;
        //$requestedMasssage = Massage::where('id', $massageId)->first();

        try {
            if (isset($massageId)  && $massageId) {

                    $therapistList = Service::join('massages', 'massages.id', '=', 'services.massage_id')
                        ->join('users', 'users.id', '=', 'services.user_id')
                        ->where('massages.id', '=', $massageId)
                        ->select('users.name', 'users.description', 'users.image'/*, 'users.rating'*/)
                        //->groupBy('therapists.name')
                        ->orderBy('users.name', 'ASC')
                        ->get();
                    $response['status'] = 1;
                    $response['msg'] = "Listado de masajistas:";
                    $response['services'] = $therapistList;
            }else{
                $response['status'] = 6;
                $response['msg'] = "Parametro necesario no recibido";
            } 
            } catch (\Exception $e) {
                $response['status'] = 0;
                $response['msg'] = "Se ha producido un error: " . $e->getMessage();
            }

        return response()->json($response);
    }

}
