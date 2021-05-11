<?php

namespace App\Auth;

use Carbon\Carbon;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;

class AmazonCognito
{

	private static $client = false;
	private static $payload;
	private static $username;

	public static function AdminInitiateAuth(string $username, string $password)
	{
		self::init();

		if (!self::$client) throw new \Exception('Client is not initiated.');

		self::$username = $username;

		try {
			return self::$client->adminInitiateAuth([
				'AuthFlow' => 'ADMIN_NO_SRP_AUTH',
				'ClientId' => getenv('AWS_APP_CLIENT_ID'),
				'UserPoolId' => getenv('AWS_COGNITO_POOL_ID'),
				'AuthParameters' => [
					'USERNAME' => $username,
					'PASSWORD' => $password,
				]
			]);
		} catch (AwsException $e) {
			throw new AWSCognitoException($e->getAwsErrorMessage(), $e->getAwsErrorCode());
		}
	}
	
	public static function ValidateAccessToken(string $username, string $jwt)
	{
		self::InterpretToken($jwt);

		if(empty(self::$payload->exp)) return false;

		$expiration = Carbon::createFromTimestamp(self::$payload->exp);
		$tokenExpired = (Carbon::now()->diffInSeconds($expiration, false) < 0);

		if ($tokenExpired) {
			return false;
		}

		if (strtolower($username) !== self::$payload->username) {
			return false;
		}

		return true;
	}

	public static function AdminGetUser(string $username)
	{
		self::init();

		if (!self::$client) throw new \Exception('Client is not initiated.');

		try {
			return self::$client->adminGetUser([
				'UserPoolId' => getenv('AWS_COGNITO_POOL_ID'),
				'Username' => $username
			]);
		} catch (AwsException $e) {
			throw new AWSCognitoException($e->getAwsErrorMessage(), $e->getAwsErrorCode());
		}
	}

	public function AdminUserGlobalSignOut(string $username)
	{
		if (!$this->client) throw new \Exception('Client is not initiated.');

		try {
			return $this->client->adminUserGlobalSignOut([
				'UserPoolId' => getenv('AWS_COGNITO_POOL_ID'),
				'Username' => $username
			]);
		} catch (AwsException $e) {
			throw new AWSCognitoException($e->getAwsErrorMessage(), $e->getAwsErrorCode());
		}
	}

	public function AdminRespondToAuthChallenge(string $challengeName, array $challengeResponses, string $cogSession)
	{
		if (!$this->client) throw new \Exception('Client is not initiated.');

		try {
			return  $this->client->adminRespondToAuthChallenge([
				"ChallengeName" => $challengeName,
				"ChallengeResponses" => $challengeResponses,
				// "ChallengeName" => "NEW_PASSWORD_REQUIRED",
				// "ChallengeResponses" => [
				// 	"USERNAME" => $userIdForSrp,
				// 	"NEW_PASSWORD" => $newPassword,
				// ],
				"ClientId" => getenv('AWS_APP_CLIENT_ID'),
				"Session" => $cogSession,
				"UserPoolId" => getenv('AWS_COGNITO_POOL_ID')
			]);
		} catch (AwsException $e) {
			throw new AWSCognitoException($e->getAwsErrorMessage(), $e->getAwsErrorCode());
		}
	}

	public function ForgotPassword(string $username)
	{
		if (!$this->client) throw new \Exception('Client is not initiated.');

		try {
			return  $this->client->forgotPassword([
				'ClientId' => getenv('AWS_APP_CLIENT_ID'), // REQUIRED
				'Username' => $username, // REQUIRED
			]);
		} catch (AwsException $e) {
			throw new AWSCognitoException($e->getAwsErrorMessage(), $e->getAwsErrorCode());
		}
	}

	public function ConfirmForgotPassword(string $username, string $confirmationCode, string $password)
	{
		if (!$this->client) throw new \Exception('Client is not initiated.');

		try {
			return  $this->client->confirmForgotPassword([
				'ClientId' => getenv('AWS_APP_CLIENT_ID'), // REQUIRED
				'Username' => $username, // REQUIRED
				'ConfirmationCode' => $confirmationCode, // REQUIRED
				'Password' => $password, // REQUIRED
			]);
		} catch (AwsException $e) {
			throw new AWSCognitoException($e->getAwsErrorMessage(), $e->getAwsErrorCode());
		}
	}

	public function ChangePassword(string $accessToken, string $oldPassword, string $newPassword)
	{
		if (!$this->client) throw new \Exception('Client is not initiated.');

		try {
			return  $this->client->changePassword([
				'AccessToken' => $accessToken, // REQUIRED
				'PreviousPassword' => $oldPassword, // REQUIRED
				'ProposedPassword' => $newPassword, // REQUIRED
			]);
		} catch (AwsException $e) {
			throw new AWSCognitoException($e->getAwsErrorMessage(), $e->getAwsErrorCode());
		}
	}
	
	public function __get($property)
	{
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}


	public function __set($property, $value)
	{
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}

		return $this;
	}

	private static function init()
	{
		self::$client = new CognitoIdentityProviderClient([
			'profile' => getenv('AWS_PROFILE'),
			'region' => getenv('AWS_DEFAULT_REGION'),
			'version' => getenv('AWS_VERSION')
		]);
	}

	private static function InterpretToken(string $jwt)
	{
		$tokenParts = explode('.', $jwt);
		$header = base64_decode($tokenParts[0]);
		$payload = base64_decode($tokenParts[1]);
		$signatureProvided = $tokenParts[2];

		self::$payload = json_decode($payload);
	}

	/**
	* PHP has no base64UrlEncode function, so let's define one that
	* does some magic by replacing + with -, / with _ and = with ''.
	* This way we can pass the string within URLs without
	* any URL encoding.
	*/
	private function base64UrlEncode($text)
	{
		return str_replace(
			['+', '/', '='],
			['-', '_', ''],
			base64_encode($text)
		);
	}
}

class AWSCognitoException
{
	private $code;

	public function __construct($message, $code)
	{
		$this->code = $code;
		throw new \Exception($message, $this->FormatCode());
	}

	private function FormatCode()
	{
		switch ($this->code) {
			case 'ResourceNotFoundException':
				return 80396;
				break;

			case 'InvalidParameterException':
				return 80397;
				break;

			case 'TooManyRequestsException':
				return 80398;
				break;

			case 'NotAuthorizedException':
				return 80399;
				break;

			case 'UserNotFoundException':
				return 80400;
				break;

			case 'AliasExistsException':
				return 80401;
				break;

			case 'LimitExceededException':
				return 80402;
				break;

			case 'InternalErrorException':
				return 80403;
				break;

			default:
				return 0404;
				break;
		}
	}
}