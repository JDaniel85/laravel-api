<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function register(Request $request)
    {
        try {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $token = $user->createToken('app')->plainTextToken;

            return response()->json([
                'acceso' => "Ok",
                'error' => "",
                'token' => $token,
                'idUsuario' => $user->id,
                'nombreUsuario' => $user->name
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'acceso' => "",
                'token' => "",
                'error' => $e->getMessage(),
                'idUsuario' => 0,
                'nombreUsuario' => ''
            ]);
        }
    }

    public function login(Request $request)
    {
        if(Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ]))
        {
            $user = Auth::user();
            $token = $user->createToken('app')->plainTextToken;

            return response()->json([
                'acceso' => "Ok",
                'error' => "",
                'token' => $token,
                'idUsuario' => $user->id,
                'nombreUsuario' => $user->name
            ]);
        }

        return response()->json([
            'acceso' => "",
            'token' => "",
            'error' => "No existe el usuario y/o contraseña",
            'idUsuario' => 0,
            'nombreUsuario' => ''
        ]);
    }
}

