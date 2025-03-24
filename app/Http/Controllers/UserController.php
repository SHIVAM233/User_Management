<?php

namespace App\Http\Controllers;

use App\Jobs\StoreDataJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'data.*.name' => 'required|string',
            'data.*.email' => 'required|email|unique:users',
            'data.*.password' => 'required',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }
        $data = $request->only('email', 'password','name');
        $data['password']=Hash::make($data['password']);
        switch ($request->user()->role->name) {
            case 'SuperAdmin':
                $data['role_id']=2;
                break;
            case 'Admin':
                $data['role_id']=3;
                break;
            
            default:
                return response()->json(['message' => 'permission denied!'], 403);
                break;
        }
        $data['created_by']=$request->user()->id;
        StoreDataJob::dispatch($data)->delay(now()->addSecond(1));

        return response()->json(['message' => 'Resource created successfully!'], 200);
    }

    public function update(Request $request, $id)
    {
        switch ($request->user()->role->name) {
            case 'SuperAdmin':
                break;
            case 'Admin':
                break;
            default:
                return response()->json(['message' => 'permission denied!'], 403);
                break;
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()], 422);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->update([
            'name' => $request->input('name', $user->name),
            'email' => $request->input('email', $user->email),
            'password' => $request->has('password') ? Hash::make($request->password) : $user->password,
        ]);

        return response()->json(['message' => 'User updated successfully!', 'user' => $user], 200);
    }

    public function softDelete($id)
    {
        switch ($request->user()->role->name) {
            case 'SuperAdmin':
                break;
            case 'Admin':
                break;
            default:
                return response()->json(['message' => 'permission denied!'], 403);
                break;
        }
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User soft deleted successfully!'], 200);
    }
}
