<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Web;

class WebController
{

    public function edit_web_sirens(Request $request, $id) 
    {
        
        $web = Web::findOrFail($id);

        $web->sirens()->sync($request->sirens);

        return response()->json(['success' => true]);

    }

    public function create(Request $request)
    {

        $web = new Web();
        $web->name = $request->form['name'];
        $web->save();

        if ($request->sirens) {
            $web->sirens()->sync($request->sirens);
        }

        return response()->json(['success' => true]);

    }

    public function delete(Request $request)
    {

        $web = Web::findOrFail($request->id_web);

        $web->delete();

        return response()->json(['success' => true]);

    }

}