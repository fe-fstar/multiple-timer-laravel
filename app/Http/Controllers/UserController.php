<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // update user
    // delete user
    // both need to be authenticated
    public function update(Request $request) {
        $user = auth()->user();

        $validator = Validator::make(request()->all(), [
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8|confirmed',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        
        if ($request->has('username')) {
            $user->username = $request->input('username');
        }
    
        if ($request->has('email')) {
            $user->email = $request->input('email');
        }
    
        if ($request->has('password')) {
            $user->password = bcrypt($request->input('password'));
        }
    
        $user->save();
    
        return response()->json(['message' => 'User updated successfully']);
    }

    public function delete(Request $request) {
        $user = auth()->user();
    
        DB::transaction(function () use ($user) {
            // Delete all steps associated with the user's plans
            foreach ($user->plans as $plan) {
                $plan->steps()->delete();
            }
    
            // Delete all plans associated with the user
            $user->plans()->delete();
    
            // Delete the user
            $user->delete();
        });
    
        return response()->json(['message' => 'User and related plans and steps deleted successfully']);
    }
}
