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

}