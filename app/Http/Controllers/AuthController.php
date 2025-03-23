<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Token;

class AuthController extends Controller
{


    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        $token = $user->createToken('API Token')->accessToken;

        return response()->json(['token' => $token], 200);
    }

    public function user(Request $request)
    {
        switch ($request->user()->role->name) {
            case 'SuperAdmin':
                return response()->json(User::all());
                break;
            case 'Admin':
                return response()->json(User::where('created_by',$request->user()->id)->orwhere('id',$request->user()->id)->get());
                break;
            default:
                return response()->json($request->user());
                break;
        }
        
    }

    public function logout(Request $request)
    {
        $request->user()->token()->delete();
        return response()->json(['message' => 'Logout successfully']);
    }
}

