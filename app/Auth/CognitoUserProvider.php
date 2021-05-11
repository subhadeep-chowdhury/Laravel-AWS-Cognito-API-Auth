<?php

namespace App\Auth;

use App;

use App\Models\Auth\User;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class CognitoUserProvider implements UserProvider {

	private $model;

	public function __construct(\App\Models\Auth\User $userModel)
	{
		$this->model = $userModel;
	}
	
	/**
	 * Retrieve a user by their unique identifier.
	 *
	 * @param  mixed $identifier
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
	 */

	public function retrieveById($identifier)
	{
		// TODO: Implement retrieveById() method.


		// $qry = UserPoa::where('admin_id', '=', $identifier);

		// if ($qry->count() > 0) {
		// 	$user = $qry->select('admin_id', 'username', 'first_name', 'last_name', 'email', 'password')->first();

		// 	$attributes = array(
		// 		'id' => $user->admin_id,
		// 		'username' => $user->username,
		// 		'password' => $user->password,
		// 		'name' => $user->first_name . ' ' . $user->last_name,
		// 	);

		// 	return $user;
		// }
		return null;
	}

	/**
	 * Retrieve a user by by their unique identifier and "remember me" token.
	 *
	 * @param  mixed $identifier
	 * @param  string $token
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
	 */
	public function retrieveByToken($identifier, $token)
	{
		// TODO: Implement retrieveByToken() method.
		// $qry = User::where('admin_id', '=', $identifier)->where('remember_token', '=', $token);

		// if ($qry->count() > 0) {
		// 	$user = $qry->select('admin_id', 'username', 'first_name', 'last_name', 'email', 'password')->first();

		// 	$attributes = array(
		// 		'id' => $user->admin_id,
		// 		'username' => $user->username,
		// 		'password' => $user->password,
		// 		'name' => $user->first_name . ' ' . $user->last_name,
		// 	);

		// 	return $user;
		// }
		return null;
	}

	/**
	 * Update the "remember me" token for the given user in storage.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable $user
	 * @param  string $token
	 * @return void
	 */
	public function updateRememberToken(Authenticatable $user, $token)
	{
		$user->setRememberToken($token);
		$user->save();
	}

	/**
	 * Retrieve a user by the given credentials.
	 *
	 * @param  array  $credentials
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
	 */
	public function retrieveByCredentials(array $credentials)
	{
		if (empty($credentials)) {
			return;
		}

		$user = $this->model->fetchUserByCredentials($credentials);

		return $user;
	}

	/**
	 * Validate a user against the given credentials.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable $user
	 * @param  array $credentials
	 * @return bool
	 */
	public function validateCredentials(Authenticatable $user, array $credentials)
	{
		$getUser = $user->get();
		
		if(!is_null($getUser) && $this->model->validateAccessToken($getUser->username)) {

			$this->updateRememberToken($getUser, $user->getRememberToken());

			$getUser->fcm_token = $credentials['fcmToken'];
			$getUser->last_login_time = Carbon::now();
			$getUser->save();
			return true;
		}
		
		return false;
	}

	/**
	 * Retrieve a user by by their unique identifier and "remember me" token.
	 *
	 * @param  mixed $identifier
	 * @param  string $token
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
	 */
	public function userByToken(string $token)
	{
		if (empty($token)) {
			return;
		}
		
		$user = $this->model->fetchUserByToken($token);

		return $user;
	}
}