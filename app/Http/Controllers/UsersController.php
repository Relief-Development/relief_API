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

        $user = User::where('email', '=', $data->email)->first();
        $token = $user->api_token;

        if ($user) {
            if (Hash::check($data->password, $user->password)) {
                //if ($user->api_token != null) {
                // if(!isset($user->api_token)) {   
                do {
                    $token = Hash::make($user->id . now());
                } while (User::where('api_token', $token)->first());
                $user->api_token = $token;
                $user->save();
                //}

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
                //$user->phone_number = $data->phone_number;

                //agregar foto decodificada si la imagen y el tipo son enviados
                //if (isset($data->image) && $data->image && isset($data->imageType) &&  $data->imageType) {
                // Storage::put($user->username . '_photo.' . $data->imageType, base64_decode($data->image));
                // $user->image = $user->username . '_photo.' . $data->imageType;
                //}

                if (isset($data->phone_number) && $data->phone_number) {
                    $user->phone_number = $data->phone_number;
                }

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

    public function search(Request $req) //Ordenar por rating
    {
        $response = ['status' => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $search = $data->search;

        try {
            if (isset($search) && $search) {
                $services = Service::join('massages', 'massages.id', '=', 'services.massage_id')
                    ->join('users', 'users.id', '=', 'services.user_id')
                    ->where('massages.name', 'like', '%' . $search . '%')
                    ->orWhere('users.name', 'like', '%' . $search . '%')
                    ->select('users.name')
                    ->orderBy('users.name', 'ASC')
                    ->get();
                $response['status'] = 1;
                $response['msg'] = "Listado de masajistas:";
                $response['services'] = $services;
            } else {
                $response['status'] = 6;
                $response['msg'] = "Parámetro necesario no recibido";
            }
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }
        return response()->json($response);
    }

    public function listMassages(Request $req) //No usada
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

        $search = $data->search;

        try {
            if (isset($search) && $search) {
                $profile = User::where('users.role', '=', 'Masajista')
                    ->where('users.name', 'like', '%' . $search . '%')
                    ->select('users.name', 'users.lat', 'users.long')
                    ->get();
                $response["status"] = 1;
                $response['msg'] = "Masajistas encontrados";
                $response['profile'] = $profile;
            } else {
                $response['status'] = 6;
                $response['msg'] = "Parámetro necesario no recibido";
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

    public function seeProfile(Request $req) //No usada
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

    function editProfile(Request $req) //Ver lo de los servicios con el estado
    {
        $response = ["status" => 1, "msg" => ""];

        $data = $req->getContent();
        $data = json_decode($data);

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

                //SE GUARDAN LOS DATOS DEL USUARIO
                $requestedUser->save();

                //SE VALIDARAN LOS SERVICIOS AGRAGADOS Y EN CASO DE EXOSTIR SE AGREGARAN A LA TARBLA SE SERVICES
                $validId = [];

                //OPCION DE ENVIAR UN ARRAY CON ID
                // foreach ($data->services as $addService) {
                //     if (isset($addService->id)) {
                //         // $i++;
                //         $service = Massage::where('id', '=', $addService->id)->first();
                //         if ($service) {
                //             //$j++;
                //             array_push($validId, $service->id);
                //         }
                //     }
                // }

                //OPCION DE ENVIAR UN ARRAY CON NUMEROS
                foreach ($data->services as $addService) {
                   // print ($addService);
                    $service = Massage::where('id', $addService)->first();
                    //print ($service);
                    if ($service) {
                        array_push($validId, $service->id);
                    }
                }


                //print_r ($validId);
                if (!empty($validId)) {
                    Service::where('user_id', $requestedUser->id)->delete();

                    foreach ($validId as $id) {
                        $service = new Service();
                        $service->user_id = $requestedUser->id;
                        $service->massage_id = $id;
                        $service->save();
                    }
                    $respuesta['msg'] = 'Se han agregado los servicios';
                }

                $response['status'] = 1;
                $response['msg'] = "Se han actualizado los datos del usuario.";
            }
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }

        return response()->json($response);
    }

    function getTherapistForMassage(Request $req)
    {
        $response = ["status" => 1, "msg" => ""];

        $data = $req->getContent();
        $data = json_decode($data);

        $massageId = $data->id;

        try {
            if (isset($massageId)  && $massageId) {

                $therapistList = DB::table('users')
                    ->join('services', 'user_id', '=', 'users.id')
                    ->join('massages', 'massages.id', '=', 'services.massage_id')
                    ->leftJoin('ratings', 'therapist_id', '=', 'users.id')
                    ->where('massages.id', '=', $massageId)
                    ->select(
                        DB::raw("AVG(rating) AS media"),
                        "users.id as id",
                        "users.name as name",
                        "users.description as description",
                        "users.lat as lat",
                        "users.long as long",
                        "users.image as image",
                        "users.phone_number as phone_number"
                    )
                    ->groupBy('users.id', 'users.name', 'users.description', 'users.lat', 'users.long', 'users.image', 'users.phone_number')
                    ->orderBy('media', 'DESC')
                    ->get();

                foreach ($therapistList as $therapist) {
                    $image64 = base64_encode(Storage::get($therapist->image));
                    // if ($image64 != "" && Storage::exists($image64)) {
                    //     $therapist->image = $image64;
                    // }
                    $therapist->image = $image64;
                }
                //dd($therapistList);

                $response['status'] = 1;
                $response['msg'] = "Listado de masajistas:";
                $response['list'] = $therapistList;
            } else {
                $response['status'] = 6;
                $response['msg'] = "Parámetro necesario no recibido";
            }
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: " . $e->getMessage();
        }

        return response()->json($response);
    }

    //FUNCIÓN PARA BUSCAR LOS SERVICIOS PRESTADOS POR UN MASAJISTA
    public function getServices(Request $req) 
    {
        $response = ["status" => 1, "msg" => ""];
        $data = $req->getContent();
        $data = json_decode($data);

        $user = User::where('api_token', $data->api_token)->first();
        $therapist = User::with('massages')->where('id', '=', $data->id)->first();

        try {
            if ($therapist) {
                $response["status"] = 1;
                $response["msg"] = "Listado de servicios";
                $response['list'] = $therapist->massages;
            } else {
                $response["status"] = 2;
                $response['msg'] = "Masajista no encontrado";
            }
        } catch (\Exception $e) {
            $response["status"] = 0;
            $response["msg"] = "Se ha producido un error" . $e->getMessage();
        }
        return response()->json($response);
    }
}
