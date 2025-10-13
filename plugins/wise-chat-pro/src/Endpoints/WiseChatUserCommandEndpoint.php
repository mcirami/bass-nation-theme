<?php

namespace Kainex\WiseChatPro\Endpoints;

use Exception;
use Kainex\WiseChatPro\Container;
use Kainex\WiseChatPro\Exceptions\WiseChatUnauthorizedAccessException;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\Services\WiseChatChannelsService;
use Kainex\WiseChatPro\Services\WiseChatImagesService;
use Kainex\WiseChatPro\WiseChatCrypt;

/**
 * Wise Chat user commands endpoint class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatUserCommandEndpoint extends WiseChatEndpoint {

	/**
	 * User commands endpoint.
	 */
	public function userCommandEndpoint() {
		$this->jsonContentType();
		$this->verifyXhrRequest();
		$this->verifyCheckSum();

		$response = array();
		try {
			$this->checkBanned();
			$this->checkChatOpen();
			$this->checkUserAuthentication();
			$this->checkUserAuthorization();
			$this->checkUserWriteAuthorization();
			$this->checkPostParams(array('command', 'parameters'));

			$command = $this->getPostParam('command');
			$parameters = $this->getPostParam('parameters');
			switch ($command) {
				case 'markChannelAsRead':
					$channel = $this->channelsDAO->getByName(WiseChatChannelsService::PRIVATE_MESSAGES_CHANNEL);
					$chatParticipant = $this->getUserFromEncryptedId($parameters['channel']);
					$this->pendingChatsService->setPendingChatChecked($chatParticipant->getId(), $this->authentication->getUser(), $channel->getId());

					$response['value'] = 'OK';
					break;
				case 'setUserProperty':
					$response['value'] = $this->handleSetUserPropertyCommands($parameters);
					break;
				case 'saveMessage':
					$message = $this->saveMessage($parameters);
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$response['result'] = 'OK';
					$response['message'] = $this->clientSide->toPlainMessage($message, $parameters['channelId'], $channel->getName());
					break;
				case 'approveMessage':
					$this->approveMessage($parameters);
					$response['result'] = 'OK';
					break;
				case 'deleteMessage':
					$this->deleteMessage($parameters);
					$response['result'] = 'OK';
					break;
				case 'muteUser':
					$this->muteUser($parameters);
					$response['result'] = 'OK';
					break;
				case 'banUser':
					$this->banUser($parameters);
					$response['result'] = 'OK';
					break;
				case 'reportSpam':
					$this->spamReport($parameters);
					$response['result'] = 'OK';
					break;
				case 'reactToMessage':
					$this->reactToMessage($parameters);
					$response['result'] = 'OK';
					break;
				case 'logOff':
					$this->logOff($parameters);
					$response['result'] = 'OK';
					break;
				case 'startStream':
					$response['stream'] = $this->videoService->startStream($parameters['channel']['id']);
					$response['result'] = 'OK';
					break;
				case 'getStreamToken':
					$response['stream'] = $this->videoService->getToken($parameters);
					$response['result'] = 'OK';
					break;
				case 'createChannel':
					$channel = $this->channelsService->createChannel($parameters);
					$response['channel'] = $this->clientSide->channelToPlain($channel);
					$response['result'] = 'OK';
					break;
				case 'saveChannel':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['id']);
					$channel = $this->channelsService->saveChannel($channel, $parameters);
					$response['channel'] = $this->clientSide->channelToPlain($channel);
					$response['result'] = 'OK';
					break;
				case 'getChannelMembers':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['id']);
					$response['members'] = $this->clientSide->channelMembersToPlain($this->channelsService->getChannelMembers($channel));
					$response['result'] = 'OK';
					break;
				case 'deleteChannelMember':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$memberId = (int) WiseChatCrypt::decryptFromString($parameters['memberId']);
					$this->channelsService->deleteChannelMember($channel, $memberId);
					$response['channel'] = $this->clientSide->channelToPlain($channel);
					$response['result'] = 'OK';
					break;
				case 'deleteChannel':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$this->channelsService->deleteChannel($channel);
					$response['channelId'] = $parameters['channelId'];
					$response['result'] = 'OK';
					break;
				case 'sendChannelNotifications':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$this->channelsService->sendChannelNotifications($channel, $parameters['users']);
					$response['channelId'] = $parameters['channelId'];
					$response['result'] = 'OK';
					break;
				case 'addChannelMember':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					if (isset($parameters['wpUserId'])) {
						$userId = (int)WiseChatCrypt::decryptFromString($parameters['wpUserId']);
						$this->channelsService->addChannelMemberOfWPUser($channel, $userId);
					} else {
						$userId = (int)WiseChatCrypt::decryptFromString($parameters['userId']);
						$this->channelsService->addChannelMember($channel, $userId);
					}
					$response['channel'] = $this->clientSide->channelToPlain($channel);
					$response['result'] = 'OK';
					break;
				case 'confirmChannelMembership':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$this->channelsService->confirmChannelMembership($channel);
					$response['channel'] = $this->clientSide->channelToPlain($channel);
					$response['result'] = 'OK';
					break;
				case 'inviteChannelMember':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$wpUserId = (int)WiseChatCrypt::decryptFromString($parameters['wpUserId']);
					$this->channelsService->addChannelMemberOfWPUser($channel, $wpUserId, false);
					$response['channel'] = $this->clientSide->channelToPlain($channel);
					$response['result'] = 'OK';
					break;
				case 'markFeedEntryAsSeen':
					$userFeedEntryId = (int) WiseChatCrypt::decryptFromString($parameters['id']); // actually WP user ID
					$this->userFeedService->markAsSeen($userFeedEntryId, $this->authentication->getUser()->getId());
					$response['result'] = 'OK';
					break;
				case 'searchUsers':
					$users = $this->usersDAO->getWPUsers(array('search' => $parameters['search'], 'number' => 20));
					$response['users'] = $this->clientSide->wpUsersToPlain($users);
					break;
				case 'searchChannels':
					$memberOf = $this->channelsService->getChannelsUserBelongsTo();
					$memberOfIDs = [];
					foreach ($memberOf as $member) {
						$memberOfIDs[] = $member->getChannelId();
					}
					$constantChannels = $this->channelsService->getConstantChannels();
					$constantChannelsIDs = [];
					foreach ($constantChannels as $constantChannel) {
						$constantChannelsIDs[] = $constantChannel->getId();
					}
					$channels = $this->channelsService->searchChannels(['search' => $parameters['search']]);
					$channelsPlain = [];
					foreach ($channels as $channel) {
						if (strpos($channel->getName(), 'bp-') === 0) {
							continue;
						}
						if (in_array($channel->getId(), $constantChannelsIDs)) {
							continue;
						}
						$channelsPlain[] = [
							'channel' => $this->clientSide->channelToPlain($channel),
							'member' => in_array($channel->getId(), $memberOfIDs)
						];
					}
					$response['entries'] = $channelsPlain;
					break;
				case 'joinChannel':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$this->channelsService->joinChannel($channel);

					break;
				case 'removeUserChannel':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$this->channelsService->removeUserChannel($channel);
					break;
				case 'quitMembership':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$this->channelsService->quitChannelMembership($channel);
					break;
				case 'addUserChannel':
					$channel = $this->clientSide->getChannelFromEncryptedId($parameters['channelId']);
					$this->channelsService->addUserChannel($channel);
					$response['channel'] = $this->clientSide->channelToPlain($channel);
					break;
				case 'getFeed':
					$response['feed'] = $this->userFeedService->getLatest($this->authentication->getUser(), intval(WiseChatCrypt::decryptFromString($parameters['lastId'])));
					$response['id'] = intval(WiseChatCrypt::decryptFromString($parameters['lastId']));
					break;
				default:
					throw new \Exception('Invalid command');
			}

			$response['parameters'] = $parameters;
			$response['command'] = $command;
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
	 * @param array $parameters
	 * @return Message
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	public function saveMessage($parameters) {
		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		if ($message === null) {
			throw new WiseChatUnauthorizedAccessException('Invalid message');
		}

		// check permissions:
		$deniedEditing = true;
		if ($message->getUserId() > 0 && $message->getUserId() == $this->authentication->getUserIdOrNull()) {
			$deniedEditing = false;
		}
		if ($deniedEditing) {
			$this->checkUserRight('edit_message');
		}

		$this->messagesService->saveRawMessageContent($message, trim($parameters['content']));
		$this->actions->publishAction('refreshMessage', array('id' => $parameters['id']));

		return $message;
	}

	/**
	 * @param array $parameters
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	public function approveMessage($parameters) {
		$this->checkUserRight('approve_message');

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$message = $this->messagesService->getById($message->getId());
		if ($message !== null) {
			$mode = $this->options->getIntegerOption('approving_messages_mode', 1);
			if ($mode === 2) {
				$this->messagesService->replicateHiddenMessage($message);
			} else {
				$this->messagesService->approveById($message->getId());
				$this->actions->publishAction('refreshMessage', array('id' => $parameters['id']));

				// approve all replies:
				if ($this->options->isOptionEnabled('enable_reply_to_messages', true)) {
					$replies = $this->messagesDAO->getAllRepliesToMessage($message);
					foreach ($replies as $reply) {
						$this->actions->publishAction('refreshMessage', array('id' => $this->clientSide->encryptMessageId($reply->getId())));
					}
				}
			}
		}
	}

	/**
	 * Endpoint that prepares an image for further upload:
	 * - basic checks
	 * - resizing
	 * - fixing orientation
	 *
	 * @notice GIFs are returned unchanged because of the lack of proper resizing abilities
	 *
	 * @return null
	 */
	public function prepareImageEndpoint() {
		$this->verifyCheckSum();

		try {
			$this->checkBanned();
			$this->checkChatOpen();
			$this->checkUserAuthentication();
			$this->checkUserAuthorization();
			$this->checkUserWriteAuthorization();

			$this->checkPostParams(array('data'));
			$data = $this->getPostParam('data');

			/** @var WiseChatImagesService $imagesService */
			$imagesService = Container::getInstance()->get(WiseChatImagesService::class);
			$decodedImageData = $imagesService->decodePrefixedBase64ImageData($data);
			if ($decodedImageData['mimeType'] == 'image/gif') {
				echo $data;
			} else {
				$preparedImageData = $imagesService->getPreparedImage($decodedImageData['data']);
				echo $imagesService->encodeBase64WithPrefix($preparedImageData, $decodedImageData['mimeType']);
			}
		} catch (WiseChatUnauthorizedAccessException $exception) {
			echo json_encode(array('error' => $exception->getMessage()));
			$this->sendUnauthorizedStatus();
		} catch (Exception $exception) {
			echo json_encode(array('error' => $exception->getMessage()));
			$this->sendBadRequestStatus();
		}

		die();
	}

	/**
	 * @param array $parameters
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	private function deleteMessage($parameters) {
		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);

		$canDeleteOwnMessages = $this->options->isOptionEnabled('enable_delete_own_messages', false) && $message->getUserId() === $this->authentication->getUserIdOrNull();
		if (!$canDeleteOwnMessages) {
			$this->checkUserRight('delete_message');
		}

		$channel = $this->channelsDAO->get($message->getChannelId());
		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$this->messagesService->deleteById($message->getId());
		$this->actions->publishAction('deleteMessage', array('id' => $parameters['id']));
	}

	/**
	 * @param array $parameters
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	private function muteUser(array $parameters) {
		$this->checkUserRight('mute_user');

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$this->userMutesService->muteUserByMessage($message);
	}

	/**
	 * @param array $parameters
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	private function banUser($parameters) {
		$this->checkUserRight('ban_user');

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);
		$this->bansService->banByMessageId($message->getId());
	}

	/**
	 * @param array $parameters
	 * @throws WiseChatUnauthorizedAccessException
	 * @throws Exception
	 */
	public function spamReport($parameters) {
		if (!$this->options->isOptionEnabled('spam_report_enable_all', true)) {
			$this->checkUserRight('spam_report');
		}

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());
		$url = trim($parameters['url']);

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);
		$this->messagesService->reportSpam($channel->getId(), $message->getId(), $url);
	}

	/**
	 * @param $parameters
	 * @return mixed
	 * @throws Exception
	 */
	private function handleSetUserPropertyCommands($parameters) {
		$response = null;
		$property = $parameters['property'];
		$value = $parameters['value'];

		switch ($property) {
			case 'name':
				$userNameLengthLimit = $this->options->getIntegerOption('user_name_length_limit', 25);
				if ($userNameLengthLimit > 0) {
					$value = substr($value, 0, $userNameLengthLimit);
				}
				$response = $this->userService->changeUserName($value);
				break;
			case 'textColor':
				$this->userService->setUserTextColor($value);
				break;
			case 'emailNotifications':
				$this->userService->setProperty('disableNotifications', $value === 'false');
				$user = $this->authentication->getUser();
				$response = $user->getData();
				break;
			default:
				$this->userSettingsDAO->setSetting($property, $value, $this->authentication->getUser());
		}

		return $response;
	}

	private function reactToMessage($parameters) {
		//$this->checkUserRight('react_to_message'); TODO

		$message = $this->clientSide->getMessageOrThrowException($parameters['id']);
		$channel = $this->channelsDAO->get($message->getChannelId());

		$this->checkChannel($channel);
		$this->checkChannelAuthorization($channel);

		$this->messageReactionsService->toggleReaction($message, intval($parameters['reactionId']));
		$this->actions->publishAction('refreshMessageReactionsCounters', array(
			'id' => $parameters['id'],
			'channel' => array('id' => $parameters['channel']['id']),
			'reactions' => $this->messageReactionsService->getReactionsAsPlainArray($message, true, false)
		));
	}

	private function logOff($parameters) {
		$this->authentication->dropAuthentication();
	}

}