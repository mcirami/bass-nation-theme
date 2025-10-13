<?php

namespace Kainex\WiseChatPro\Endpoints;

use Exception;
use Kainex\WiseChatPro\Commands\WiseChatCommandsResolver;
use Kainex\WiseChatPro\Container;
use Kainex\WiseChatPro\Exceptions\WiseChatUnauthorizedAccessException;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\WiseChatCrypt;

/**
 * Wise Chat message actions endpoint class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMessageEndpoint extends WiseChatEndpoint {

	/**
	 * New message endpoint.
	 */
	public function messageEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->verifyCheckSum();

		$encryptedChannelId = trim($this->getPostParam('channelId'));
		$message = trim($this->getPostParam('message'));
		$attachments = $this->getPostParam('attachments');
		$replyToMessageId = $this->getPostParam('replyToMessageId');
		if (!is_array($attachments)) {
			$attachments = array();
		}

		$response = array();
		try {
			$this->checkBanned();
			$this->checkUserAuthentication();
			$this->checkUserAuthorization();
			$this->checkUserWriteAuthorization();
			$this->checkChatOpen();

			$channel = $this->clientSide->getChannelFromEncryptedId($encryptedChannelId);
			$this->checkChannel($channel);
			$this->checkChannelAuthorization($channel);

			if (!$message && count($attachments) === 0) {
				throw new Exception('Missing required fields');
			}

			$user = $this->authentication->getUser();

			/** @var WiseChatCommandsResolver $wiseChatCommandsResolver */
			$wiseChatCommandsResolver = Container::getInstance()->get(WiseChatCommandsResolver::class);

			// resolve a command if it is recognized:
			$isCommandResolved = $wiseChatCommandsResolver->resolve(
				$user, $this->authentication->getSystemUser(), $channel, $message
			);

			// add a regular message:
			if (!$isCommandResolved) {
				// detect private message request:
				$recipient = null;
				$channelTypeAndId = WiseChatCrypt::decryptFromString($encryptedChannelId);

				if (strpos($channelTypeAndId, 'd|') === 0) {
					if (!$this->options->isOptionEnabled('enable_private_messages')) {
						throw new Exception('Cannot process private message requests');
					}

					$recipient = null;
					$userId = preg_replace('/^d\|/', '' , $channelTypeAndId);
					if (strpos($userId, 'v') === 0) {
						$recipient = $this->usersDAO->createOrGetBasedOnWordPressUserId(str_replace('v', '', $userId));

						// attach channel mapping to the response:
						if ($recipient !== null) {
							$response['channelMapping'] = array(
								'from' => $encryptedChannelId,
								'to' => WiseChatCrypt::encryptToString('d|'.$recipient->getId())
							);
						}
					} else {
						$recipient = $this->usersDAO->get(intval($userId));
					}

					if ($recipient === null || $recipient->getId() == $user->getId()) {
						throw new Exception('Incorrect private message request parameters');
					}

					if (!$this->privateMessagesRulesService->isMessageDeliveryAllowed($user, $recipient)) {
						throw new Exception('You are not allowed to send private messages to this user.');
					}
				} else if (!$this->hasPublicChannelsAccess()) {
					throw new Exception('Access denied to the public channel');
				}

				$replyToMessage = $replyToMessageId ? $this->clientSide->getMessageOrThrowException($replyToMessageId) : null;
				$addedMessage = $this->messagesService->addMessage($user, $channel, $message, $attachments, false, $recipient, $replyToMessage);

				// add pending chat to offline user:
				if ($recipient !== null && $addedMessage !== null) {
					$this->userService->setInactiveUsersOfflineStatus();
					if (!$this->channelUsersDAO->isOnline($recipient->getId())) {
						$this->pendingChatsService->addPendingChat($addedMessage, $channel);
					}
				}

				if ($addedMessage !== null) {
					$response['message'] = array(
						'text' => $addedMessage->getText(),
						'hidden' => $addedMessage->isHidden()
					);
				}
			}

			$response['result'] = 'OK';
		} catch (WiseChatUnauthorizedAccessException $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendUnauthorizedStatus();
		} catch (Exception $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendBadRequestStatus();
		}

		echo json_encode($response);
		die();
	}

	/**
	 * Returns a message by given ID.
	 */
	public function getMessageEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->confirmUserAuthenticationOrEndRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkGetParams(array('id'));
			$message = $this->clientSide->getMessageOrThrowException($this->getGetParam('id'));
			$channel = $this->channelsDAO->get($message->getChannelId());

			$this->checkBanned();
			$this->checkUserAuthorization();
			$this->checkChatOpen();
			$this->checkChannel($channel);
			$this->checkChannelAuthorization($channel);

			$response['result'] = array();
			$response['nowTime'] = gmdate('c', time());
			$userId = $this->authentication->getUserIdOrNull();

			// get the message:
			$messages = array($message);

			/** @var Message $message */
			foreach ($messages as $message) {
				// omit non-admin messages:
				if ($message->isAdmin() && !$this->usersDAO->isWpUserAdminLogged()) {
					continue;
				}

				// omit not-related private messages:
				if ($message->getRecipientId() > 0 && $message->getRecipientId() != $userId && $message->getUserId() != $userId) {
					continue;
				}

				$channelId = $this->clientSide->encryptPublicChannelId($channel->getId());
				if ($message->getRecipientId() > 0) {
					$directUserId = $this->authentication->getUserIdOrNull() === $message->getRecipientId() ? $message->getUserId() : $message->getRecipientId();
					$channelId = WiseChatCrypt::encryptToString('d|'.$directUserId);
				}

				$response['result'][] = $this->clientSide->toPlainMessage($message, $channelId, $channel->getName());
			}
		} catch (WiseChatUnauthorizedAccessException $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendUnauthorizedStatus();
		} catch (Exception $exception) {
			$response['error'] = $exception->getMessage();
			$this->sendBadRequestStatus();
		}

		echo json_encode($response);
		die();
	}

}