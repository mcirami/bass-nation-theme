<?php

namespace Kainex\WiseChatPro\Services;

use Exception;
use Kainex\WiseChatPro\DAO\Criteria\WiseChatMessagesCriteria;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelsDAO;
use Kainex\WiseChatPro\DAO\WiseChatMessagesDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\Model\WiseChatUser;
use Kainex\WiseChatPro\Rendering\Filters\PostReversed\WiseChatPostReversedFilter;
use Kainex\WiseChatPro\Rendering\Filters\Pre\WiseChatFilter;
use Kainex\WiseChatPro\Rendering\Filters\Pre\WiseChatLinksPreFilter;
use Kainex\WiseChatPro\Rendering\Filters\WiseChatShortcodeConstructor;
use Kainex\WiseChatPro\Services\ClientSide\WiseChatClientSide;
use Kainex\WiseChatPro\Services\Message\WiseChatTextProcessing;
use Kainex\WiseChatPro\Services\User\WiseChatAbuses;
use Kainex\WiseChatPro\Services\User\WiseChatActions;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\WiseChatCrypt;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * WiseChat messages services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMessagesService {

	/**
	 * @var WiseChatChannelsService
	 */
	private $channelsService;

	/**
	 * @var WiseChatClientSide
	 */
	private $clientSide;

	/**
	 * @var WiseChatUsersDAO
	 */
	private $usersDAO;

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	* @var WiseChatMessagesDAO
	*/
	private $messagesDAO;

	/**
	 * @var WiseChatAttachmentsService
	 */
	private $attachmentsService;

	/**
	 * @var WiseChatImagesService
	 */
	private $imagesService;

	/**
	 * @var WiseChatAbuses
	 */
	private $abuses;

	/**
	 * @var UserMutesService
	 */
	private $userMutesService;

	/**
	 * @var WiseChatNotificationsService
	 */
	private $notificationsService;

	/**
	 * @var WiseChatUserNotificationsService
	 */
	private $userNotificationsService;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	* @var WiseChatOptions
	*/
	private $options;

	/** @var WiseChatChannelsDAO */
	private $channelsDAO;

	/** @var WiseChatFilterChain $filterChain */
	private $filterChain;

	/** @var WiseChatLinksPreFilter $linksPreFilter */
	private $linksPreFilter;

	/** @var WiseChatPostReversedFilter $filterReversed */
	private $filterReversed;

	/**
	 * @param WiseChatChannelsService $channelsService
	 * @param WiseChatClientSide $clientSide
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatActions $actions
	 * @param WiseChatMessagesDAO $messagesDAO
	 * @param WiseChatAttachmentsService $attachmentsService
	 * @param WiseChatImagesService $imagesService
	 * @param WiseChatAbuses $abuses
	 * @param UserMutesService $userMutesService
	 * @param WiseChatNotificationsService $notificationsService
	 * @param WiseChatUserNotificationsService $userNotificationsService
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatOptions $options
	 * @param WiseChatChannelsDAO $channelsDAO
	 * @param WiseChatFilterChain $filterChain
	 * @param WiseChatLinksPreFilter $linksPreFilter
	 * @param WiseChatPostReversedFilter $filterReversed
	 */
	public function __construct(WiseChatChannelsService $channelsService, WiseChatClientSide $clientSide, WiseChatUsersDAO $usersDAO, WiseChatActions $actions, WiseChatMessagesDAO $messagesDAO, WiseChatAttachmentsService $attachmentsService, WiseChatImagesService $imagesService, WiseChatAbuses $abuses, UserMutesService $userMutesService, WiseChatNotificationsService $notificationsService, WiseChatUserNotificationsService $userNotificationsService, WiseChatAuthentication $authentication, WiseChatOptions $options, WiseChatChannelsDAO $channelsDAO, WiseChatFilterChain $filterChain, WiseChatLinksPreFilter $linksPreFilter, WiseChatPostReversedFilter $filterReversed) {
		$this->channelsService = $channelsService;
		$this->clientSide = $clientSide;
		$this->usersDAO = $usersDAO;
		$this->actions = $actions;
		$this->messagesDAO = $messagesDAO;
		$this->attachmentsService = $attachmentsService;
		$this->imagesService = $imagesService;
		$this->abuses = $abuses;
		$this->userMutesService = $userMutesService;
		$this->notificationsService = $notificationsService;
		$this->userNotificationsService = $userNotificationsService;
		$this->authentication = $authentication;
		$this->options = $options;
		$this->channelsDAO = $channelsDAO;
		$this->filterChain = $filterChain;
		$this->linksPreFilter = $linksPreFilter;
		$this->filterReversed = $filterReversed;
	}

	/**
	* Maintenance actions performed at start-up.
	*/
	public function startUpMaintenance() {
		$this->deleteOldMessages();
	}

	/**
	 * Maintenance actions performed periodically.
	 *
	 * @throws Exception
	 */
	public function periodicMaintenance() {
		$this->deleteOldMessages();
	}

	/**
	 * Publishes a message in the given channel of the chat and returns it.
	 *
	 * @param WiseChatUser $user Author of the message
	 * @param Channel $channel A channel to publish in
	 * @param string $text Content of the message
	 * @param array $attachments Array of attachments (only single image is supported)
	 * @param boolean $isAdmin Indicates whether to mark the message as admin-owned
	 * @param WiseChatUser|null $recipient The recipient of the message
	 * @param Message|null $replyToMessage
	 * @param array $options
	 * @return Message|null
	 * @throws Exception On validation error
	 */
	public function addMessage($user, $channel, $text, $attachments, $isAdmin = false, $recipient = null, $replyToMessage = null, $options = array()) {
		$text = trim($text);
		$filteredMessage = $text;

		// basic validation:
		if ($user === null) {
			throw new Exception('User cannot be null');
		}
		if ($channel === null) {
			throw new Exception('Channel cannot be null');
		}

		// check if the user has been muted
		if ($user->getId() > 0 && $this->authentication->getSystemUser()->getId() != $user->getId() && $this->userMutesService->isUserMuted($user)) {
			throw new Exception(__('You are not allowed to send messages. You have been muted.', 'wise-chat-pro'));
		}

        // use bad words filtering:
        if ($this->options->isOptionEnabled('filter_bad_words')) {
            $badWordsFilterReplacement = $this->options->getOption('bad_words_replacement_text');
            $filteredMessage = WiseChatFilter::filter(
                $filteredMessage,
                strlen($badWordsFilterReplacement) > 0 ? $badWordsFilterReplacement : null
            );
        }

		// auto-mute feature:
		if ($this->options->isOptionEnabled('enable_automute') && $filteredMessage != $text) {
			$counter = $this->abuses->incrementAndGetAbusesCounter();
			$threshold = $this->options->getIntegerOption('automute_threshold', 3);
			if ($counter >= $threshold && $threshold > 0) {
				$this->userMutesService->muteUser($user, $this->options->getIntegerOption('automute_duration', 1440));
				$this->abuses->clearAbusesCounter();
			}
		}

		// flood prevention feature:
		if ($this->options->isOptionEnabled('enable_flood_control')) {
			$floodControlThreshold = $this->options->getIntegerOption('flood_control_threshold', 200);
			$floodControlTimeFrame = $this->options->getIntegerOption('flood_control_time_frame', 1);
			if ($floodControlThreshold > 0 && $floodControlTimeFrame > 0) {
				$messagesAmount = $this->messagesDAO->getNumberByCriteria(
					WiseChatMessagesCriteria::build()
						->setIp($user->getIp())
						->setMinimumTime(time() - $floodControlTimeFrame * 60)
				);
				if ($messagesAmount > $floodControlThreshold) {
					$this->userMutesService->muteUser($user, $this->options->getIntegerOption('flood_control_mute_duration', 1440));
				}
			}
		}

		// go through the custom filters:
		$filteredMessage = $this->filterChain->filter($filteredMessage);

		// cut the message:
		if (!array_key_exists('disableCrop', $options)) {
			$filteredMessage = WiseChatTextProcessing::cutMessageText($filteredMessage, $this->options->getIntegerOption('message_max_length', 100));
		}

		// convert images and links into proper shortcodes and download images (if enabled):
		if (!array_key_exists('disableFilters', $options)) {
			$filteredMessage = $this->linksPreFilter->filter(
				$filteredMessage,
				$this->options->isOptionEnabled('allow_post_images'),
				$this->options->isOptionEnabled('enable_youtube')
			);
		}

		$message = new Message();
		$message->setTime(time());
		$message->setAdmin($isAdmin);
		$message->setUserName($user->getName());
		$message->setUserId($user->getId());
		$message->setAvatarUrl($this->getUserAvatar($user));
		$message->setText($filteredMessage);
		$message->setChannelId($channel->getId());
		$message->setIp($user->getIp() ? $user->getIp() : '');
		if ($user->getWordPressId() !== null) {
			$message->setWordPressUserId($user->getWordPressId());
		}
		if ($recipient !== null) {
			$message->setRecipientId($recipient->getId());
		}
		$message->setHidden($this->checkNewMessagesHidden());

		if ($this->options->isOptionEnabled('enable_reply_to_messages', true) && $replyToMessage !== null) {
			$message->setReplyToMessageId($replyToMessage->getId());
		}

		/**
		 * Filters a message just before it is inserted into the database.
		 *
		 * @param Message $message A fully prepared message object. Depending on enabled features:
		 *                                    - links are converted to internal tags: [link]
		 *                                    - image links are downloaded to Media Library and converted to internal tags: [img]
		 *                                    - the text of the message is trimmed and filtered using the bad words dictionary
		 *                                    - YouTube links are converted to internal tags: [youtube]
		 *                                    - images or files attached to the message via the uploader are converted to internal tags: [img] or [attachment]
		 * @param string $text A raw text of the message typed by chat user
		 *@since 2.3.2
		 *
		 */
		$message = apply_filters('wc_insert_message', $message, $text);

		// save the attachment and include it into the message:
		$attachmentIds = array();
		if (count($attachments) > 0) {
			list($attachmentShortcode, $attachmentIds) = $this->saveAttachments($channel, $attachments);
			$message->setText($message->getText() . $attachmentShortcode);
		}

		$message = $this->messagesDAO->save($message);

		// mark attachments created by the links pre-filter:
		$createdAttachments = $this->linksPreFilter->getCreatedAttachments();
		if (count($createdAttachments) > 0) {
			$this->attachmentsService->markAttachmentsWithDetails($createdAttachments, $channel->getName(), $message->getId());
		}

		// mark attachments uploaded together with the message:
		if (count($attachmentIds) > 0) {
			$this->attachmentsService->markAttachmentsWithDetails($attachmentIds, $channel->getName(), $message->getId());
		}

		// send public notifications:
		if ($message->getRecipientId() === null) {
			$this->notificationsService->send($message, $channel);
		} else {
			$this->userNotificationsService->send($message, $channel);
		}

		/**
 		 * Fires once a message has been saved.
 		 *
 		 * @param Message $message A message object.
 		 * @param array $attachmentIds Attachment IDs of the uploaded images or files
 		 *@since 2.3.2
 		 *
 		 */
 		do_action("wc_message_inserted", $message, $attachmentIds);

		return $message;
	}

	/**
	 * Saves message's content.
	 *
	 * @param Message $message
	 * @param string $rawHTML Raw HTML received from user
	 * @throws \Exception
	 */
	public function saveRawMessageContent($message, $rawHTML) {
		$messageMaxLength = $this->options->getIntegerOption('message_max_length', 100);
		$rawHTML = trim($rawHTML);
		$originalText = $message->getText();
		$newText = '';
		try {
			if (strlen($rawHTML) > 0) {
				$count = $this->filterReversed->getTextCharactersCount($rawHTML);
				if ($count > $messageMaxLength) {
					throw new \Exception('Number of characters exceeded');
				}

				$newText = $this->filterReversed->filtersReverse($rawHTML);
			}

			// update the message:
			$message->setText($newText);
			$this->messagesDAO->save($message);

			/**
			 * Fires once a message has been updated.
			 *
			 * @param Message $message A message object.
			 *@since 2.3.2
			 *
			 */
			do_action("wc_message_updated", $message);

		} catch (\Exception $e) {
			throw new \Exception("Could not save the raw message content (".$e->getMessage().").");
		}
	}

	/**
	 * Checks if the current user's messages have to be hidden.
	 *
	 * @return boolean
	 */
	private function checkNewMessagesHidden() {
		if ($this->options->isOptionEnabled('new_messages_hidden', false)) {

			$wpUser = $this->usersDAO->getCurrentWpUser();
			if ($wpUser !== null) {
				$targetRoles = (array) $this->options->getOption("no_hidden_messages_roles", 'administrator');
				if ((is_array($wpUser->roles) && count(array_intersect($targetRoles, $wpUser->roles)) > 0)) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Saves attachments in the Media Library and attaches them to the end of the message.
	 *
	 * @param Channel $channel
	 * @param array $attachments Array of attachments
	 *
	 * @return array Array consisting of the two elements: a shortcode representing the attachments and array of IDs of created attachments
	 */
	private function saveAttachments($channel, $attachments) {
		if (!is_array($attachments) || count($attachments) === 0) {
			return array(null, array());
		}

		$channelConfiguration = array_merge(WiseChatChannelsDAO::DEFAULT_CONFIGURATION, $channel->getConfiguration() ? $channel->getConfiguration() : []);
		$firstAttachment = $attachments[0];
		$data = $firstAttachment['data'];
		$data = substr($data, strpos($data, ",") + 1);
		$decodedData = base64_decode($data);

		$attachmentShortcode = null;
		$attachmentIds = array();
		if ($this->options->isOptionEnabled('enable_images_uploader') && $channelConfiguration['enableImages'] !== false && $firstAttachment['type'] === 'image') {
			$image = $this->imagesService->saveImage($decodedData);
			if (is_array($image)) {
				$attachmentShortcode = ' '.WiseChatShortcodeConstructor::getImageShortcode($image['id'], $image['image'], $image['image-th'], '_');
				$attachmentIds = array($image['id']);
			}
		}

		if ($this->options->isOptionEnabled('enable_attachments_uploader') && $channelConfiguration['enableAttachments'] !== false && $firstAttachment['type'] === 'file') {
			$fileName = $firstAttachment['name'];
			$file = $this->attachmentsService->saveAttachment($fileName, $decodedData, $channel->getName());
			if (is_array($file)) {
				$attachmentShortcode = ' '.WiseChatShortcodeConstructor::getAttachmentShortcode($file['id'], $file['file'], $fileName);
				$attachmentIds = array($file['id']);
			}
		}

		if ($firstAttachment['type'] === 'mp3' && $channelConfiguration['enableVoiceMessages'] !== false) {
			$fileName = $firstAttachment['name'].'.mp3';
			$file = $this->attachmentsService->saveAttachment($fileName, $decodedData, $channel->getName());
			if (is_array($file)) {
				$attachmentShortcode = ' '.WiseChatShortcodeConstructor::getSoundShortcode($file['id'], $file['file'], $fileName);
				$attachmentIds = array($file['id']);
			}
		}

		return array($attachmentShortcode, $attachmentIds);
	}

	/**
	 * @param $clientChannelId Client-side channel ID
	 * @param $beforeClientMessageId Client-side message ID
	 * @return Message[]
	 * @throws Exception
	 */
	public function getMessagesOfChannel($clientChannelId, $beforeClientMessageId = null) {
		$channelTypeAndId = WiseChatCrypt::decryptFromString($clientChannelId);
		if ($channelTypeAndId === null) {
			throw new Exception('Invalid channel');
		}

		if (strpos($channelTypeAndId, 'c|') !== false) {
			$publicChannelId = intval(str_replace('c|', '', $channelTypeAndId));
			$channel = $this->channelsDAO->get($publicChannelId);
			if (!$channel) {
				throw new Exception('Unknown channel '.$clientChannelId);
			}

			if (!$this->channelsService->hasPublicChannelAccess($channel)) {
				throw new Exception('Public channel access denied');
			}

			$criteria = new WiseChatMessagesCriteria();
			$criteria->setChannelIDs(array($channel->getId()));
			$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
			$criteria->setIncludeOnlyPrivateMessages(false);
			$criteria->setLimit($this->options->getIntegerOption('messages_preload_limit', 20));
			$criteria->setOrderMode(WiseChatMessagesCriteria::ORDER_ASCENDING);

			if ($beforeClientMessageId) {
				$message = $this->clientSide->getMessageOrThrowException($beforeClientMessageId);
				$criteria->setMaximumMessageId($message->getId());
			}

			return $this->messagesDAO->getAllByCriteria($criteria);
		} else if (strpos($channelTypeAndId, 'd|') !== false) {
			if (!$this->options->isOptionEnabled('enable_private_messages')) {
				throw new Exception('Direct channel access denied');
			}
			$userId = intval(preg_replace('/^d\|/', '' , $channelTypeAndId));
			$channel = $this->channelsService->getDirectChannel();

			$criteria = new WiseChatMessagesCriteria();
			$criteria->setChannelIDs(array($channel->getId()));
			$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
			$criteria->setIncludeOnlyPrivateMessages(true);
			$criteria->setDirectChatters(array($this->authentication->getUserIdOrNull(), $userId));
			$criteria->setLimit($this->options->getIntegerOption('private_messages_preload_limit', 20));
			$criteria->setOrderMode(WiseChatMessagesCriteria::ORDER_ASCENDING);

			if ($beforeClientMessageId) {
				$message = $this->clientSide->getMessageOrThrowException($beforeClientMessageId);
				$criteria->setMaximumMessageId($message->getId());
			}

			return $this->messagesDAO->getAllByCriteria($criteria);
		} else {
			throw new Exception('Unknown channel');
		}

	}

	/**
	 * Returns all messages from the given channel and (optionally) beginning from the given offset.
	 * Limit and admin messages inclusion are taken from the plugin's options.
	 *
	 * @param array $channelNames Channels
	 * @param integer $fromId Begin from specific message ID
	 * @param integer|null $privateMessagesSenderOrRecipientId ID of the user that is either sender or recipient of private messages
	 *
	 * @return Message[]
	 * @throws Exception
	 */
	public function getAllByChannelNamesAndOffset($channelNames, $fromId = null, $privateMessagesSenderOrRecipientId = null) {
		$channels = $this->channelsService->getByNames($channelNames);

		$criteria = new WiseChatMessagesCriteria();
		$criteria->setChannelIDs(array_map(function($channel) { return $channel->getId(); }, $channels));
		$criteria->setOffsetId($fromId);
		$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
		$criteria->setLimit($this->options->getIntegerOption('messages_limit', 100));
		$criteria->setOrderMode(WiseChatMessagesCriteria::ORDER_ASCENDING);
		if ($privateMessagesSenderOrRecipientId !== null) {
			$criteria->setRecipientOrSenderId(intval($privateMessagesSenderOrRecipientId));
		}

		return $this->messagesDAO->getAllByCriteria($criteria);
	}

	/**
	 * Returns all messages from the given channel.
	 * Limit and admin messages inclusion are taken from the plugin's options.
	 *
	 * @param integer $channelID
	 *
	 * @return Message[]
	 * @throws Exception
	 */
	public function getAllPublicByChannelId($channelID) {
		$criteria = new WiseChatMessagesCriteria();
		$criteria->setChannelIDs(array($channelID));
		$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
		$criteria->setIncludeOnlyPrivateMessages(false);
		$criteria->setLimit($this->options->getIntegerOption('messages_limit', 100));
		$criteria->setOrderMode(WiseChatMessagesCriteria::ORDER_ASCENDING);

		return $this->messagesDAO->getAllByCriteria($criteria);
	}

	/**
	 * Returns all private messages from the given channel.
	 * Limit and admin messages inclusion are taken from the plugin's options.
	 *
	 * @param integer $privateMessagesSenderOrRecipientId ID of the user that is either sender or recipient of private messages
	 *
	 * @return Message[]
	 * @throws Exception
	 */
	public function getAllPrivateByUser($privateMessagesSenderOrRecipientId) {
		$channel = $this->channelsService->getDirectChannel();

		$criteria = new WiseChatMessagesCriteria();
		$criteria->setChannelIDs(array($channel->getId()));
		$criteria->setIncludeAdminMessages($this->usersDAO->isWpUserAdminLogged());
		$criteria->setIncludeOnlyPrivateMessages(true);
		$criteria->setLimit($this->options->getIntegerOption('private_messages_limit', 200));
		$criteria->setOrderMode(WiseChatMessagesCriteria::ORDER_ASCENDING);
		if ($privateMessagesSenderOrRecipientId !== null) {
			$criteria->setRecipientOrSenderId(intval($privateMessagesSenderOrRecipientId));
		}

		return $this->messagesDAO->getAllByCriteria($criteria);
	}

	/**
	 * Returns all messages from the given channel without limit and with the default order.
	 * Admin messages are not returned.
	 *
	 * @param integer $channelID
	 *
	 * @return Message[]
	 */
	public function getAllByChannelID($channelID) {
		return $this->messagesDAO->getAllByCriteria(WiseChatMessagesCriteria::build()->setChannelIDs(array($channelID)));
	}

	/**
	 * Returns all private messages from the given channel.
	 *
	 * @param integer $channelID Name of the channel
	 *
	 * @return Message[]
	 */
	public function getAllPrivateByChannelID($channelID) {
		$criteria = new WiseChatMessagesCriteria();
		$criteria->setChannelIDs(array($channelID));
		$criteria->setIncludeAdminMessages(false);
		$criteria->setIncludeOnlyPrivateMessages(true);

		return $this->messagesDAO->getAllByCriteria($criteria);
	}

	/**
	 * Returns message by ID.
	 *
	 * @param integer $id
	 * @param bool $populateUser
	 * @return Message|null
	 */
	public function getById($id, $populateUser = false) {
		$message = $this->messagesDAO->get($id);
		if (!$message) {
			return null;
		}

		if ($populateUser) {
			$message->setUser($this->usersDAO->get($message->getUserId()));
		}

		return $message;
	}

	/**
	 * Returns number of messages in the channel.
	 *
	 * @param integer $channelID
	 *
	 * @return integer
	 */
	public function getNumberByChannelId($channelID) {
		return $this->messagesDAO->getNumberByCriteria(WiseChatMessagesCriteria::build()->setChannelIDs(array($channelID)));
	}

	/**
	 * Deletes message by ID.
	 * Images connected to the message (WordPress Media Library attachments) are also deleted.
	 *
	 * @param integer $id
	 */
	public function deleteById($id) {
		$message = $this->messagesDAO->get($id);
		if ($message !== null) {
			$this->messagesDAO->deleteById($id);
			$this->attachmentsService->deleteAttachmentsByMessageIds(array($id));

			/**
			 * Fires once a message has been deleted.
			 *
			 * @param Message $message A deleted message object.
			 *@since 2.3.2
			 *
			 */
			do_action("wc_message_deleted", $message);
		}
	}

	/**
	 * Approves message by ID.
	 *
	 * @param integer $id
	 */
	public function approveById($id) {
		$this->messagesDAO->unhideById($id);

		$message = $this->messagesDAO->get($id);
		/**
		 * Fires once a message has been approved.
		 *
		 * @param Message $message A message object.
		 *@since 2.3.2
		 *
		 */
		do_action("wc_message_approved", $message);
	}

	/**
	 * Replicates message and makes it visible (not hidden).
	 *
	 * @param Message $message
	 * @throws Exception
	 */
	public function replicateHiddenMessage($message) {
		$clone = $message->getClone();
		$clone->setTime(time());
		$clone->setHidden(false);
		$this->messagesDAO->save($clone);

		$messagesIds = array();
		if ($this->options->isOptionEnabled('enable_reply_to_messages', true)) {
			$replies = $this->messagesDAO->getAllRepliesToMessage($message);
			foreach ($replies as $reply) {
				$replyClone = $reply->getClone();
				$replyClone->setTime(time());
				$replyClone->setHidden(false);
				$replyClone->setReplyToMessageId($clone->getId());
				$this->messagesDAO->save($replyClone);

				$messagesIds[] = $reply->getId();
				$this->messagesDAO->deleteById($reply->getId());
			}
		}

		$messagesIds[] = $message->getId();
		$this->messagesDAO->deleteById($message->getId());

		$this->actions->publishAction('deleteMessages', array('ids' => $this->clientSide->encryptMessageIds($messagesIds)));
	}

	/**
	 * Deletes all messages (in all channels).
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 */
	public function deleteAll() {
		$this->messagesDAO->deleteAllByCriteria(WiseChatMessagesCriteria::build()->setIncludeAdminMessages(true));
		$this->messagesDAO->deleteAllByCriteria(WiseChatMessagesCriteria::build()->setIncludeAdminMessages(true)->setIncludeOnlyPrivateMessages(true));
		$this->attachmentsService->deleteAllAttachments();
	}

	/**
	 * Deletes all messages from specified channel.
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 *
	 * @param integer $channelID
	 * @throws Exception
	 */
	public function deleteByChannel($channelID) {
		$this->messagesDAO->deleteAllByCriteria(
            WiseChatMessagesCriteria::build()
                ->setChannelIDs(array($channelID))
                ->setIncludeAdminMessages(true)
        );
		$this->messagesDAO->deleteAllByCriteria(
			WiseChatMessagesCriteria::build()
				->setChannelIDs(array($channelID))
				->setIncludeAdminMessages(true)
				->setIncludeOnlyPrivateMessages(true)
		);
		$this->attachmentsService->deleteAttachmentsByChannel($channelID);
	}

	/**
	 * Sends a notification e-mail reporting spam message.
	 *
	 * @param integer $channelId
	 * @param integer $messageId
	 * @param string $url
	 */
	public function reportSpam($channelId, $messageId, $url) {
		$recipient = $this->options->getOption('spam_report_recipient', get_option('admin_email'));
		$subject = $this->options->getOption('spam_report_subject', '[Wise Chat] Spam Report');
		$contentDefaultTemplate = "Wise Chat Spam Report\n\n".
			'Channel: {channel}'."\n".
			'Message: {message}'."\n".
			'Posted by: {message-user}'."\n".
			'Posted from IP: {message-user-ip}'."\n\n".
			"--\n".
			'This e-mail was sent by {report-user} from {url}'."\n".
			'{report-user-ip}';
		$content = $this->options->getOption('spam_report_content', $contentDefaultTemplate);
		if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
			return;
		}
		$currentUser = $this->authentication->getUser();
		$message = $this->messagesDAO->get($messageId);
		if ($message === null || $currentUser === null) {
			return;
		}
		$channel = $this->channelsService->get($message->getChannelId());
		if (!$channel) {
			return;
		}
		$variables = array(
			'url' => $url,
			'channel' => $channel->getName(),
			'message' => $message->getText(),
			'message-user' => $message->getUserName(),
			'message-user-ip' => $message->getIp(),
			'report-user' => $currentUser->getName(),
			'report-user-ip' => $currentUser->getIp()
		);
		foreach ($variables as $key => $variable) {
			$content = str_replace(array('${'.$key.'}', '{'.$key.'}'), $variable, $content);
		}
		wp_mail($recipient, $subject, $content);

		/**
		 * Fires once a spam message has been reported.
		 *
		 * @param Message $message A reported spam message
		 * @param string $url URL of the chat page
		 *@since 2.3.2
		 *
		 */
		do_action("wc_spam_reported", $message, $url);
	}

	/**
	 * Deletes old messages if auto-remove option is on.
	 * Images connected to the messages (WordPress Media Library attachments) are also deleted.
	 *
	 * @throws Exception
	 */
	private function deleteOldMessages() {
		$minutesThreshold = $this->options->getIntegerOption('auto_clean_after', 0);
		$minutesThresholdOfDirect = $this->options->getIntegerOption('auto_clean_direct_after', 0);

		$messagesIds = array();
		if ($minutesThreshold > 0) {
			$channels = $this->channelsDAO->getByNames((array) $this->options->getOption('channel'));

			$criteria = new WiseChatMessagesCriteria();
			$criteria->setChannelIDs(array_map(function($channel) { return $channel->getId(); }, $channels));
			$criteria->setIncludeAdminMessages(true);
			$criteria->setMaximumTime(time() - $minutesThreshold * 60);
			$criteria->setIncludeOnlyPrivateMessages(false);
			$messages = $this->messagesDAO->getAllByCriteria($criteria);
			foreach ($messages as $message) {
				$messagesIds[] = $message->getId();
			}
			$this->messagesDAO->deleteAllByCriteria($criteria);
		}
		if ($minutesThresholdOfDirect > 0) {
			$criteria = new WiseChatMessagesCriteria();
			$criteria->setIncludeAdminMessages(true);
			$criteria->setMaximumTime(time() - $minutesThresholdOfDirect * 60);
			$criteria->setIncludeOnlyPrivateMessages(true);
			$messages = $this->messagesDAO->getAllByCriteria($criteria);
			foreach ($messages as $message) {
				$messagesIds[] = $message->getId();
			}
			$this->messagesDAO->deleteAllByCriteria($criteria);
		}

		if (count($messagesIds) > 0) {
			$this->attachmentsService->deleteAttachmentsByMessageIds($messagesIds);
			$this->actions->publishAction('deleteMessages', array('ids' => $this->clientSide->encryptMessageIds($messagesIds)));
		}
	}

	/**
	 * @param WiseChatUser $user
	 *
	 * @return string|null
	 */
	private function getUserAvatar($user) {
		$imageSrc = null;

		if ($user !== null && $user->getExternalId()) {
			$imageSrc = $user->getAvatarUrl();
		} else if ($user !== null && $user->getWordPressId() !== null) {
			$imageTag = get_avatar($user->getWordPressId());
			if ($imageTag === false) {
				$imageSrc = $this->options->getIconsURL().'user.png';
			} else {
				$doc = new \DOMDocument();
				$doc->loadHTML($imageTag);
				$imageTags = $doc->getElementsByTagName('img');
				foreach ($imageTags as $tag) {
					$imageSrc = $tag->getAttribute('src');
				}
			}
		} else {
			$imageSrc = $this->options->getIconsURL().'user.png';
		}

		return $imageSrc;
	}
}