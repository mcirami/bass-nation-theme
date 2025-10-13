<?php

namespace Kainex\WiseChatPro\Services;

use Exception;
use Kainex\WiseChatPro\DAO\WiseChatMessagesDAO;
use Kainex\WiseChatPro\DAO\WiseChatPendingChatsDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\Model\WiseChatPendingChat;
use Kainex\WiseChatPro\Model\WiseChatUser;

/**
 * WiseChat pending chats services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatPendingChatsService {

	/**
	 * @var WiseChatMessagesDAO
	 */
	protected $messagesDAO;

	/**
	 * @var WiseChatPendingChatsDAO
	 */
	private $pendingChatsDAO;

	/**
	 * @param WiseChatMessagesDAO $messagesDAO
	 * @param WiseChatPendingChatsDAO $pendingChatsDAO
	 */
	public function __construct(WiseChatMessagesDAO $messagesDAO, WiseChatPendingChatsDAO $pendingChatsDAO) {
		$this->messagesDAO = $messagesDAO;
		$this->pendingChatsDAO = $pendingChatsDAO;
	}

	/**
	 * Creates new pending chat for given message and channel.
	 *
	 * @param Message $message
	 * @param Channel $channel
	 */
	public function addPendingChat($message, $channel) {
		$pendingChat = new WiseChatPendingChat();
		$pendingChat->setChannelId($channel->getId());
		$pendingChat->setUserId($message->getUserId());
		$pendingChat->setRecipientId($message->getRecipientId());
		$pendingChat->setMessageId($message->getId());
		$pendingChat->setTime(time());
		$pendingChat->setChecked(false);
		$this->pendingChatsDAO->save($pendingChat);
	}

	/**
	 * Sets pending chats as checked.
	 *
	 * @param integer $userId Sender encrypted ID
	 * @param WiseChatUser $recipient
	 * @param integer $channelId
	 * @throws Exception
	 */
	public function setPendingChatChecked($userId, $recipient, $channelId) {
		$pendingChats = $this->pendingChatsDAO->getAllUnreadByUserRecipientAndChannel($userId, $recipient->getId(), $channelId);
		foreach ($pendingChats as $pendingChat) {
			$pendingChat->setChecked(true);
			$this->pendingChatsDAO->save($pendingChat);
		}
	}

	/**
	 * Returns last unread unread messages for each user.
	 *
	 * @param WiseChatUser|null $user
	 * @param Channel $channel
	 *
	 * @return Message[]
	 */
	public function getUnreadMessages($user, $channel) {
		if ($user === null) {
			return array();
		}

		$messages = array();
		$unreadMessages = $this->pendingChatsDAO->getAllUnreadDirectMessages($user->getId(), $channel->getId());
		foreach ($unreadMessages as $unreadMessage) {
			if (array_key_exists($unreadMessage->getUserId(), $messages)) {
				continue;
			}
			$messages[$unreadMessage->getUserId()] = $unreadMessage;
		}

		return array_values($messages);
	}
}