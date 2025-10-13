<?php

namespace Kainex\WiseChatPro\Services\User;

use Exception;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\User\WiseChatUserSessionDAO;
use Kainex\WiseChatPro\Model\WiseChatUser;
use Kainex\WiseChatPro\Services\WiseChatHttpRequestService;
use Kainex\WiseChatPro\WiseChatCrypt;
use Kainex\WiseChatPro\WiseChatOptions;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\Google;

/**
 * Wise Chat user external authentication service.
 */
class WiseChatExternalAuthentication {
    const FACEBOOK = 'fb';
    const FACEBOOK_API = 'v16.0';
    const TWITTER = 'tw';
    const TWITTER_AUTHORIZE_ENDPOINT = 'https://twitter.com/i/oauth2/authorize';
    const GOOGLE = 'go';

    /**
     * @var WiseChatOptions
     */
    private $options;

    /**
     * @var WiseChatUsersDAO
     */
    private $usersDAO;

    /**
     * @var WiseChatAuthentication
     */
    private $authentication;

    /**
     * @var WiseChatHttpRequestService
     */
    private $httpRequestService;

    /**
     * @var WiseChatUserSessionDAO
     */
    private $userSessionDAO;

	/**
	 * @param WiseChatOptions $options
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatHttpRequestService $httpRequestService
	 * @param WiseChatUserSessionDAO $userSessionDAO
	 */
	public function __construct(WiseChatOptions $options, WiseChatUsersDAO $usersDAO, WiseChatAuthentication $authentication, WiseChatHttpRequestService $httpRequestService, WiseChatUserSessionDAO $userSessionDAO) {
		$this->options = $options;
		$this->usersDAO = $usersDAO;
		$this->authentication = $authentication;
		$this->httpRequestService = $httpRequestService;
		$this->userSessionDAO = $userSessionDAO;
	}

