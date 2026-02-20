<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController
{

    public function login(Request $request)
    {

        $result = new \stdClass();

        $user = User::where('user', $request->user)->first();

        if ($user) {
            if (Hash::check($request->pass, $user->pass)) {

                $update = User::find($user->id_user);
                $update->token = $request->token;
                $update->save();

                $result->code = 200;
                $result->user = $user;
                $result->text = "Correcto";

            } else {

                $result->code = 400;
                $result->text = "Credenciales inválidas";

            }
        } else {

            $result->code = 400;
            $result->text = "Credenciales inválidas";

        }

        return response()->json($result);

    }

    public function hash() 
    {

        $result = new \stdClass();

        $result->hash = Hash::make('123456');

        return response()->json($result);

    }

    public function get_users()
    {

        $users = User::with('webs')->get();
        return response()->json($users);

    }

    public function edit_user_webs(Request $request, $id)
    {

        $user = User::findOrFail($id);

        $user->webs()->sync($request->webs);

        return response()->json(['success' => true]);

    }

    public function change(Request $request)
    {

        $user = User::find($request->id_user);
        $user->pass = Hash::make($request->pass);
        $user->save();

        return response()->json(['success' => true]);

    }

    public function create(Request $request)
    {

        $user = new User();
        $user->name = $request->form['name'];
        $user->lastname = $request->form['lastname'];
        $user->user = $request->form['user'];
        $user->level = 2;
        $user->pass = Hash::make('123456');
        $user->save();

        if ($request->webs) {
            $user->webs()->sync($request->webs);
        }

        return response()->json(['success' => true]);

    }

    public function delete(Request $request)
    {

        $user = User::findOrFail($request->id_user);

        $user->delete();

        return response()->json(['success' => true]);

    }

    public function test_receipt()
    {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://exp.host/--/api/v2/push/getReceipts");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            "ids" => ["019c3394-964d-76c3-b656-1e4d0d71b897"]
        ]));

        $response = curl_exec($ch);
        curl_close($ch);

        echo $response;

    }

    public function test_token()
    {

        $user = User::find(1);

        echo $user->token;

        $data = [
            "to" => $user->token,
            "sound" => "alerta",
            "title" => "🚨 Test de notificación",
            "body" => "Las notificaciones fueron configuradas exitosamente",
            "priority" => "high",
            "channelId" => "alerta_sirenap",
            "data" => [
                "type" => "sismo"
            ]
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://exp.host/--/api/v2/push/send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        echo $response;

    }

    public function pushToken($id_user, $title, $text, $type = 1)
    {

        $user = User::find($id_user);

        $data = [
            "to" => $user->token,
            "sound" => $type == 1 ? "alerta" : "default",
            "title" => "🚨 ".$title,
            "body" => $text,
            "priority" => "high",
            "channelId" => $type == 1 ? "alerta_sirenap" : "default",
            "data" => [
                "type" => "sismo"
            ]
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://exp.host/--/api/v2/push/send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        curl_close($ch);

        echo $response;
    }

    public function sendAlerts($title, $text, $type = 1)
    {

        $users = User::where('alerts',1)->get();

        if ($users) {

            foreach ($users as $user) {

                $data = [
                    "to" => $user->token,
                    "sound" => $type == 1 ? "alerta" : "default",
                    "title" => "🚨 ".$title,
                    "body" => $text,
                    "priority" => "high",
                    "channelId" => $type == 1 ? "alerta_sirenap" : "default",
                    "data" => [
                        "type" => "sismo"
                    ]
                ];

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "https://exp.host/--/api/v2/push/send");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json",
                    "Accept: application/json"
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

                $response = curl_exec($ch);
                curl_close($ch);

                echo $response;

            }

        }

    }

    public function sendNotifications($title, $text)
    {

        $users = User::where('notifications',1)->get();

        if ($users) {

            foreach ($users as $user) {

                $data = [
                    "to" => $user->token,
                    "sound" => "default",
                    "title" => $title,
                    "body" => $text,
                    "priority" => "high",
                    "channelId" => "default",
                    "data" => [
                        "type" => "sismo"
                    ]
                ];

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "https://exp.host/--/api/v2/push/send");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Content-Type: application/json",
                    "Accept: application/json"
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

                $response = curl_exec($ch);
                curl_close($ch);

                echo $response;

            }

        }

    }

}