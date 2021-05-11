<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

use App\Auth\CognitoUserProvider;
use App\Auth\CognitoUserGuard;

class AwsAuthProvider extends ServiceProvider
{
	/**
	 * Bootstrap the application services.
	 *
	 * @return CognitoUserProvider
	 */
	public function boot()
	{
		// add custom guard provider
		Auth::provider('aws-cognito-user-pool', function ($app, array $config) {
			return new CognitoUserProvider($app->make('App\Models\Auth\User'));
		});

		// add custom guard
		Auth::extend('aws-cognito', function ($app, $name, array $config) {
			return new CognitoUserGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
		});
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}
}
