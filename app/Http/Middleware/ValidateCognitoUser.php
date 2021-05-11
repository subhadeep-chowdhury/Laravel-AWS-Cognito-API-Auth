<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Concerns\InteractsWithInput;
use Illuminate\Support\Facades\Auth;

class ValidateCognitoUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
		$token = $request->bearerToken();
		$isValidUser = Auth::authenticate($token);
		
		if (Auth::check() && $isValidUser) {
			return $next($request);
		} else {
			return response()->json(['error' => 'Unauthorised'], 401);
		}
    }
}
