<?php

namespace App\Http\Controllers;

use App\Mail\ForgotPasswordMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

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

            if ($user_details->role === "mentor" || $user_details->role === "student") {
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

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where("email", $request->email)->first();

        if (!$user) {
            return response()->json(['message' => "User not found"]);
        }

        $token = Str::random(60);

        DB::table('password_resets')->where("email", $request->email)->delete();

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        Mail::to($user->email)->send(new ForgotPasswordMail($token));

        return response()->json(['message' => 'Password reset link sent to your email']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            "token" => 'required',
            "password" => 'required',
            "confirmPassword" => 'required'
        ]);

        if ($request->password !== $request->confirmPassword) {
            return response()->json(['message' => 'Passwords do not match']);
        }

        $tokenData = DB::table('password_resets')->where('token', $request->token)->first();

        if (!$tokenData) {
            return response()->json(['message' => 'Invalid token']);
        }

        $user = User::where("email", $tokenData->email)->first();

        if (!$user) {
            return response()->json(['message' => "User not found"]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_resets')->where("email", $tokenData->email)->delete();

        return response()->json(['message' => "Password reset successfully"]);
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
