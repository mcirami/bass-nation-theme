<?php

namespace Kainex\WiseChatPro\Endpoints;

use Exception;
use Kainex\WiseChatPro\Exceptions\WiseChatUnauthorizedAccessException;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\Services\WiseChatChannelsService;
use Kainex\WiseChatPro\WiseChatCrypt;

/**
 * Wise Chat messages endpoint class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMessagesEndpoint extends WiseChatEndpoint {

	/**
	 * Returns messages to render in the chat window.
	 */
	public function messagesEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->confirmUserAuthenticationOrEndRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkGetParams(array('lastId', 'fromActionId'));
			$encryptedLastId = $this->getGetParam('lastId', '0');
			$lastId = intval(WiseChatCrypt::decryptFromString($encryptedLastId));
			$initRequest = $this->getGetParam('init') === '1';
			$directEnabled = $this->options->isOptionEnabled('enable_private_messages');

			$channels = $this->channelsSourcesService->getChannels();

			$this->checkBanned();
			$this->checkUserAuthorization();
			$this->checkChatOpen();
			$channels = array_filter($channels, function($channel) { return $this->channelsService->isUserAuthorizedForChannel($channel); } );

			$response['init'] = $initRequest;
			$response['nowTime'] = gmdate('c', time());
			$response['result'] = array();

			// get and render messages:
			if ($initRequest) {
				// read the past direct messages:
				$messages = $directEnabled
					? $this->messagesService->getAllPrivateByUser($this->authentication->getUser()->getId())
					: array();

				// read the past channel messages:
				if ($this->hasPublicChannelsAccess()) {
					foreach ($channels as $channel) {
						$messages = array_merge(
							$this->messagesService->getAllPublicByChannelId($channel->getId()),
							$messages
						);
					}
				}

				// sort by ID:
				usort($messages, function($a, $b) {
					return $a->getId() > $b->getId() ? 1 : -1;
				});
			} else {
				// read current messages:
				$channelNames = array_map(function($channel) { return $channel->getName(); }, $channels);

				// enable direct channel if enabled:
				$privateMessagesSenderOrRecipientId = null;
				if ($directEnabled) {
					$channelNames[] = WiseChatChannelsService::PRIVATE_MESSAGES_CHANNEL;
					$privateMessagesSenderOrRecipientId = $this->authentication->getUserIdOrNull();
				}

				$messages = $this->messagesService->getAllByChannelNamesAndOffset($channelNames, $encryptedLastId !== '0' ? $lastId : null, $privateMessagesSenderOrRecipientId);
			}

			$channelsMap = array();
			foreach ($channels as $channel) {
				$channelsMap[$channel->getId()] = $channel;
			}

			$this->messageReactionsService->cacheReactions($messages);

			/** @var Message $message */
			foreach ($messages as $message) {
				// omit non-admin messages:
				if ($message->isAdmin() && !$this->usersDAO->isWpUserAdminLogged()) {
					continue;
				}

				$attributes = array(
					'live' => !$initRequest
				);

				$channelName = isset($channelsMap[$message->getChannelId()]) ? $channelsMap[$message->getChannelId()]->getName() : 'Unknown';
				if ($message->getRecipientId() > 0) {
					$directUserId = $this->authentication->getUserIdOrNull() === $message->getRecipientId() ? $message->getUserId() : $message->getRecipientId();
					$channelId = WiseChatCrypt::encryptToString('d|'.$directUserId);
				} else if (!$this->hasPublicChannelsAccess()) {
					continue;
				} else {
					$channelId = $this->clientSide->encryptPublicChannelId($message->getChannelId());
				}

				$response['result'][] = $this->clientSide->toPlainMessage($message, $channelId, $channelName, $attributes);
			}

			// load actions:
			$fromActionId = intval($this->getGetParam('fromActionId', 0));
			$response['actions'] = $fromActionId > 0 ? $this->actions->getJSONReadyActions($fromActionId, $this->authentication->getUser()) : array();
			$response['lastActionId'] = $this->actions->getLastActionId();

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
	 * Loads past messages in the given channel. Without beforeMessage parameter it loads last messages.
	 */
	public function pastMessagesEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->confirmUserAuthenticationOrEndRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkGetParams(array('channelId'));
			$encryptedBeforeMessage = $this->getGetParam('beforeMessage', '');
			$channelId = $this->getGetParam('channelId');
			$channel = $this->clientSide->getChannelFromEncryptedId($channelId);

			$this->checkBanned();
			$this->checkUserAuthorization();
			$this->checkChatOpen();

			$response['result'] = array();
			$messages = $this->messagesService->getMessagesOfChannel($channelId, $encryptedBeforeMessage);
			foreach ($messages as $message) {
				$response['result'][] = $this->clientSide->toPlainMessage($message, $channelId, $channel->getName(), array( 'live' => false ));
			}

			shuffle($response['result']);

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