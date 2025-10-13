<?php

namespace Kainex\WiseChatPro\Endpoints\Maintenance;

use Exception;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelUsersDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Model\Channel\ChannelUser;
use Kainex\WiseChatPro\Model\WiseChatUser;
use Kainex\WiseChatPro\Rendering\WiseChatRenderer;
use Kainex\WiseChatPro\Rendering\WiseChatUITemplates;
use Kainex\WiseChatPro\Services\Channels\Listing\WiseChatChannelsSourcesService;
use Kainex\WiseChatPro\Services\ClientSide\WiseChatClientSide;
use Kainex\WiseChatPro\Services\User\UserFeedService;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\Services\User\WiseChatAuthorization;
use Kainex\WiseChatPro\Services\User\WiseChatUserService;
use Kainex\WiseChatPro\Services\UserMutesService;
use Kainex\WiseChatPro\Services\WiseChatChannelsService;
use Kainex\WiseChatPro\Services\WiseChatMessagesService;
use Kainex\WiseChatPro\Services\WiseChatService;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Class for loading channels.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMaintenanceChannels {

	/** @var UserFeedService */
	private $userFeedService;

	/** @var WiseChatChannelsSourcesService */
	private $channelsSourcesService;

	/**
	 * @var WiseChatClientSide
	 */
	protected $clientSide;

	/**
	 * @var WiseChatChannelUsersDAO
	 */
	protected $channelUsersDAO;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

	/**
	 * @var WiseChatAuthorization
	 */
	protected $authorization;

	/**
	 * @var WiseChatUserService
	 */
	protected $userService;

	/**
	 * @var WiseChatService
	 */
	protected $service;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var WiseChatMessagesService
	 */
	protected $messagesService;

	/**
	 * @var WiseChatChannelsService
	 */
	protected $channelsService;

	/**
	 * @var WiseChatRenderer
	 */
	protected $renderer;

	/**
	 * @var WiseChatUITemplates
	 */
	protected $uiTemplates;

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	/** @var array All required channels (both public and direct) */
	private $channelsStorage;

	private UserMutesService $userMutesService;

	/**
	 * @param UserMutesService $userMutesService
	 * @param UserFeedService $userFeedService
	 * @param WiseChatChannelsSourcesService $channelsSourcesService
	 * @param WiseChatClientSide $clientSide
	 * @param WiseChatChannelUsersDAO $channelUsersDAO
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatAuthorization $authorization
	 * @param WiseChatUserService $userService
	 * @param WiseChatService $service
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatMessagesService $messagesService
	 * @param WiseChatChannelsService $channelsService
	 * @param WiseChatRenderer $renderer
	 * @param WiseChatUITemplates $uiTemplates
	 * @param WiseChatOptions $options
	 */
	public function __construct(UserMutesService $userMutesService, UserFeedService $userFeedService, WiseChatChannelsSourcesService $channelsSourcesService, WiseChatClientSide $clientSide, WiseChatChannelUsersDAO $channelUsersDAO, WiseChatAuthentication $authentication, WiseChatAuthorization $authorization, WiseChatUserService $userService, WiseChatService $service, WiseChatUsersDAO $usersDAO, WiseChatMessagesService $messagesService, WiseChatChannelsService $channelsService, WiseChatRenderer $renderer, WiseChatUITemplates $uiTemplates, WiseChatOptions $options) {
		$this->userMutesService = $userMutesService;
		$this->userFeedService = $userFeedService;
		$this->channelsSourcesService = $channelsSourcesService;
		$this->clientSide = $clientSide;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->authentication = $authentication;
		$this->authorization = $authorization;
		$this->userService = $userService;
		$this->service = $service;
		$this->usersDAO = $usersDAO;
		$this->messagesService = $messagesService;
		$this->channelsService = $channelsService;
		$this->renderer = $renderer;
		$this->uiTemplates = $uiTemplates;
		$this->options = $options;
		$this->channelsStorage = array();
	}

	/**
	 * @return Channel[]
	 * @throws Exception
	 */
	public function getPublicChannels() {
		$result = array();
		$channels = $this->channelsSourcesService->getChannels();

		foreach ($channels as $channel) {
			$result[] = $this->clientSide->channelToPlain($channel);
		}

		return $result;
	}



	/**
	 * @return array
	 */
	public function getDirectChannels(): array {
		$plainUsers = [];
		$channelUsers = $this->channelsSourcesService->getDirectChannels();
		$users = [];
		foreach ($channelUsers as $channelUser) {
			$users[] = $channelUser->getUser();
		}

		$supplementaryData = $this->getUsersSupplementaryData($users);

		foreach ($channelUsers as $channelUser) {
			$plainUser = $this->clientSide->getChannelUserAsPlainDirectChannel($channelUser, $supplementaryData);

			$plainUsers[$channelUser->getUser()->getId()] = $plainUser;
		}

		return array_values($plainUsers);
	}

	/**
	 * TODO: optimize
	 *
	 * @return integer
	 */
	public function getDirectChannelsNumber() {
		$plainUsers = [];
		$channelUsers = $this->channelsSourcesService->getDirectChannels();
		foreach ($channelUsers as $channelUser) {
			if ($channelUser->isActive()) {
				$plainUsers[$channelUser->getUser()->getId()] = true;
			}
		}

		return count($plainUsers);
	}

	/**
	 * @return string|null Channel client ID
	 */
	public function getAutoOpenChannel() {
		$rememberAutoOpen = $this->options->isOptionEnabled('auto_open_remember', true);

		if ($this->options->isOptionNotEmpty('auto_open')) {
			$currentUser = $this->authentication->getUser();
			$channelUser = null;

			// try to read from cache:
			if ($rememberAutoOpen && $currentUser->getDataProperty('auto_open_direct_channel')) {
				$autoOpenDirectChannelCandidate = $currentUser->getDataProperty('auto_open_direct_channel');
				$user = $this->usersDAO->get($autoOpenDirectChannelCandidate);
				if ($user) {
					$channelUser = $this->channelUsersDAO->getByUserId($user->getId());

					if ($channelUser) {
						$channelUser->setUser($user);
					} else {
						$channelUser = new ChannelUser();
						$channelUser->setUser($user);
						$channelUser->setActive(false);
						$channelUser->setUserId($user->getId());
					}
				}
			}

			if (!$channelUser) {
				$autoOpenChannelCandidates = (array) $this->options->getOption('auto_open', array());

				// TODO: settings - auto open strategy
				$offlineUsers = array();
				$onlineUsers = array();
				foreach ($autoOpenChannelCandidates as $autoOpenChannelCandidate) {
					$user = $this->usersDAO->createOrGetBasedOnWordPressUserId($autoOpenChannelCandidate);
					if (!$user || $user->getId() === $currentUser->getId()) {
						continue;
					}
					$channelUser = $this->channelUsersDAO->getByUserId($user->getId());

					if ($channelUser) {
						$channelUser->setUser($user);
						if ($channelUser->isActive()) {
							$onlineUsers[] = $channelUser;
						} else {
							$offlineUsers[] = $channelUser;
						}
					} else {
						$channelUser = new ChannelUser();
						$channelUser->setUser($user);
						$channelUser->setActive(false);
						$channelUser->setUserId($user->getId());
						$offlineUsers[] = $channelUser;
					}
				}

				$channelUser = null;
				if (count($onlineUsers) > 0) {
					$channelUser = $onlineUsers[array_rand($onlineUsers)];
				} else if (count($offlineUsers) > 0) {
					$channelUser = $offlineUsers[array_rand($offlineUsers)];
				}
			}

			if ($channelUser) {
				// add a welcome message:
				if ($rememberAutoOpen && !$currentUser->hasDataProperty('auto_open_direct_channel')) {
					$welcomeMessage = $this->uiTemplates->getWelcomeMessage($channelUser->getUser(), $this->authentication->getUser());

					if ($welcomeMessage) {
						$this->messagesService->addMessage(
							$channelUser->getUser(), $this->channelsService->getDirectChannel(), $welcomeMessage, array(), false, $this->authentication->getUser(), null,
							array('disableFilters' => true, 'disableCrop' => true)
						);
					}
				}

				// store the selection:
				if ($rememberAutoOpen) {
					$currentUser->setDataProperty('auto_open_direct_channel', $channelUser->getUserId());
					$this->usersDAO->save($currentUser);
				}

				$this->channelsStorage[] = $this->clientSide->getChannelUserAsPlainDirectChannel($channelUser);

				return $this->clientSide->encryptDirectChannelId($channelUser->getUserId());
			}
		} else {
			// open the 1st public channel:
			if ($this->options->isOptionEnabled('auto_open_first_public_channel', true) && $this->arePublicChannelsEnabled()) {
				$channels = (array) $this->options->getOption('channel');
				if (count($channels) > 0) {
					$channel = $this->channelsSourcesService->getPublicChannelByName($channels[0]);
					if ($channel) {
						$this->channelsStorage[] = $this->clientSide->channelToPlain($channel);

						return $this->clientSide->encryptPublicChannelId($channel->getId());
					}
				}
			}
		}

		return null;
	}

	public function getChannels() {
		return $this->channelsStorage;
	}

	public function arePublicChannelsEnabled() {
		return $this->options->getIntegerOption('mode', 0) === 0 && !($this->options->isOptionEnabled('classic_disable_channel', false))
					|| $this->options->getIntegerOption('mode', 0) === 1 && !($this->options->isOptionEnabled('fb_disable_channel', false));
	}

	public function getUserFeed(): array {
		return $this->userFeedService->getLatest($this->authentication->getUser());
	}

	/**
	 * Loads all related data.
	 *
	 * @param WiseChatUser[] $users
	 * @return array
	 */
	private function getUsersSupplementaryData(array $users): array {
		return [
			'mutedUsers' => $this->userMutesService->getMutedUsersIDs($users)
		];
	}

}