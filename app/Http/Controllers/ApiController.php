<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;


class ApiController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
            'role' => 'required',
        ]);

        $data = new User;
        $data->name = $request->name;
        $data->email = $request->email;
        $data->password = Hash::make($request->password);
        $data->role = $request->role;
        $data->save();

        return response()->json([
            'status' => true,
            'message' => 'User created sucessfully',
        ]);
    }

    public function login(Request $request)
    {

        // data validation
        $request->validate([
            "email" => "required|email",
            "password" => "required"
        ]);

        // JWTAuth
        $token = JWTAuth::attempt([
            "email" => $request->email,
            "password" => $request->password
        ]);

        if (!empty($token)) {
            $user_details = Auth::user();

            if($user_details->role === "mentor" || $user_details->role === "student") {
                return response()->json([
                    "status" => false,
                    "message" => "Unauthorized Access."
                ], 401);
            }

            $user_details->block = $user_details->block;
            return response()->json([
                "status" => true,
                "message" => "User logged in succcessfully",
                "token" => $token,
                "user" => $user_details,
            ], 200);
        }

        return response()->json([
            "status" => false,
            "message" => "Invalid details"
        ], 401);
    }

    public function profile()
    {

        $userdata = auth()->user();
        $userdata->block = $userdata->block;

        return response()->json([
            "status" => true,
            "message" => "Profile data",
            "data" => $userdata
        ]);
    }

    public function refreshToken()
    {

        // $newToken = auth()->refresh();
        $newToken = JWTAuth::parseToken()->refresh();

        return response()->json([
            "status" => true,
            "message" => "New access token",
            "token" => $newToken
        ]);
    }

    public function logout()
    {

        auth()->logout();

        return response()->json([
            "status" => true,
            "message" => "User logged out successfully"
        ]);
    }
}
