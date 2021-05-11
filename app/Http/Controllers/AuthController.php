<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
	/**
	 * Login
	 */
	public function login(Request $request)
	{
		$credentials = $request->only('username', 'password', 'fcmToken');

		if (Auth::validate($credentials)) {
			$token = Auth::user()->getRememberToken();
			return response()->json(['token' => $token], 200);
		} else {
			return response()->json(['error' => 'Unauthorised'], 401);
		}
	}

	public function user(Request $request)
	{
		return response()->json(Auth::user()->get());
	}

	public function logout()
	{
		if (Auth::check() && Auth::logoutUser()) {
			return response()->json(['success' => 'User logged out successfully.'], 200);
		} else {
			return response()->json(['error' => 'Unauthorised'], 401);
		}
	}
}
