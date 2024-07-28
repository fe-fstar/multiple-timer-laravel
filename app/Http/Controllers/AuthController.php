<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Register a user
    public function register() {
        $validator = Validator::make(request()->all(), [
            'username' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8|max:32',
        ], [
            'username.required' => 'Username is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already taken.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Passwords do not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 32 characters.',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
  
        $user = new User;
        $user->username = request()->username;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();

        // Generate a token using the user ID
        $token = auth("api")->login($user);

        return $this->respondWithToken($token);
    }

    // Get a JWT via given credentials
    public function login()
    {
        $credentials = request(['username', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    // Get the authenticated user
    public function verify()
    {
        return response()->json(auth()->user());
    }

    // Log out the user (invalidate the token)
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'logoutSuccessful']);
    }

    // Get the token array structure.
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth("api")->factory()->getTTL() * 60 * 24 * 7 // 7 Days
        ], 200);
    }
}