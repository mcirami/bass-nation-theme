<?php

namespace Kainex\WiseChatPro\Services\ClientSide;

use Exception;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelsDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Model\Channel\ChannelMember;
use Kainex\WiseChatPro\Model\Channel\ChannelUser;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\Model\WiseChatUser;
use Kainex\WiseChatPro\Rendering\WiseChatUITemplates;
use Kainex\WiseChatPro\Services\Message\WiseChatMessageReactionsService;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\Services\User\WiseChatUserService;
use Kainex\WiseChatPro\Services\WiseChatChannelsService;
use Kainex\WiseChatPro\Services\WiseChatMessagesService;
use Kainex\WiseChatPro\Services\WiseChatPrivateMessagesRulesService;
use Kainex\WiseChatPro\Services\WiseChatService;
use Kainex\WiseChatPro\WiseChatCrypt;
use Kainex\WiseChatPro\WiseChatOptions;
use WP_User;

/**
 * WiseChat client side utilities.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatClientSide {

	/**
	 * @var WiseChatOptions
	 */
	protected $options;

	/**
	 * @var WiseChatMessagesService
	 */
	private $messagesService;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	 * @var WiseChatUserService
	 */
	private $userService;

	/**
	 * @var WiseChatPrivateMessagesRulesService
	 */
	private $privateMessagesRulesService;

	/**
	 * @var WiseChatUITemplates
	 */
	protected $uiTemplates;

	/**
	 * @var WiseChatChannelsDAO
	 */
	protected $channelsDAO;

	/**
	 * @var WiseChatChannelsService
	 */
	protected $channelsService;

	/**
	 * @var WiseChatService
	 */
	protected $service;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var WiseChatMessageReactionsService
	 */
	protected $messageReactionsService;

	/**
	 * @var array
	 */
	private $plainDirectChannelsCache = array();

	/**
	 * @param WiseChatOptions $options
	 * @param WiseChatMessagesService $messagesService
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatUserService $userService
	 * @param WiseChatPrivateMessagesRulesService $privateMessagesRulesService
	 * @param WiseChatUITemplates $uiTemplates
	 * @param WiseChatChannelsDAO $channelsDAO
	 * @param WiseChatChannelsService $channelsService
	 * @param WiseChatService $service
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatMessageReactionsService $messageReactionsService
	 */
	public function __construct(WiseChatOptions $options, WiseChatMessagesService $messagesService, WiseChatAuthentication $authentication, WiseChatUserService $userService, WiseChatPrivateMessagesRulesService $privateMessagesRulesService, WiseChatUITemplates $uiTemplates, WiseChatChannelsDAO $channelsDAO, WiseChatChannelsService $channelsService, WiseChatService $service, WiseChatUsersDAO $usersDAO, WiseChatMessageReactionsService $messageReactionsService) {
		$this->options = $options;
		$this->messagesService = $messagesService;
		$this->authentication = $authentication;
		$this->userService = $userService;
		$this->privateMessagesRulesService = $privateMessagesRulesService;
		$this->uiTemplates = $uiTemplates;
		$this->channelsDAO = $channelsDAO;
		$this->channelsService = $channelsService;
		$this->service = $service;
		$this->usersDAO = $usersDAO;
		$this->messageReactionsService = $messageReactionsService;
	}

	/**
	 * @param Message $message
	 * @param integer $channelId
	 * @param string $channelName
	 * @param array $attributes
	 * @return array
	 */
	public function toPlainMessage($message, $channelId, $channelName, $attributes = array()) {
		// check if the message cannot be exposed to the user:
		if (!$this->userService->isUserAllowedToSeeTheContentOfMessage($message)) {
			return array(
				'id' => $this->encryptMessageId($message->getId()),
				'locked' => true,
				'channel' => array(
					'id' => $channelId
				)
			);
		}

		$replyToMessage = $this->options->isOptionEnabled('enable_reply_to_messages', true) && $message->getReplyToMessageId() > 0
			? $this->messagesService->getById($message->getReplyToMessageId(), true)
			: null;

		// if it is a reply to a pending message:
		if ($replyToMessage && !$this->userService->isUserAllowedToSeeTheContentOfMessage($replyToMessage)) {
			return array(
				'id' => $this->encryptMessageId($replyToMessage->getId()),
				'locked' => true,
				'channel' => array(
					'id' => $channelId
				)
			);
		}

		$textColorAffectedParts = (array)$this->options->getOption("text_color_parts", array('message', 'messageUserName'));
		$classes = '';
		$wpUser = $this->usersDAO->getWpUserByID($message->getWordPressUserId());
		if ($this->options->isOptionEnabled('css_classes_for_user_roles', false)) {
			$classes = $this->userService->getCssClassesForUserRoles($message->getUser(), $wpUser);
		}

		$isAllowed = false;
		if ($this->options->isOptionEnabled('enable_private_messages', false) || !$this->options->isOptionEnabled('users_list_linking', false)) {
			if ($message->getRecipientId() > 0) {
				$directUserId = $this->authentication->getUserIdOrNull() === $message->getRecipientId() ? $message->getUserId() : $message->getRecipientId();
				$directUser = $this->usersDAO->get($directUserId);
				if ($directUser !== null) {
					$isAllowed = $this->privateMessagesRulesService->isMessageDeliveryAllowed($this->authentication->getUser(), $directUser);
				}
			} else {
				$isAllowed = true;
			}
		}

		$channelName = $message->getRecipientId() > 0
			? ($this->authentication->getUserIdOrNull() === $message->getRecipientId()
				? ($message->getUser() ? $message->getUser()->getName() : 'Unknown User')
				: ($message->getRecipient() ? $message->getRecipient()->getName() : 'Unknown User')
			)
			: $channelName;

		$messagePlain = array(
			'id' => $this->encryptMessageId($message->getId()),
			'own' => $message->getUserId() === $this->authentication->getUserIdOrNull(),
			'text' => $message->getText(),
			'channel' => array(
				'id' => $channelId,
				'name' => $channelName,
				'type' => $message->getRecipientId() > 0 ? 'direct' : 'public',
				'readOnly' => !$isAllowed,
				'avatar' => $this->options->getIconsURL() . 'public-channel.png',
				'online' => array_key_exists('live', $attributes) && $attributes['live'] === true
			),
			'color' => in_array('message', $textColorAffectedParts) ? $this->userService->getUserTextColor($message->getUser()) : null,
			'cssClasses' => $classes,
			'timeUTC' => gmdate('c', $message->getTime()),
			'sortKey' => $message->getTime().$message->getId(),
			'awaitingApproval' => $this->options->isOptionEnabled('new_messages_hidden', false) && $message->isHidden(),
			'locked' => false,
			'sender' => $this->getMessageSender($message, $wpUser),

			'quoted' => $replyToMessage !== null
				? $this->toPlainMessage($replyToMessage, $channelId, $channelName)
				: null
		);

		// append message reactions:
		if ($this->messageReactionsService->isEnabled()) {
			$messagePlain['reactions'] = $this->messageReactionsService->getReactionsAsPlainArray($message);
		}

		$messagePlain = array_merge($messagePlain, $attributes);

		return $messagePlain;
	}

	private function getMessageSender($message, $wpUser) {
		$textColorAffectedParts = (array) $this->options->getOption("text_color_parts", array('message', 'messageUserName'));
		$isCurrent = $this->authentication->getUser()->getId() === $message->getUserId();

		$details = array(
			'id' => $this->encryptUserId($message->getUserId()),
			'name' => $message->getUserName(),
			'source' => $wpUser !== null ? 'w' : 'a',
			'current' => $isCurrent,
			'color' => in_array('messageUserName', $textColorAffectedParts) ? $this->userService->getUserTextColor($message->getUser()) : null,
			'profileUrl' => $this->options->getIntegerOption('link_wp_user_name', 0) === 1 ? $this->userService->getUserProfileLink($message->getUser(), $message->getUserName(), $message->getWordPressUserId()) : null,
			'avatarUrl' => $this->options->isOptionEnabled('show_avatars', false) ? $this->userService->getUserAvatarFromMessage($message) : null
		);

		if (!$isCurrent && $message->getUser()) {
			$details['channel'] = $this->getUserAsPlainDirectChannel($message->getUser());
		}

		return $details;
	}

	/**
	 * @param $user
	 * @return string
	 */
	public function getUserCacheId($user) {
		return $this->getInstanceId().'_'.WiseChatCrypt::encryptToString($user->getId());
	}

	/**
	 * Get chat's instance ID.
	 *
	 * @return string
	 */
	public function getInstanceId() {
		return sha1(serialize($this->options->getOption('channel')));
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptUserId($id) {
		return WiseChatCrypt::encryptToString($id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptDirectChannelId($id) {
		return WiseChatCrypt::encryptToString('d|'.$id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptPublicChannelId($id) {
		return WiseChatCrypt::encryptToString('c|'.$id);
	}

	/**
	 * @param integer $id
	 * @return string
	 */
	public function encryptMessageId($id) {
		return WiseChatCrypt::encryptToString($id);
	}

	/**
	 * @param integer[] $ids
	 * @return string[]
	 */
	public function encryptMessageIds($ids) {
		return array_map(function($id) {
			return WiseChatCrypt::encryptToString($id);
		}, $ids);
	}

	/**
	 * @param string $encryptedId
	 * @return integer
	 */
	public function decryptMessageId($encryptedId) {
		return intval(WiseChatCrypt::decryptFromString($encryptedId));
	}

	/**
	 * Decrypts the message ID and loads the message.
	 *
	 * @param string $encryptedMessageId
	 * @return Message
	 * @throws Exception If the message does not exist
	 */
	public function getMessageOrThrowException($encryptedMessageId) {
		$message = $this->messagesService->getById($this->decryptMessageId($encryptedMessageId));
		if ($message === null) {
			throw new \Exception('The message does not exist');
		}

		return $message;
	}

	/**
	 * @param string $encryptedChannelId
	 * @return Channel|null
	 * @throws Exception
	 */
	public function getChannelFromEncryptedId($encryptedChannelId) {
		$channelTypeAndId = WiseChatCrypt::decryptFromString($encryptedChannelId);
		if ($channelTypeAndId === null) {
			throw new Exception('Invalid channel');
		}

		if (strpos($channelTypeAndId, 'c|') !== false || is_numeric($channelTypeAndId)) {
			$channel = $this->channelsDAO->get(intval(str_replace('c|', '', $channelTypeAndId)));
			if ($channel && $this->channelsService->isDirect($channel)) {
				throw new Exception('Unknown channel ID');
			}
		} else if (strpos($channelTypeAndId, 'd|') !== false) {
			$channel = $this->channelsService->getDirectChannel();
		} else {
			throw new Exception('Unknown channel');
		}

		return $channel;
	}

	/**
	 * @param ChannelMember[] $members
	 * @return array[]
	 */
	public function channelMembersToPlain($members) {
		$plain = [];

		foreach ($members as $member) {
			$plain[] = [
				'id' => WiseChatCrypt::encryptToString($member->getUserId()),
				'name' => $member->getUser()->getName(),
				'avatarUrl' => $this->userService->getUserAvatar($member->getUser()),
				'type' => $member->getType() === ChannelMember::TYPE_OWNER ? 'Administrator' : 'Member',
				'confirmed' => $member->isConfirmed()
			];
		}

		return $plain;
	}

	/**
	 * Returns plain array representation of channel user object.
	 *
	 * @param ChannelUser $channelUser
	 * @return array
	 */
	public function getChannelUserAsPlainDirectChannel(ChannelUser $channelUser, array $supplementaryData = []) {
		return $this->getUserAsPlainDirectChannel($channelUser->getUser(), array(
			'online' => $channelUser->isActive(),
		), $supplementaryData);
	}

	/**
	 * Outputs public channel as plain array.
	 *
	 * @param Channel $channel
	 * @return array
	 * @throws Exception
	 */
	public function channelToPlain($channel) {
		static $isChatFull = null;

		// TODO: "full" channel convert to "full" chat (including direct channels)
		if ($isChatFull === null) {
			$isChatFull = $this->service->isChatFull();
		}

		return array(
			'id' => $this->encryptPublicChannelId($channel->getId()),
			'readOnly' => !$this->userService->isSendingMessagesAllowed() && !$this->authentication->isAuthenticatedExternally(),
			'type' => $this->getChannelTypeAsText($channel),
			'name' => $channel->getName(),
			'configuration' => array_merge(WiseChatChannelsDAO::DEFAULT_CONFIGURATION, $channel->getConfiguration() ? $channel->getConfiguration() : []),
			'avatar' => $this->options->getIconsURL() . 'public-channel.png',
			'full' => $isChatFull,
			'protected' => $this->channelsService->isProtectedChannel($channel),
			'authorized' => $this->channelsService->isUserAuthorizedForChannel($channel),
			'canEdit' => $this->channelsService->canEdit($channel),
			'canRemove' => $this->channelsService->canRemove($channel)
		);
	}

	/**
	 * @param WP_User[] $users
	 * @return array
	 */
	public function wpUsersToPlain($users) {
		$plain = [];

		foreach ($users as $user) {
			$avatarUrl = get_avatar_url($user, array("size" => 96));
			if ($avatarUrl === false) {
				$avatarUrl = $this->options->getIconsURL().'user.png';
			}
			$plain[] = [
				'id' => WiseChatCrypt::encryptToString($user->ID),
				'name' => $user->display_name,
				'avatarUrl' => $avatarUrl
			];
		}

		return $plain;
	}

	/**
	 * Returns plain array representation of user object.
	 *
	 * @param WiseChatUser $user
	 * @param array $additionalDetails
	 * @return array
	 */
	public function getUserAsPlainDirectChannel(WiseChatUser $user, array $additionalDetails = [], array $supplementaryData = []) {
		if (array_key_exists($user->getId(), $this->plainDirectChannelsCache)) {
			return $this->plainDirectChannelsCache[$user->getId()];
		}

		$currentUserId = $this->authentication->getUserIdOrNull();

		// text color defined by role:
		$textColor = $this->userService->getTextColorDefinedByUserRole($user);

		// custom text color:
		if ($this->options->isOptionEnabled('allow_change_text_color')) {
			$textColorProposal = $user->getDataProperty('textColor');
			if ($textColorProposal) {
				$textColor = $textColorProposal;
			}
		}

		// avatar:
		$avatarSrc = $this->options->isOptionEnabled('show_users_list_avatars', false) ? $this->userService->getUserAvatar($user) : null;

		$isCurrentUser = $currentUserId === $user->getId();

		// add roles as css classes:
		$roleClasses = $this->options->isOptionEnabled('css_classes_for_user_roles', false) ? $this->userService->getCssClassesForUserRoles($user) : null;

		$countryFlagSrc = null;
		$countryCode = null;
		$country = null;
		$city = null;

		if ($this->options->isOptionEnabled('collect_user_stats', true) && $this->options->isOptionEnabled('show_users_flags', false)) {
			$countryCode = $user->getDataProperty('countryCode');
			$country = $user->getDataProperty('country');
			if ($countryCode) {
				$countryFlagSrc = $this->options->getFlagURL(strtolower($countryCode));
			}
		}
		if ($this->options->isOptionEnabled('collect_user_stats', true) && $this->options->isOptionEnabled('show_users_city_and_country', false)) {
			$city = $user->getDataProperty('city');
			$countryCode = $user->getDataProperty('countryCode');
		}

		$isAllowed = false;
		$url = null;
		if ($this->options->isOptionEnabled('enable_private_messages', false) || !$this->options->isOptionEnabled('users_list_linking', false)) {
			$isAllowed = $this->privateMessagesRulesService->isMessageDeliveryAllowed($this->authentication->getUser(), $user);
		} else if ($this->options->isOptionEnabled('users_list_linking', false)) {
			$url = $this->userService->getUserProfileLink($user, $user->getName(), $user->getWordPressId());
		}

		return $this->plainDirectChannelsCache[$user->getId()] = array_merge([
			'id' => $this->encryptDirectChannelId($user->getId()),
			'name' => $user->getName(),
			'type' => 'direct',
			'readOnly' => !$isAllowed,
			'configuration' => WiseChatChannelsDAO::DEFAULT_CONFIGURATION,
			'url' => $url,
			'textColor' => $textColor,
			'avatar' => $avatarSrc,
			'locked' => $isCurrentUser,
			'classes' => $roleClasses,
			'countryCode' => $countryCode,
			'country' => $country,
			'city' => $city,
			'countryFlagSrc' => $countryFlagSrc,
			'infoWindow' => $this->getInfoWindow($user),
			'intro' => $this->uiTemplates->getDirectChannelIntro($user),
			'online' => false,
			'muted' => in_array($user->getId(), $supplementaryData['mutedUsers'] ?? [])
		], $additionalDetails);
	}

	private function getInfoWindow($user) {
		if (!$this->options->isOptionEnabled('show_users_list_info_windows', true)) {
			return null;
		}

		$avatarSrc = $this->options->isOptionEnabled('show_users_list_avatars', false) ? $this->userService->getUserAvatar($user) : null;

		return array(
			'avatar' => $avatarSrc,
			'name' => $user->getName(),
			'url' => $this->userService->getUserProfileLink($user, $user->getName(), $user->getWordPressId()),
			'content' => $this->uiTemplates->getInfoWindow($user)
		);
	}

	/**
	 * @param Channel $channel
	 * @return string|null
	 */
	private function getChannelTypeAsText($channel) {
		if (!$channel->getType() || $channel->getType() === Channel::TYPE_PUBLIC) {
			return 'public';
		}
		if ($channel->getType() === Channel::TYPE_PRIVATE) {
			return 'private';
		}

		return null;
	}

}