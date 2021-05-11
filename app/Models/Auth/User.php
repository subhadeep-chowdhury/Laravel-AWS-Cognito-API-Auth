<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use App\Auth\AmazonCognito;

class User extends Model implements AuthenticatableContract
{
	
	private $user;
	private $_username;
	private $_password;
	
	protected $rememberTokenName = 'remember_token';

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'users';

	/**
	 * The primary key associated with the table.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = [
		'password',
		'remember_token',
	];
	
	public function __construct()
	{
	}
	
	/**
	* Fetch user by Credentials
	*
	* @param array $credentials
	* @return Illuminate\Contracts\Auth\Authenticatable
	*/
	public function fetchUserByCredentials(Array $credentials)
	{
		try {
			$getUser = AmazonCognito::AdminGetUser($credentials['username']);
			
			$status = $getUser->get('UserStatus');

			if ($status == 'NEW_PASSWORD_REQUIRED' && $status == 'FORCE_CHANGE_PASSWORD') return;

			return $this->adminInitiateAuth($credentials['username'], $credentials['password']);

		} catch (\Exception $e) {
			return $this;
		}

	}
	
	/**
	* {@inheritDoc}
	* @see \Illuminate\Contracts\Auth\Authenticatable::getAuthIdentifierName()
	*/
	public function getAuthIdentifierName()
	{
		return "_username";
	}
	
	/**
	* {@inheritDoc}
	* @see \Illuminate\Contracts\Auth\Authenticatable::getAuthIdentifier()
	*/
	public function getAuthIdentifier()
	{
		return $this->{$this->getAuthIdentifierName()};
	}
	
	/**
	* {@inheritDoc}
	* @see \Illuminate\Contracts\Auth\Authenticatable::getAuthPassword()
	*/
	public function getAuthPassword()
	{
		return $this->_password;
	}
	
	/**
	* {@inheritDoc}
	* @see \Illuminate\Contracts\Auth\Authenticatable::getRememberToken()
	*/
	public function getRememberToken()
	{
		if (! empty($this->getRememberTokenName())) {
			return $this->{$this->getRememberTokenName()};
		}
	}
	
	/**
	* {@inheritDoc}
	* @see \Illuminate\Contracts\Auth\Authenticatable::setRememberToken()
	*/
	public function setRememberToken($value)
	{
		if (! empty($this->getRememberTokenName())) {
			$this->{$this->getRememberTokenName()} = $value;
		}
	}
	
	/**
	* {@inheritDoc}
	* @see \Illuminate\Contracts\Auth\Authenticatable::getRememberTokenName()
	*/
	public function getRememberTokenName()
	{
		return $this->rememberTokenName;
	}

	public function get()
	{
		return $this->user;
	}

	private function adminInitiateAuth(string $username, string $password)
	{
		try {
			$result = AmazonCognito::AdminInitiateAuth($username, $password);
			
			// get token from login, set in session or return issue
			if ($result->get('AuthenticationResult')) {
				$authenticationResult = $result->get('AuthenticationResult');

				$this->setRememberToken($authenticationResult['AccessToken']);
				// $this->session->set_userdata('refresh_token', $authenticationResult['RefreshToken']);

				// Get User details
				$getUser = $this->where('username', $username);
				if($getUser->count() == 0) return;

				$this->user = $getUser->first();

				$this->_username = $this->user->username;
				$this->_password = $this->user->password;
			}

			return $this;
			
		} catch (\Exception $e) {
			return $this;
		}

	}

	public function validateAccessToken(string $username)
	{
		return AmazonCognito::ValidateAccessToken($username, $this->getRememberToken());
	}

	public function fetchUserByToken(string $token)
	{
		$getUser = $this->where('remember_token', $token);
		if ($getUser->count() == 0) return;

		$this->user = $getUser->first();
		
		return $this;
	}
}