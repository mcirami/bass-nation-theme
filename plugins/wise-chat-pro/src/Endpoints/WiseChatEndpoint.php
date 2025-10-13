<?php

namespace Kainex\WiseChatPro\Endpoints;

use Exception;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\User\WiseChatUserSettingsDAO;
use Kainex\WiseChatPro\DAO\UserMutesDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelsDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatMessagesDAO;
use Kainex\WiseChatPro\Exceptions\WiseChatUnauthorizedAccessException;
use Kainex\WiseChatPro\Integrations\Twilio\WiseChatTwilioVideo;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Model\WiseChatUser;
use Kainex\WiseChatPro\Rendering\WiseChatRenderer;
use Kainex\WiseChatPro\Services\Channels\Listing\WiseChatChannelsSourcesService;
use Kainex\WiseChatPro\Services\ClientSide\WiseChatClientSide;
use Kainex\WiseChatPro\Services\Message\WiseChatMessageReactionsService;
use Kainex\WiseChatPro\Services\User\UserFeedService;
use Kainex\WiseChatPro\Services\User\WiseChatActions;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\Services\User\WiseChatAuthorization;
use Kainex\WiseChatPro\Services\User\WiseChatUserEvents;
use Kainex\WiseChatPro\Services\User\WiseChatUserService;
use Kainex\WiseChatPro\Services\UserMutesService;
use Kainex\WiseChatPro\Services\WiseChatChannelsService;
use Kainex\WiseChatPro\Services\WiseChatHttpRequestService;
use Kainex\WiseChatPro\Services\UserBansService;
use Kainex\WiseChatPro\Services\WiseChatMessagesService;
use Kainex\WiseChatPro\Services\WiseChatPendingChatsService;
use Kainex\WiseChatPro\Services\WiseChatPrivateMessagesRulesService;
use Kainex\WiseChatPro\Services\WiseChatService;
use Kainex\WiseChatPro\Traits\HttpUtils;
use Kainex\WiseChatPro\WiseChatCrypt;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Wise Chat base endpoints class
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatEndpoint {
	use HttpUtils;

	/** @var UserFeedService */
	protected $userFeedService;

	/**
	 * @var WiseChatClientSide
	 */
	protected $clientSide;

	/**
	 * @var WiseChatMessagesDAO
	 */
	protected $messagesDAO;

	/**
	 * @var WiseChatChannelsDAO
	 */
	protected $channelsDAO;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var WiseChatUserSettingsDAO
	 */
	protected $userSettingsDAO;

	/**
	 * @var WiseChatChannelUsersDAO
	 */
	protected $channelUsersDAO;

	/**
	 * @var UserMutesDAO
	 */
	protected $userMutesDAO;

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	 * @var WiseChatRenderer
	 */
	protected $renderer;

	/**
	 * @var UserMutesService
	 */
	protected $userMutesService;

	/**
	 * @var UserBansService
	 */
	protected $bansService;

	/**
	 * @var WiseChatMessagesService
	 */
	protected $messagesService;

	/**
	 * @var WiseChatUserService
	 */
	protected $userService;

	/**
	 * @var WiseChatService
	 */
	protected $service;

	/**
	 * @var WiseChatChannelsService
	 */
	protected $channelsService;

	/** @var WiseChatChannelsSourcesService */
	protected $channelsSourcesService;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

	/**
	 * @var WiseChatUserEvents
	 */
	protected $userEvents;

	/**
	 * @var WiseChatAuthorization
	 */
	protected $authorization;

	/**
	 * @var WiseChatPendingChatsService
	 */
	protected $pendingChatsService;

	/**
	 * @var WiseChatPrivateMessagesRulesService
	 */
	protected $privateMessagesRulesService;

	/**
	 * @var WiseChatHttpRequestService
	 */
	protected $httpRequestService;

	/**
	 * @var WiseChatMessageReactionsService
	 */
	protected $messageReactionsService;

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	/**
	 * @var WiseChatTwilioVideo
	 */
	protected $videoService;

	/**
	 * @param UserFeedService $userFeedService
	 * @param WiseChatClientSide $clientSide
	 * @param WiseChatMessagesDAO $messagesDAO
	 * @param WiseChatChannelsDAO $channelsDAO
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatUserSettingsDAO $userSettingsDAO
	 * @param WiseChatChannelUsersDAO $channelUsersDAO
	 * @param UserMutesDAO $userMutesDAO
	 * @param WiseChatActions $actions
	 * @param WiseChatRenderer $renderer
	 * @param UserMutesService $userMutesService
	 * @param UserBansService $bansService
	 * @param WiseChatMessagesService $messagesService
	 * @param WiseChatUserService $userService
	 * @param WiseChatService $service
	 * @param WiseChatChannelsService $channelsService
	 * @param WiseChatChannelsSourcesService $channelsSourcesService
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatUserEvents $userEvents
	 * @param WiseChatAuthorization $authorization
	 * @param WiseChatPendingChatsService $pendingChatsService
	 * @param WiseChatPrivateMessagesRulesService $privateMessagesRulesService
	 * @param WiseChatHttpRequestService $httpRequestService
	 * @param WiseChatMessageReactionsService $messageReactionsService
	 * @param WiseChatOptions $options
	 * @param WiseChatTwilioVideo $videoService
	 */
	public function __construct(UserFeedService $userFeedService, WiseChatClientSide $clientSide, WiseChatMessagesDAO $messagesDAO, WiseChatChannelsDAO $channelsDAO, WiseChatUsersDAO $usersDAO, WiseChatUserSettingsDAO $userSettingsDAO, WiseChatChannelUsersDAO $channelUsersDAO, UserMutesDAO $userMutesDAO, WiseChatActions $actions, WiseChatRenderer $renderer, UserMutesService $userMutesService, UserBansService $bansService, WiseChatMessagesService $messagesService, WiseChatUserService $userService, WiseChatService $service, WiseChatChannelsService $channelsService, WiseChatChannelsSourcesService $channelsSourcesService, WiseChatAuthentication $authentication, WiseChatUserEvents $userEvents, WiseChatAuthorization $authorization, WiseChatPendingChatsService $pendingChatsService, WiseChatPrivateMessagesRulesService $privateMessagesRulesService, WiseChatHttpRequestService $httpRequestService, WiseChatMessageReactionsService $messageReactionsService, WiseChatOptions $options, WiseChatTwilioVideo $videoService) {
		$this->userFeedService = $userFeedService;
		$this->clientSide = $clientSide;
		$this->messagesDAO = $messagesDAO;
		$this->channelsDAO = $channelsDAO;
		$this->usersDAO = $usersDAO;
		$this->userSettingsDAO = $userSettingsDAO;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->userMutesDAO = $userMutesDAO;
		$this->actions = $actions;
		$this->renderer = $renderer;
		$this->userMutesService = $userMutesService;
		$this->bansService = $bansService;
		$this->messagesService = $messagesService;
		$this->userService = $userService;
		$this->service = $service;
		$this->channelsService = $channelsService;
		$this->channelsSourcesService = $channelsSourcesService;
		$this->authentication = $authentication;
		$this->userEvents = $userEvents;
		$this->authorization = $authorization;
		$this->pendingChatsService = $pendingChatsService;
		$this->privateMessagesRulesService = $privateMessagesRulesService;
		$this->httpRequestService = $httpRequestService;
		$this->messageReactionsService = $messageReactionsService;
		$this->options = $options;
		$this->videoService = $videoService;
	}

	/**
	 * Checks if user is authenticated.
	 *
	 * @throws WiseChatUnauthorizedAccessException
	 */
	protected function checkUserAuthentication() {
		if (!$this->authentication->isAuthenticated()) {
			throw new WiseChatUnauthorizedAccessException('Not authenticated');
		}
	}

	protected function confirmUserAuthenticationOrEndRequest() {
		if (!$this->authentication->isAuthenticated()) {
			$this->sendBadRequestStatus();
			die('{ }');
		}
	}

	/**
	 * @throws WiseChatUnauthorizedAccessException
	 */
	protected function checkUserAuthorization() {
		if ($this->service->isChatRestrictedForAnonymousUsers()) {
			throw new WiseChatUnauthorizedAccessException('Access denied');
		}
		if ($this->service->isChatRestrictedForCurrentUserRole()) {
			throw new WiseChatUnauthorizedAccessException('Access denied');
		}
		if ($this->service->isChatRestrictedToCurrentUser()) {
			throw new WiseChatUnauthorizedAccessException('Access denied');
		}
	}

	/**
	 * @throws WiseChatUnauthorizedAccessException
	 */
	protected function checkBanned() {
		if ($this->bansService->isBanned()) {
			throw new WiseChatUnauthorizedAccessException(__('You are blocked from using the chat', 'wise-chat-pro'));
		}
	}

	/**
	 * @throws WiseChatUnauthorizedAccessException
	 */
	protected function checkUserWriteAuthorization() {
		if (!$this->userService->isSendingMessagesAllowed() && !$this->authentication->isAuthenticatedExternally()) {
			throw new WiseChatUnauthorizedAccessException('No write permission');
		}
	}

	/**
	 * @throws Exception
	 */
	protected function checkChatOpen() {
		if (!$this->service->isChatOpen()) {
			throw new Exception(__('The chat is closed now', 'wise-chat-pro'));
		}
	}

	/**
	 * @param Channel $channel
	 * @throws Exception
	 */
	protected function checkChannel($channel) {
		if ($channel === null) {
			throw new Exception('Channel does not exist');
		}
	}

	/**
	 * @param Channel $channel
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	protected function checkChannelAuthorization($channel) {
		if (!$this->channelsService->isUserAuthorizedForChannel($channel)) {
			throw new WiseChatUnauthorizedAccessException('Not authorized in this channel');
		}
	}

	protected function generateCheckSum() {
		$checksum = $this->getParam('checksum');
		if ($checksum !== null) {
			$decoded = unserialize(WiseChatCrypt::decryptFromString(base64_decode($checksum)));
			if (is_array($decoded)) {
				$decoded['ts'] = time();

				return base64_encode(WiseChatCrypt::encryptToString(serialize($decoded)));
			}
		}
		return null;
	}

	protected function verifyCheckSum() {
		$checksum = $this->getParam('checksum');

		if ($checksum !== null) {
			$decoded = unserialize(WiseChatCrypt::decryptFromString(base64_decode($checksum)));
			if (is_array($decoded)) {
				$timestamp = array_key_exists('ts', $decoded) ? $decoded['ts'] : time();
				$validityTime = $this->options->getIntegerOption('ajax_validity_time', 1440) * 60;
				if ($timestamp + $validityTime < time()) {
					$this->sendNotFoundStatus();
					die();
				}

				if (array_key_exists('_bpg', $decoded)) {
					$decoded['buddypress_group_id'] = intval($decoded['_bpg']);
				}

				$this->options->replaceOptions($decoded);
			}
		}
	}

	protected function checkUserRight($rightName) {
		if (!$this->usersDAO->hasCurrentWpUserRight($rightName) && !$this->usersDAO->hasCurrentBpUserRight($rightName)) {
			throw new WiseChatUnauthorizedAccessException('Not enough privileges to execute this request');
		}
	}

	/**
	 * @param string $encryptedChannelId
	 * @return WiseChatUser
	 * @throws Exception
	 */
	protected function getUserFromEncryptedId($encryptedChannelId) {
		$channelTypeAndId = WiseChatCrypt::decryptFromString($encryptedChannelId);
		if ($channelTypeAndId === null) {
			throw new Exception('Invalid channel');
		}

		if (strpos($channelTypeAndId, 'd|') !== false) {
			return $this->usersDAO->get(intval(str_replace('d|', '', $channelTypeAndId)));
		} else {
			throw new Exception('Unknown channel');
		}
	}

	protected function hasPublicChannelsAccess() {
		return ($this->options->getIntegerOption('mode', 0) === 0 && !($this->options->isOptionEnabled('classic_disable_channel', false)))
			|| ($this->options->getIntegerOption('mode', 0) === 1 && !($this->options->isOptionEnabled('fb_disable_channel', false)));
	}

}