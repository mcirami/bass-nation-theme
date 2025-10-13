<?php

namespace Kainex\WiseChatPro\Endpoints\Maintenance;

use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelsDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatMessagesDAO;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\Rendering\WiseChatRenderer;
use Kainex\WiseChatPro\Rendering\WiseChatUITemplates;
use Kainex\WiseChatPro\Services\User\WiseChatActions;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\Services\User\WiseChatAuthorization;
use Kainex\WiseChatPro\Services\User\WiseChatUserService;
use Kainex\WiseChatPro\Services\WiseChatChannelsService;
use Kainex\WiseChatPro\Services\WiseChatMessagesService;
use Kainex\WiseChatPro\Services\WiseChatPendingChatsService;
use Kainex\WiseChatPro\Services\WiseChatPrivateMessagesRulesService;
use Kainex\WiseChatPro\WiseChatCrypt;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Class for loading recent chats.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMaintenanceRecentChats {

	/**
	 * @var WiseChatMessagesDAO
	 */
	protected $messagesDAO;

	/**
	 * @var WiseChatChannelsDAO
	 */
	protected $channelsDAO;

	/**
	 * @var WiseChatChannelUsersDAO
	 */
	protected $channelUsersDAO;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	 * @var WiseChatRenderer
	 */
	protected $renderer;

	/**
	 * @var WiseChatMessagesService
	 */
	protected $messagesService;

	/**
	 * @var WiseChatUserService
	 */
	protected $userService;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

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
	 * @var WiseChatUITemplates
	 */
	protected $uiTemplates;

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	/**
	 * @param WiseChatMessagesDAO $messagesDAO
	 * @param WiseChatChannelsDAO $channelsDAO
	 * @param WiseChatChannelUsersDAO $channelUsersDAO
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatActions $actions
	 * @param WiseChatRenderer $renderer
	 * @param WiseChatMessagesService $messagesService
	 * @param WiseChatUserService $userService
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatAuthorization $authorization
	 * @param WiseChatPendingChatsService $pendingChatsService
	 * @param WiseChatPrivateMessagesRulesService $privateMessagesRulesService
	 * @param WiseChatUITemplates $uiTemplates
	 * @param WiseChatOptions $options
	 */
	public function __construct(WiseChatMessagesDAO $messagesDAO, WiseChatChannelsDAO $channelsDAO, WiseChatChannelUsersDAO $channelUsersDAO, WiseChatUsersDAO $usersDAO, WiseChatActions $actions, WiseChatRenderer $renderer, WiseChatMessagesService $messagesService, WiseChatUserService $userService, WiseChatAuthentication $authentication, WiseChatAuthorization $authorization, WiseChatPendingChatsService $pendingChatsService, WiseChatPrivateMessagesRulesService $privateMessagesRulesService, WiseChatUITemplates $uiTemplates, WiseChatOptions $options) {
		$this->messagesDAO = $messagesDAO;
		$this->channelsDAO = $channelsDAO;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->usersDAO = $usersDAO;
		$this->actions = $actions;
		$this->renderer = $renderer;
		$this->messagesService = $messagesService;
		$this->userService = $userService;
		$this->authentication = $authentication;
		$this->authorization = $authorization;
		$this->pendingChatsService = $pendingChatsService;
		$this->privateMessagesRulesService = $privateMessagesRulesService;
		$this->uiTemplates = $uiTemplates;
		$this->options = $options;
	}

	/**
	 * @return array
	 */
	public function getRecentChats() {
		$pmChannel = $this->channelsDAO->getByName(WiseChatChannelsService::PRIVATE_MESSAGES_CHANNEL);
		if (!$pmChannel) {
			return array();
		}

		$recentChats = array();
		$duplicationCheck = array();
		$unread = $this->pendingChatsService->getUnreadMessages($this->authentication->getUser(), $pmChannel);
		foreach ($unread as $message) {
			$recentChat = $this->convertMessageToRecentChat($message, false);
			if ($recentChat !== null) {
				$recentChats[] = $recentChat;
				$duplicationCheck[$message->getUserId()] = 1;
			}
		}

		$read = $this->messagesDAO->getAllNewestDirectMessages($this->authentication->getUser(), $pmChannel, $this->options->getIntegerOption('recent_chats_limit', 20));
		foreach ($read as $message) {
			if (array_key_exists($message->getUserId(), $duplicationCheck) || array_key_exists($message->getRecipientId(), $duplicationCheck)) {
				continue;
			}

			$recentChat = $this->convertMessageToRecentChat($message, true);
			if ($recentChat !== null) {
				$recentChats[] = $recentChat;
			}
		}

		return $recentChats;
	}

	/**
	 * @param Message $message
	 * @param boolean $read
	 * @return array|null
	 */
	private function convertMessageToRecentChat($message, $read) {
		$directUserId = $this->authentication->getUserIdOrNull() === $message->getRecipientId() ? $message->getUserId() : $message->getRecipientId();

		$directUser = $this->usersDAO->get($directUserId);
		if ($directUser === null) {
			return null;
		}

		$isAllowed = false;
		if ($this->options->isOptionEnabled('enable_private_messages', false) || !$this->options->isOptionEnabled('users_list_linking', false)) {
			$isAllowed = $this->privateMessagesRulesService->isMessageDeliveryAllowed($this->authentication->getUser(), $directUser);
		}

		return array(
			'id' => WiseChatCrypt::encryptToString($message->getId()),
			'channel' => array(
				'id' => WiseChatCrypt::encryptToString('d|'.$directUserId),
				'name' => $directUser->getName(),
				'type' => 'direct',
				'configuration' => WiseChatChannelsDAO::DEFAULT_CONFIGURATION,
				'readOnly' => !$isAllowed,
				'avatar' => $this->options->isOptionEnabled('show_users_list_avatars', false) ? $this->userService->getUserAvatar($directUser) : null,
				'online' => $this->channelUsersDAO->isOnline($directUserId),
				'intro' => $this->uiTemplates->getDirectChannelIntro($directUser)
			),
			'text' => $message->getText(),
			'timeUTC' => gmdate('c', $message->getTime()),
			'sortKey' => $message->getTime().$message->getId(),
			'read' => $read
		);
	}
}