	/**
     * @return string
     *
     * @throws Exception
     */
    public function getFacebookActionLoginURL() {
        $parameters = array(
            'wcExternalLoginAction' => self::FACEBOOK,
            'nonce' => wp_create_nonce($this->getFacebookActionNonceAction())
        );

        return $this->httpRequestService->getReferrerURLWithParameters($parameters);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function getTwitterActionLoginURL() {
        $parameters = array(
            'wcExternalLoginAction' => self::TWITTER,
            'nonce' => wp_create_nonce($this->getTwitterActionNonceAction())
        );

        return $this->httpRequestService->getReferrerURLWithParameters($parameters);
    }

    /**
     * @return string
     *
     * @throws Exception
     */
    public function getGoogleActionLoginURL() {
        $parameters = array(
            'wcExternalLoginAction' => self::GOOGLE,
            'nonce' => wp_create_nonce($this->getGoogleActionNonceAction())
        );

        return $this->httpRequestService->getReferrerURLWithParameters($parameters);
    }

    /**
     * Detects the authentication method in the current request, verifies its nonce value and redirects to Facebook, Twitter or Google for further processing.
     */
    public function handleRedirects() {
        // validate parameters:
        $method = $this->httpRequestService->getParam('wcExternalLoginAction');
        if ($method === null) {
            return;
        }
        $nonce = $this->httpRequestService->getParam('nonce');
        if ($method !== null && $nonce === null) {
            $this->httpRequestService->reload(array('wcExternalLoginAction', 'nonce'));
        }

        // verify nonce:
        $nonceAction = null;
        switch ($method) {
            case self::FACEBOOK:
                $nonceAction = $this->getFacebookActionNonceAction();
                break;
            case self::TWITTER:
                $nonceAction = $this->getTwitterActionNonceAction();
                break;
            case self::GOOGLE:
                $nonceAction = $this->getGoogleActionNonceAction();
                break;
            default:
                $this->httpRequestService->reload(array('wcExternalLoginAction', 'nonce'));
        }
        if (!wp_verify_nonce($nonce, $nonceAction)) {
            $this->httpRequestService->setRequestParam('authenticationError', 'Bad request');
            return;
        }

        if ($this->authentication->isAuthenticated()) {
            $this->httpRequestService->setRequestParam('authenticationError', 'Already authenticated');
            return;
        }

        $isRunning = $this->userSessionDAO->isRunning();
        try {
            require_once(dirname(dirname(dirname(__DIR__))).'/vendor/autoload.php');

            // open the session because the libraries make use of it:
            if (!$isRunning) {
                $this->userSessionDAO->start();
            }

            $redirectURL = null;
            if ($method === self::FACEBOOK && $this->options->isOptionEnabled('facebook_login_enabled', false)) {
                $redirectURL = $this->getFacebookRedirectLoginURL();
            } else if ($method === self::TWITTER && $this->options->isOptionEnabled('twitter_login_enabled', false)) {
                $redirectURL = $this->getTwitterRedirectLoginURL();
            } else if ($method === self::GOOGLE && $this->options->isOptionEnabled('google_login_enabled', false)) {
                $redirectURL = $this->getGoogleRedirectLoginURL();
            } else {
                throw new Exception('Bad request - feature disabled');
            }

            $this->httpRequestService->redirect($redirectURL);
        } catch (Exception $e) {
            $this->httpRequestService->setRequestParam('authenticationError', $e->getMessage().' Please try again.');
        } finally {
            if (!$isRunning) {
                $this->userSessionDAO->close();
            }
        }
    }

    /*
     * Detects authentication response from Facebook, Twitter or Google and authenticates the user in Wise Chat Pro.
     */
    public function handleAuthentication() {
        $method = $this->httpRequestService->getParam('wcExternalLogin');
        if ($method === null) {
            return;
        }

        $isRunning = $this->userSessionDAO->isRunning();
        try {
            require_once(dirname(dirname(dirname(__DIR__))).'/vendor/autoload.php');

            // open the session because the libraries make use of it:
            if (!$isRunning) {
                $this->userSessionDAO->start();
            }

            $this->httpRequestService->redirect($this->authenticate($method));
        } catch (Exception $e) {
            $this->httpRequestService->setRequestParam('authenticationError', $e->getMessage().' Please try again.');
        } finally {
            if (!$isRunning) {
                $this->userSessionDAO->close();
            }
        }
    }

    /**
     * Returns URL to external authentication through Facebook.
     *
     * @return string
     *
     * @throws Exception
     */
    public function getFacebookRedirectLoginURL() {
        $appID = $this->options->getOption('facebook_login_app_id');
        $appSecret = $this->options->getOption('facebook_login_app_secret');
        if (!$appID || !$appSecret) {
            throw new Exception('Facebook App ID or App Secret is not defined');
        }

        $redirectUri = $this->httpRequestService->getCurrentURLWithParameter('wcExternalLogin', self::FACEBOOK, array('wcExternalLoginAction', 'nonce'));
        $this->userSessionDAO->set('wise_chat_fb_redirect_uri', $redirectUri);

        $provider = new Facebook([
			'clientId'          => $appID,
			'clientSecret'      => $appSecret,
			'redirectUri'       => $redirectUri,
			'graphApiVersion'   => self::FACEBOOK_API,
		]);

        $authUrl = $provider->getAuthorizationUrl([
	        'scope' => ['public_profile'],
	    ]);

        $this->userSessionDAO->set('wise_chat_fb_oauth2state', $provider->getState());

	    return $authUrl;
    }

    /**
     * Returns URL to external authentication through Twitter.
     *
     * @return string
     *
     * @throws Exception
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function getTwitterRedirectLoginURL() {
    	$oauth2ClientId = $this->options->getOption('twitter_login_client_id');
        $oauth2ClientSecret = $this->options->getOption('twitter_login_client_secret');
        if (!$oauth2ClientId || !$oauth2ClientSecret) {
        	throw new Exception('Twitter authentication error: OAuth 2.0 Client ID and Client Secret is not set in the configuration');
        }

        $redirectUri = $this->httpRequestService->getCurrentURLWithParameter('wcExternalLogin', self::TWITTER, array('wcExternalLoginAction', 'nonce', 'wcExternalLogin', 'code', 'state'));

		$this->userSessionDAO->set('wise_chat_tw_redirect_uri', $redirectUri);

        $state = WiseChatCrypt::encryptToString(sha1(uniqid()));
		$array = $this->generatePKCEVerifierAndChallenge();
		$code_verifier = $array[0];
		$code_challenge = $array[1];
		$this->userSessionDAO->set('wise_chat_tw_code_verifier', $code_verifier);
		$this->userSessionDAO->set('wise_chat_tw_state', $state);

		return self::TWITTER_AUTHORIZE_ENDPOINT.'?'.http_build_query(array(
			'response_type' => 'code',
			'client_id' => $oauth2ClientId,
			'redirect_uri' => $redirectUri,
			'code_challenge' => $code_challenge,
			'state' => $state,
			'code_challenge_method' => 's256',
			'scope' => "tweet.read users.read"
		));
    }

    private function base64url_encode($plainText) {
	    $base64 = base64_encode($plainText);
	    $base64 = trim($base64, "=");
	    $base64url = strtr($base64, '+/', '-_');
	    return ($base64url);
	}

	private function generatePKCEVerifierAndChallenge() {
	    $random = bin2hex(openssl_random_pseudo_bytes(128));
	    $verifier = $this->base64url_encode(pack('H*', $random));
	    $challenge = $this->base64url_encode(pack('H*', hash('sha256', $verifier)));
	    return [$verifier, $challenge];
	}

    /**
     * Returns URL to external authentication through Google.
     *
     * @return string
     * @throws Exception
     */
    public function getGoogleRedirectLoginURL() {
        $clientId = $this->options->getOption('google_login_client_id');
        $clientSecret = $this->options->getOption('google_login_client_secret');
        if (!$clientId || !$clientSecret) {
            throw new Exception('Google Client ID or Client Secret is not defined');
        }

        $redirectUri = $this->httpRequestService->getCurrentURLWithParameter('wcExternalLogin', self::GOOGLE, array('wcExternalLoginAction', 'nonce'));
        $this->userSessionDAO->set('wise_chat_google_redirect_uri', $redirectUri);

        $provider = new Google(array(
		    'clientId'     => $clientId,
		    'clientSecret' => $clientSecret,
		    'redirectUri'  => $redirectUri
        ));

        $authUrl = $provider->getAuthorizationUrl();
        $this->userSessionDAO->set('wise_chat_google_state', $provider->getState());

        return $authUrl;
    }

    /**
     * Authenticates user using given method.
     *
     * @param string $method
     *
     * @return string
     * @throws Exception
     */
    public function authenticate($method) {
        switch ($method) {
            case self::FACEBOOK:
                return $this->facebookAuthenticate();
                break;
            case self::TWITTER:
                return $this->twitterAuthenticate();
                break;
            case self::GOOGLE:
                return $this->googleAuthenticate();
                break;
            default:
                throw new Exception('Unknown method in action');
        }
    }

    private function facebookAuthenticate() {
    	$appID = $this->options->getOption('facebook_login_app_id');
        $appSecret = $this->options->getOption('facebook_login_app_secret');
        if (!$appID || !$appSecret) {
            throw new Exception('Facebook authentication error: app ID or app secret is not defined');
        }

        $provider = new Facebook([
			'clientId'          => $appID,
			'clientSecret'      => $appSecret,
			'redirectUri'       => $this->userSessionDAO->get('wise_chat_fb_redirect_uri'),
			'graphApiVersion'   => self::FACEBOOK_API,
		]);

        $code = $this->httpRequestService->getParam('code');
        if (!$code) {
        	throw new \Exception('Facebook authentication error: no code was passed in URL');
        }

		$token = $provider->getAccessToken('authorization_code', [ 'code' => $code ]);

		try {
		    $fbUser = $provider->getResourceOwner($token);

		    // authenticate user:
	        $user = $this->usersDAO->getByExternalTypeAndId(self::FACEBOOK, $fbUser->getId());
	        if ($user === null) {
	            $user = new WiseChatUser();
	            $user->setName($fbUser->getName());
	            $user->setExternalType(self::FACEBOOK);
	            $user->setExternalId($fbUser->getId());
	            $user->setAvatarUrl($fbUser->getPictureUrl());
	            $user->setProfileUrl($fbUser->getLink());
	            $user->setDataProperty('fb_auth_data', $fbUser->toArray());
	        } else {
	            $user->setName($fbUser->getName());
	            $user->setAvatarUrl($fbUser->getPictureUrl());
	            $user->setProfileUrl($fbUser->getLink());
	            $user->setDataProperty('fb_auth_data', $fbUser->toArray());
	        }
	        $this->authentication->authenticateWithUser($user);

	        /**
	         * Fires once user has started its session in the chat.
	         *
	         * @since 2.3.2
	         *
	         * @param WiseChatUser $user The user object
	         */
	        do_action("wc_user_session_started", $user);

	        return $this->httpRequestService->getCurrentURLWithoutParameters(array('wcExternalLogin', 'code', 'state'));
		} catch (\Exception $e) {
			throw new Exception('Facebook authentication error: '.$e->getMessage());
		}
    }

    private function twitterAuthenticate() {
        $oauth2ClientId = $this->options->getOption('twitter_login_client_id');
        $oauth2ClientSecret = $this->options->getOption('twitter_login_client_secret');
		$code = $this->httpRequestService->getParam('code');

        $connection = new Abraham\TwitterOAuth\TwitterOAuth($oauth2ClientId, $oauth2ClientSecret);
        $connection->setApiVersion('2');

        $token = $connection->oauth2('2/oauth2/token', array(
        	    "grant_type" => "authorization_code",
		        "code" => $code,
		        "client_id" => $oauth2ClientId,
                "redirect_uri" => $this->userSessionDAO->get('wise_chat_tw_redirect_uri'),
		        "code_verifier" => $this->userSessionDAO->get('wise_chat_tw_code_verifier')
	        )
        );

        if (!$token) {
        	throw new Exception('Twitter authentication error: empty token object');
        } else if (property_exists($token, 'errors') && is_array($token->errors)) {
        	throw new Exception('Twitter authentication error: '.$token->errors[0]->message.' (error code: '.$token->errors[0]->code.')');
        } else if (property_exists($token, 'error') && $token->error) {
        	throw new Exception('Twitter authentication error: '.$token->error.', '.$token->error_description);
        }

		$connection = new Abraham\TwitterOAuth\TwitterOAuth($oauth2ClientId, $oauth2ClientSecret, null, $token->access_token);
        $connection->setApiVersion('2');

        $content = $connection->get('users/me', array('user.fields' => 'id,name,profile_image_url'));
        if (!$content) {
        	throw new Exception('Twitter authentication error: empty user response');
        } else if (property_exists($content, 'errors') && is_array($content->errors)) {
        	throw new Exception('Twitter authentication error: '.$content->errors[0]->message.' (error code: '.$content->errors[0]->code.')');
        }

        if (property_exists($content, 'data')) {
        	$twitterUser = $content->data;
            $user = $this->usersDAO->getByExternalTypeAndId(self::TWITTER, $twitterUser->id);
            if ($user === null) {
                $user = new WiseChatUser();
                $user->setName($twitterUser->name);
                $user->setExternalType(self::TWITTER);
                $user->setExternalId($twitterUser->id);
                $user->setAvatarUrl($twitterUser->profile_image_url);
                $user->setProfileUrl('https://twitter.com/'.$twitterUser->username);
            } else {
                $user->setName($twitterUser->name);
                $user->setAvatarUrl($twitterUser->profile_image_url);
                $user->setProfileUrl('https://twitter.com/'.$twitterUser->username);
            }
            $this->authentication->authenticateWithUser($user);

            /**
             * Fires once user has started its session in the chat.
             *
             * @since 2.3.2
             *
             * @param WiseChatUser $user The user object
             */
            do_action("wc_user_session_started", $user);
        } else {
			if (property_exists($content, 'status')) {
				throw new Exception('Twitter authentication error: could not load user profile, '.$content->status.', '.$content->detail);
			} else {
				throw new Exception('Twitter authentication error: could not load user profile');
	        }
        }

        return $this->httpRequestService->getCurrentURLWithoutParameters(array('wcExternalLogin', 'code', 'state'));
    }

    private function googleAuthenticate() {
        $clientId = $this->options->getOption('google_login_client_id');
        $clientSecret = $this->options->getOption('google_login_client_secret');

        $redirectUri = $this->userSessionDAO->get('wise_chat_google_redirect_uri');
        $sessionState = $this->userSessionDAO->get('wise_chat_google_state');

        $provider = new Google(array(
			'clientId'     => $clientId,
			'clientSecret' => $clientSecret,
			'redirectUri'  => $redirectUri
		));

        $state = $this->httpRequestService->getParam('state');
        if (empty($state) || ($state !== $sessionState)) {
	        $this->userSessionDAO->set('wise_chat_google_state', '');
	        throw new \Exception('Invalid state');
        }

		$token = $provider->getAccessToken('authorization_code', array('code' => $this->httpRequestService->getParam('code')));

		try {
			$ownerDetails = $provider->getResourceOwner($token);

			// authenticate user:
			if ($ownerDetails) {
				$user = $this->usersDAO->getByExternalTypeAndId(self::GOOGLE, $ownerDetails->getId());
				if ($user === null) {
					$user = new WiseChatUser();
					$user->setName($ownerDetails->getName());
					$user->setExternalType(self::GOOGLE);
					$user->setExternalId($ownerDetails->getId());
					$user->setAvatarUrl($ownerDetails->getAvatar());
				} else {
					$user->setName($ownerDetails->getName());
					$user->setAvatarUrl($ownerDetails->getAvatar());
				}
				$this->authentication->authenticateWithUser($user);

				/**
				 * Fires once user has started its session in the chat.
				 *
				 * @param WiseChatUser $user The user object
				 * @since 2.3.2
				 *
				 */
				do_action("wc_user_session_started", $user);
			} else {
				throw new \Exception('Could not load user profile based on the token');
			}
		} catch (Exception $e) {
			throw new Exception('Google authentication error: '.$e->getMessage());
		}

		return $this->httpRequestService->getCurrentURLWithoutParameters(array('wcExternalLogin', 'code', 'scope', 'state', 'authuser', 'prompt'));
    }

    private function getFacebookActionNonceAction() {
        return self::FACEBOOK.$this->options->getOption('facebook_login_app_secret').$this->httpRequestService->getRemoteAddress();
    }

    private function getTwitterActionNonceAction() {
        return self::TWITTER.$this->options->getOption('twitter_login_api_secret').$this->httpRequestService->getRemoteAddress();
    }

    private function getGoogleActionNonceAction() {
        return self::GOOGLE.$this->options->getOption('google_login_client_secret').$this->httpRequestService->getRemoteAddress();
    }
}