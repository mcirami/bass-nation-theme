<?php

namespace Kainex\WiseChatPro\Endpoints\Maintenance;

use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\User\WiseChatUserSettingsDAO;
use Kainex\WiseChatPro\Services\ClientSide\WiseChatClientSide;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\Services\User\WiseChatExternalAuthentication;
use Kainex\WiseChatPro\Services\WiseChatHttpRequestService;
use Kainex\WiseChatPro\Services\WiseChatService;
use Kainex\WiseChatPro\WiseChatCrypt;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Class WiseChatMaintenanceAuth
 *
 * Adds user / auth related functionalities to the maintenance endpoint.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMaintenanceAuth {

	/**
	 * @var WiseChatClientSide
	 */
	protected $clientSide;

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

	/**
	 * @var WiseChatUserSettingsDAO
	 */
	protected $userSettingsDAO;

	/**
	 * @var WiseChatHttpRequestService
	 */
	private $httpRequestService;

	/**
	 * @var WiseChatService
	 */
	protected $service;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var WiseChatExternalAuthentication
	 */
	private $externalAuthentication;

	/**
	 * @param WiseChatClientSide $clientSide
	 * @param WiseChatOptions $options
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatUserSettingsDAO $userSettingsDAO
	 * @param WiseChatHttpRequestService $httpRequestService
	 * @param WiseChatService $service
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatExternalAuthentication $externalAuthentication
	 */
	public function __construct(WiseChatClientSide $clientSide, WiseChatOptions $options, WiseChatAuthentication $authentication, WiseChatUserSettingsDAO $userSettingsDAO, WiseChatHttpRequestService $httpRequestService, WiseChatService $service, WiseChatUsersDAO $usersDAO, WiseChatExternalAuthentication $externalAuthentication) {
		$this->clientSide = $clientSide;
		$this->options = $options;
		$this->authentication = $authentication;
		$this->userSettingsDAO = $userSettingsDAO;
		$this->httpRequestService = $httpRequestService;
		$this->service = $service;
		$this->usersDAO = $usersDAO;
		$this->externalAuthentication = $externalAuthentication;
	}

	/**
	 * @return array
	 */
	public function getEvents() {
		$events = array();
		$user = null;
		$auth = $this->getAuth();
		if (!$auth) {
			$user = $this->getUser();
		}

		$events[] = array(
			'name' => 'user',
			'data' => $user
		);
		$events[] = array(
			'name' => 'auth',
			'data' => $auth
		);

		return $events;
	}

	/**
	 * @return bool
	 */
	public function needsAuth() {
		return $this->getAuth() !== null;
	}

	/**
	 * Returns all user settings (including cookie-stored settings).
	 *
	 * @return array
	 */
	public function getUser() {
		$user = $this->authentication->getUser();
		$userData = $user->getData();

		$userSettings = array(
			'enableNotifications' => !array_key_exists('disableNotifications', $userData) ? true : !$userData['disableNotifications'],
			'textColor' => array_key_exists('textColor', $userData) ? $userData['textColor'] : null,
			'allowChangeTextColor' => $this->options->isOptionEnabled('allow_change_text_color'),
			'allowControlUserNotifications' => $this->options->isOptionEnabled('allow_control_user_notifications') && $this->options->isOptionEnabled('enable_private_messages', false) && $user->getWordPressId() > 0,
			'allowMuteSound' => $this->options->isOptionEnabled('allow_mute_sound') && $this->options->getEncodedOption('sound_notification'),
			'allowChangeUserName' => $this->options->isOptionEnabled('allow_change_user_name') && !($user->getWordPressId() > 0) && !$this->authentication->isAuthenticatedExternally(),
		);

		$userSettings['allowCustomize'] = $userSettings['allowChangeTextColor'] || $userSettings['allowControlUserNotifications'] || $userSettings['allowMuteSound'] || $userSettings['allowChangeUserName'];

		return array(
			'id' => WiseChatCrypt::encryptToString($user->getId()),
			'cacheId' => $this->clientSide->getUserCacheId($user),
			'name' => $user->getName(),
			'settings' => array_merge($userSettings, $this->userSettingsDAO->getAll()),
			'rights' => $this->getUserRights()
		);
	}

	private function getAuth() {
		static $auth = false;

		if ($auth !== false) {
			return $auth;
		}

		$auth = null;
		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			$auth = $this->getAccessDeniedResponse(__('Only logged in users are allowed to enter the chat', 'wise-chat-pro'));
		}

		if ($this->service->isChatRestrictedForCurrentUserRole() || $this->service->isChatRestrictedToCurrentUser()) {
			$auth = $this->getAccessDeniedResponse(__('You are not allowed to enter the chat.', 'wise-chat-pro'));
		}

		if ($this->service->isBanned()) {
			$auth = $this->getAccessDeniedResponse(__('You are blocked from using the chat', 'wise-chat-pro'));
		}

		if (!$this->service->isChatOpen()) {
			$auth = $this->getAccessDeniedResponse(__('The chat is closed now', 'wise-chat-pro'));
		}

		if ($this->service->hasUserToBeForcedToEnterName()) {
			$auth = array(
				'mode' => 'auth-username',
				'nonce' => wp_create_nonce('un'.$this->httpRequestService->getRemoteAddress())
			);
		}

		if ($this->service->hasUserToBeAuthenticatedExternally()) {
			$auth = array(
				'mode' => 'auth-external'
			);
			if ($this->options->isOptionEnabled('anonymous_login_enabled', true)) {
				$auth['anonymous'] = array(
					'nonce' => wp_create_nonce('an'.$this->httpRequestService->getRemoteAddress())
				);
			}
			if ($this->options->isOptionEnabled('facebook_login_enabled', false)) {
				$auth['facebook'] = array(
					'url' => $this->externalAuthentication->getFacebookActionLoginURL()
				);
			}
			if ($this->options->isOptionEnabled('twitter_login_enabled', false)) {
				$auth['twitter'] = array(
					'url' => $this->externalAuthentication->getTwitterActionLoginURL()
				);
			}
			if ($this->options->isOptionEnabled('google_login_enabled', false)) {
				$auth['google'] = array(
					'url' => $this->externalAuthentication->getGoogleActionLoginURL()
				);
			}
		}

		return $auth;
	}

	private function getAccessDeniedResponse($error) {
		return array(
			'mode' => 'access-denied',
			'error' => $error
		);
	}

	private function getUserRights() {
		return array(
			'approveMessages' => $this->usersDAO->hasCurrentWpUserRight('approve_message') && $this->options->isOptionEnabled('new_messages_hidden', false),
			'deleteMessages' => $this->usersDAO->hasCurrentWpUserRight('delete_message') || $this->usersDAO->hasCurrentBpUserRight('delete_message'),
			'deleteOwnMessages' => $this->options->isOptionEnabled('enable_delete_own_messages', false),
			'editMessages' => $this->usersDAO->hasCurrentWpUserRight('edit_message') || $this->usersDAO->hasCurrentBpUserRight('edit_message'),
			'editOwnMessages' => $this->options->isOptionEnabled('enable_edit_own_messages', false),
			'muteUsers' => $this->usersDAO->hasCurrentWpUserRight('mute_user') || $this->usersDAO->hasCurrentBpUserRight('mute_user'),
			'banUsers' => $this->usersDAO->hasCurrentWpUserRight('ban_user') || $this->usersDAO->hasCurrentBpUserRight('ban_user'),
			'spamReport' => $this->options->isOptionEnabled('spam_report_enable_all', true) || $this->usersDAO->hasCurrentWpUserRight('spam_report') || $this->usersDAO->hasCurrentBpUserRight('spam_report'),
			'replyToMessages' => $this->options->isOptionEnabled('enable_reply_to_messages', true),
			'createChannels' => $this->usersDAO->hasCurrentWpUserRight('create_channels') || (!$this->usersDAO->isWpUserLogged() && $this->options->isOptionEnabled('permission_create_channels_anonymous', false)),
			'searchChannels' => $this->usersDAO->hasCurrentWpUserRight('search_channels') || (!$this->usersDAO->isWpUserLogged() && $this->options->isOptionEnabled('permission_search_channels_anonymous', false)),
		);
	}
}