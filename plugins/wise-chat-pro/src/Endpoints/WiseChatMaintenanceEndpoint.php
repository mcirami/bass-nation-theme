<?php

namespace Kainex\WiseChatPro\Endpoints;

use Exception;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\User\WiseChatUserSettingsDAO;
use Kainex\WiseChatPro\DAO\UserMutesDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelsDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatMessagesDAO;
use Kainex\WiseChatPro\Endpoints\Maintenance\WiseChatMaintenanceAuth;
use Kainex\WiseChatPro\Endpoints\Maintenance\WiseChatMaintenanceChannels;
use Kainex\WiseChatPro\Endpoints\Maintenance\MaintenanceI18n;
use Kainex\WiseChatPro\Endpoints\Maintenance\WiseChatMaintenanceRecentChats;
use Kainex\WiseChatPro\Integrations\Twilio\WiseChatTwilioVideo;
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
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Wise Chat maintenance endpoint class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMaintenanceEndpoint extends WiseChatEndpoint {

	/** @var WiseChatMaintenanceAuth */
	private $maintenanceAuth;

	/** @var MaintenanceI18n */
	private $maintenanceI18n;

	/** @var WiseChatMaintenanceRecentChats */
	private $maintenanceRecentChats;

	/** @var WiseChatMaintenanceChannels */
	private $maintenanceChannels;

	public function __construct(UserFeedService $userFeedService, WiseChatMaintenanceAuth $maintenanceAuth, MaintenanceI18n $maintenanceI18n, WiseChatMaintenanceRecentChats $maintenanceRecentChats, WiseChatMaintenanceChannels $maintenanceChannels, WiseChatClientSide $clientSide, WiseChatMessagesDAO $messagesDAO, WiseChatChannelsDAO $channelsDAO, WiseChatUsersDAO $usersDAO, WiseChatUserSettingsDAO $userSettingsDAO, WiseChatChannelUsersDAO $channelUsersDAO, UserMutesDAO $userMutesDAO, WiseChatActions $actions, WiseChatRenderer $renderer, UserMutesService $userMutesService, UserBansService $bansService, WiseChatMessagesService $messagesService, WiseChatUserService $userService, WiseChatService $service, WiseChatChannelsService $channelsService, WiseChatChannelsSourcesService $channelsSourcesService, WiseChatAuthentication $authentication, WiseChatUserEvents $userEvents, WiseChatAuthorization $authorization, WiseChatPendingChatsService $pendingChatsService, WiseChatPrivateMessagesRulesService $privateMessagesRulesService, WiseChatHttpRequestService $httpRequestService, WiseChatMessageReactionsService $messageReactionsService, WiseChatOptions $options, WiseChatTwilioVideo $videoService) {
		parent::__construct($userFeedService, $clientSide, $messagesDAO, $channelsDAO, $usersDAO, $userSettingsDAO, $channelUsersDAO, $userMutesDAO, $actions, $renderer, $userMutesService, $bansService, $messagesService, $userService, $service, $channelsService, $channelsSourcesService, $authentication, $userEvents, $authorization, $pendingChatsService, $privateMessagesRulesService, $httpRequestService, $messageReactionsService, $options, $videoService);

		$this->maintenanceAuth = $maintenanceAuth;

		$this->maintenanceI18n = $maintenanceI18n;

		$this->maintenanceRecentChats = $maintenanceRecentChats;

		$this->maintenanceChannels = $maintenanceChannels;
	}

	/**
	 * Endpoint to perform periodic (every 10-20 seconds) maintenance services like:
	 * - user auto-authentication, authentication requests
	 * - getting the list of events to listen on the client side
	 * - maintenance actions in messages, bans, users, etc.
	 */
	public function maintenanceEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->verifyCheckSum();

		$response = array('events' => array());
		try {
			$this->checkGetParams(array('full'));
			$isFull = $this->getGetParam('full') === 'true';

			// periodic maintenance:
			$this->userService->periodicMaintenance();
			$this->messagesService->periodicMaintenance();
			$this->userMutesService->periodicMaintenance();

			// send user-related content:
			if (!$this->maintenanceAuth->needsAuth()) {
				$this->userService->autoAuthenticateOnMaintenance();

				// merge user dependent events:
				$response['events'] = $this->getUserDependentEvents($isFull);
			}

			// get authentication requests / access denied screens and the public events:
			$response['events'] = array_merge($response['events'], $this->maintenanceAuth->getEvents(), $this->getPublicEvents($isFull));
		} catch (Exception $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendBadRequestStatus();
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Returns events accessible without authentication.
	 *
	 * @param boolean $isFull
	 * @return array
	 */
	private function getPublicEvents($isFull) {
		$events = array();

		$events[] = array(
			'name' => 'checkSum',
			'data' => $this->generateCheckSum()
		);

		if ($isFull) {
			$events[] = array(
				'name' => 'i18n',
				'data' => $this->maintenanceI18n->getTranslations()
			);
		}

		return $events;
	}

	/**
	 * Returns events accessible for authenticated users only.
	 *
	 * @param boolean $isFull
	 * @return array
	 * @throws Exception
	 */
	private function getUserDependentEvents($isFull) {
		$events = array();

		if ($isFull || $this->userEvents->shouldTriggerEvent('browser', 'full')) {
			if ($this->options->isOptionEnabled('users_list_offline_enable', true) && $this->options->isOptionEnabled('enable_private_messages')) {
				$events[] = array(
					'name' => 'recentChats',
					'data' => $this->maintenanceRecentChats->getRecentChats()
				);
			}

			// public channels:
			if ($this->maintenanceChannels->arePublicChannelsEnabled()) {
				$events[] = array(
					'name' => 'publicChannels',
					'data' => $this->maintenanceChannels->getPublicChannels()
				);
			}

			// direct channels:
			if ($this->options->isOptionEnabled('show_users')) {
				$events[] = array(
					'name' => 'directChannels',
					'data' => $this->maintenanceChannels->getDirectChannels()
				);
			}

			// auto open channels:
			$events[] = array(
				'name' => 'autoOpenChannel',
				'data' => $this->maintenanceChannels->getAutoOpenChannel()
			);

			// global channels storage (only auto-open for now):
			$events[] = array(
				'name' => 'channels',
				'data' => $this->maintenanceChannels->getChannels()
			);
		}

		if ($isFull || $this->userEvents->shouldTriggerEvent('counter', 'full')) {
			$events[] = array(
				'name' => 'onlineUsersCounter',
				'data' => $this->maintenanceChannels->getDirectChannelsNumber()
			);
		}

		if ($isFull || $this->userEvents->shouldTriggerEvent('feed', 'full')) {
			$events[] = array(
				'name' => 'userFeed',
				'data' => $this->maintenanceChannels->getUserFeed()
			);
		}

		return $events;
	}

}