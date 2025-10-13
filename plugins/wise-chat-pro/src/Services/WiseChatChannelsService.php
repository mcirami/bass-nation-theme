<?php

namespace Kainex\WiseChatPro\Services;

use Exception;
use Kainex\WiseChatPro\DAO\Channels\MembersDAO;
use Kainex\WiseChatPro\DAO\Channels\UserChannelsDAO;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelsDAO;
use Kainex\WiseChatPro\DAO\WiseChatMessagesDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Model\Channel\ChannelMember;
use Kainex\WiseChatPro\Model\UserChannel;
use Kainex\WiseChatPro\Services\ClientSide\WiseChatClientSide;
use Kainex\WiseChatPro\Services\User\UserFeedService;
use Kainex\WiseChatPro\Services\User\WiseChatActions;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\Services\User\WiseChatAuthorization;
use Kainex\WiseChatPro\Services\User\WiseChatUserService;
use Kainex\WiseChatPro\WiseChatCrypt;
use Kainex\WiseChatPro\WiseChatOptions;
use Kainex\WiseChatPro\WiseChatSettings;

/**
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatChannelsService extends WiseChatChannelsDAO {

	const PRIVATE_MESSAGES_CHANNEL = '__private';

	/** @var UserFeedService */
	private $userFeedService;

	/**
     * @var WiseChatUserService
     */
    private $userService;

	/** @var MembersDAO */
	private $membersDAO;

	/** @var UserChannelsDAO */
	private $userChannelsDAO;

	/**
	 * @var WiseChatUsersDAO
	 */
	protected $usersDAO;

	/**
	 * @var WiseChatAuthorization
	 */
	protected $authorization;

	/**
	 * @var WiseChatAuthentication
	 */
	protected $authentication;

	/**
	* @var WiseChatMessagesDAO
	*/
	protected $messagesDAO;

	/**
	 * @var WiseChatMessagesService
	 */
	protected $messagesService;

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	 * @var WiseChatOptions
	 */
	private $options;

	/**
	 * @var WiseChatClientSide
	 */
	protected $clientSide;

	/**
	 * @param UserFeedService $userFeedService
	 * @param WiseChatUserService $userService
	 * @param MembersDAO $membersDAO
	 * @param UserChannelsDAO $userChannelsDAO
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatAuthorization $authorization
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatMessagesDAO $messagesDAO
	 * @param WiseChatMessagesService $messagesService
	 * @param WiseChatActions $actions
	 * @param WiseChatOptions $options
	 * @param WiseChatClientSide $clientSide
	 */
	public function __construct(UserFeedService $userFeedService, WiseChatUserService $userService, MembersDAO $membersDAO, UserChannelsDAO $userChannelsDAO, WiseChatUsersDAO $usersDAO, WiseChatAuthorization $authorization, WiseChatAuthentication $authentication, WiseChatMessagesDAO $messagesDAO, WiseChatMessagesService $messagesService, WiseChatActions $actions, WiseChatOptions $options, WiseChatClientSide $clientSide) {
		$this->userFeedService = $userFeedService;
		$this->userService = $userService;
		$this->membersDAO = $membersDAO;
		$this->userChannelsDAO = $userChannelsDAO;
		$this->usersDAO = $usersDAO;
		$this->authorization = $authorization;
		$this->authentication = $authentication;
		$this->messagesDAO = $messagesDAO;
		$this->messagesService = $messagesService;
		$this->actions = $actions;
		$this->options = $options;
		$this->clientSide = $clientSide;
	}

	/**
	 * @return Channel[]
	 */
	public function getConstantChannels() {
		return $this->getByNames((array) $this->options->getOption('channel'));
	}

	/**
	 * @param Channel $channelToCheck
	 * @return bool
	 */
	public function isConstantChannel($channelToCheck) {
		$channels = $this->getConstantChannels();
		foreach ($channels as $channel) {
			if ($channel->getId() === $channelToCheck->getId()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 * @throws Exception
	 */
	public function hasPublicChannelAccess($channel) {
		if ($this->options->getIntegerOption('mode', 0) === 0 && $this->options->isOptionEnabled('classic_disable_channel', false)) {
			return false;
		}

		if ($this->options->getIntegerOption('mode', 0) === 1 && $this->options->isOptionEnabled('fb_disable_channel', false)) {
			return false;
		}

		return $this->isUserAuthorizedForChannel($channel);
	}

	/**
	 * Determines whether the current user is authorized to access the channel.
	 *
	 * @param Channel $channel
	 *
	 * @return boolean
	 * @throws Exception
	 */
    public function isUserAuthorizedForChannel($channel) {
    	if ($channel->getPassword()) {
    		$grants = $this->userService->getProperty(WiseChatAuthorization::PROPERTY_NAME);
    		$passwordAuthorized = is_array($grants) && array_key_exists($channel->getId(), $grants) && $grants[$channel->getId()] === $channel->getPassword();

    		if (!$passwordAuthorized) {
    			return false;
		    }
	    }

        return $this->isChannelMember($channel);
    }

	public function canCreateChannels() {
		return $this->usersDAO->hasCurrentWpUserRight('create_channels') || (!$this->usersDAO->isWpUserLogged() && $this->options->isOptionEnabled('permission_create_channels_anonymous', false));
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function isProtectedChannel($channel) {
		return $channel !== null && $channel->getPassword();
	}

	/**
	 * @param integer[] $channelIds
	 * @return Channel[]
	 * @throws Exception If a channel cannot be found
	 */
	public function getChannelsByIds($channelIds) {
		$channels = array();

		foreach ($channelIds as $channelId) {
			$requestedChannel = $this->get($channelId);
			if ($requestedChannel === null) {
				throw new Exception('The channel does not exist: '.$channelId);
			}
			$channels[] = $requestedChannel;
		}

		return $channels;
	}

	/**
	 * Get the channel for storing direct messages.
	 *
	 * @return Channel
	 * @throws Exception
	 */
	public function getDirectChannel() {
		$channel = $this->getByName(self::PRIVATE_MESSAGES_CHANNEL);

		// create the direct messages channel if it does not exist:
		if (!$channel) {
			$channel = new Channel();
			$channel->setName(self::PRIVATE_MESSAGES_CHANNEL);
			$channel->setType(Channel::TYPE_PUBLIC);
			$this->save($channel);
		}

		return $channel;
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function canEdit($channel) {
		return $this->canCreateChannels() && $this->isChannelMemberType($channel, ChannelMember::TYPE_OWNER);
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function canRemove($channel) {
		$userChannels = $this->userChannelsDAO->getAllByUserIdAndChannelId($this->authentication->getUser()->getId(), $channel->getId());

		return count($userChannels) > 0;
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function isDirect($channel) {
		return $channel->getName() === self::PRIVATE_MESSAGES_CHANNEL;
	}

	/**
	 * @param Channel $channel
	 * @return bool
	 */
	public function isChannelMember($channel) {
		if ($channel->getType() === null) {
			return true;
		}

		if ($channel->getType() === Channel::TYPE_PUBLIC) {
			return true;
		}

		if ($channel->getType() === Channel::TYPE_PRIVATE) {
			return $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $this->authentication->getUserIdOrNull(), true) !== null;
		}

		return false;
	}

	/**
	 * @param Channel $channel
	 * @param integer $type
	 * @return bool
	 */
	public function isChannelMemberType($channel, $type) {
		if ($channel->getType() === null) {
			return false;
		}

		$member = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $this->authentication->getUserIdOrNull(), true);

		return $member !== null && $member->getType() === $type;
	}

	/**
	 * @param $parameters
	 * @return Channel
	 * @throws Exception
	 */
	public function createChannel($parameters) {
		if (!$this->canCreateChannels()) {
			throw new \Exception(__('No permission to create channels', 'wise-chat-pro'));
		}

		$name = $parameters['name'];
		$type = $parameters['type'];

		if (!$name) {
			throw new \Exception('No name provided');
		}
		if (!in_array($type, ['public', 'private'])) {
			throw new \Exception('Invalid type');
		}

		if ($this->getByName($name)) {
			throw new \Exception(__('This name is already occupied', 'wise-chat-pro'));
		}

		// create channel:
		$channel = new Channel();
		$channel->setName($name);
		$channel->setType($type === 'private' ? Channel::TYPE_PRIVATE : Channel::TYPE_PUBLIC);
		$this->save($channel);

		// set current user as an owner:
		$member = new ChannelMember();
		$member->setType(ChannelMember::TYPE_OWNER);
		$member->setChannelId($channel->getId());
		$member->setUserId($this->authentication->getUserIdOrNull());
		$member->setConfirmed(true);
		$this->membersDAO->save($member);

		// add channel to own list:
		$userChannel= new UserChannel();
		$userChannel->setSort($this->userChannelsDAO->getMaxSortUserId($this->authentication->getUserIdOrNull()) + 10);
		$userChannel->setUserId($this->authentication->getUserIdOrNull());
		$userChannel->setChannelId($channel->getId());
		$this->userChannelsDAO->save($userChannel);

		return $channel;
	}

	/**
	 * @param Channel $channel
	 * @param array $parameters
	 * @return Channel
	 * @throws Exception
	 */
	public function saveChannel($channel, $parameters) {
		if (!$this->canCreateChannels()) {
			throw new \Exception(__('No permission to save channels', 'wise-chat-pro'));
		}

		if (!$this->canEdit($channel)) {
			throw new \Exception(__('No permission to save the channel', 'wise-chat-pro'));
		}

		$name = $parameters['name'];
		$type = $parameters['type'];
		$configuration = json_decode($parameters['configuration'], true);

		if (!$name) {
			throw new \Exception('No name provided');
		}
		if (!in_array($type, ['public', 'private'])) {
			throw new \Exception('Invalid type');
		}

		if ($name !== $channel->getName()) {
			if ($this->getByName($name)) {
				throw new \Exception(__('This name is already occupied', 'wise-chat-pro'));
			}
		}

		$channel->setName($name);
		$channel->setType($type === 'private' ? Channel::TYPE_PRIVATE : Channel::TYPE_PUBLIC);
		$channel->setConfiguration($configuration);
		$this->save($channel);

		return $channel;
	}

	/**
	 * @param Channel $channel
	 * @return ChannelMember[]
	 */
	public function getChannelMembers($channel) {
		return $this->membersDAO->getAllByChannelId($channel->getId());
	}

	/**
	 * Run by the channel owner or the user itself.
	 *
	 * @param Channel $channel
	 * @param integer $userId
	 * @throws Exception
	 */
	public function deleteChannelMember($channel, $userId, $postFeedEntry = true) {;
		$currentUserMember = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $this->authentication->getUserIdOrNull());
		if ($currentUserMember === null) {
			throw new \Exception('Channel member not found');
		}

		if ($currentUserMember->getType() === ChannelMember::TYPE_OWNER && $currentUserMember->getUserId() === $userId) {
			throw new \Exception(__('Channel administrator cannot remove itself from the channel', 'wise-chat-pro'));
		}

		$userMember = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $userId);
		if (!$userMember) {
			throw new \Exception('The user is not the channel member');
		}

		// if this is run by channel administrator or the user itself:
		if ($currentUserMember->getType() === ChannelMember::TYPE_OWNER || $userMember->getUserId() === $this->authentication->getUserIdOrNull()) {
			$this->membersDAO->deleteById($userMember->getId());

			$userChannels = $this->userChannelsDAO->getAllByUserIdAndChannelId($userId, $channel->getId());
			foreach ($userChannels as $userChannel) {
				$this->userChannelsDAO->deleteById($userChannel->getId());
			}

			// do not notify unconfirmed users:
			if (!$userMember->isConfirmed()) {
				$postFeedEntry = false;
			}
		} else {
			throw new \Exception('No permission to remove channel member');
		}

		if ($postFeedEntry) {
			$this->userFeedService->create($userId, 'channels.member.deleted', $channel->getId());
		}

		$this->actions->publishAction('deleteChannel', array('channelId' => $this->clientSide->encryptPublicChannelId($channel->getId())), $userId);
	}

	/**
	 * Deletes the channel, members and messages.
	 *
	 * @param Channel $channel
	 * @throws Exception
	 */
	public function deleteChannel($channel) {
		if (!current_user_can(WiseChatSettings::CAPABILITY)) {
			$currentUserMember = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $this->authentication->getUserIdOrNull());
			if ($currentUserMember === null) {
				throw new \Exception('Permission denied');
			}

			if ($currentUserMember->getType() !== ChannelMember::TYPE_OWNER) {
				throw new \Exception('Permission denied');
			}
		}

		$this->membersDAO->deleteByChannelId($channel->getId());
		$this->userChannelsDAO->deleteByChannelId($channel->getId());
		$this->messagesService->deleteByChannel($channel->getName());
		$this->actions->publishAction('deleteChannel', array('channelId' => $this->clientSide->encryptPublicChannelId($channel->getId())));
		$this->deleteById($channel->getId());
	}

	/**
	 * @param Channel $channel
	 * @param integer $wordPressUserId
	 * @param boolean $confirmed
	 * @throws Exception
	 */
	public function addChannelMemberOfWPUser($channel, $wordPressUserId, $confirmed = true) {
		if (!$this->canEdit($channel)) {
			throw new \Exception('No permission to save the channel');
		}

		$user = $this->usersDAO->createOrGetBasedOnWordPressUserId($wordPressUserId);
		if (!$user) {
			throw new \Exception('WordPress user does not exist');
		}

		$this->addChannelMember($channel, $user->getId(), $confirmed);
	}

	/**
	 * @param Channel $channel
	 * @param integer $userId
	 * @param boolean $confirmed Whether the member should be confirmed
	 * @throws Exception
	 */
	public function addChannelMember($channel, $userId, $confirmed = true) {
		if (!$this->canEdit($channel)) {
			throw new \Exception('No permission to save the channel');
		}

		if ($channel->getType() !== Channel::TYPE_PRIVATE) {
			throw new \Exception(__('You can only add member to private channels', 'wise-chat-pro'));
		}

		$user = $this->usersDAO->get($userId);
		if (!$user) {
			throw new \Exception('WordPress user does not exist');
		}

		$currentUserMember = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $user->getId());
		if ($currentUserMember !== null) {
			throw new \Exception(__('The user is already a member of this channel', 'wise-chat-pro'));
		}

		// set current user as an owner:
		$member = new ChannelMember();
		$member->setType(ChannelMember::TYPE_MEMBER);
		$member->setChannelId($channel->getId());
		$member->setUserId($user->getId());
		$member->setConfirmed($confirmed);
		$this->membersDAO->save($member);

		if ($confirmed) {
			// add channel to own list:
			$userChannel = new UserChannel();
			$userChannel->setSort($this->userChannelsDAO->getMaxSortUserId($user->getId()) + 10);
			$userChannel->setUserId($user->getId());
			$userChannel->setChannelId($channel->getId());
			$this->userChannelsDAO->save($userChannel);

			$this->userFeedService->create($user->getId(), 'channels.member.added', $channel->getId());
		} else {
			$this->userFeedService->create($user->getId(), 'channels.member.invitation', $channel->getId());
		}
	}

	/**
	 * @return ChannelMember[]
	 */
	public function getChannelsUserBelongsTo(): array {
		return $this->membersDAO->getAllByUserId($this->authentication->getUser()->getId());
	}

	public function joinChannel(Channel $channel) {
		if ($channel->getType() !== Channel::TYPE_PRIVATE) {
			throw new \Exception('Join is only possible in private channels');
		}

		$ownerMember = $this->membersDAO->getByChannelIdAndTypeId($channel->getId(), ChannelMember::TYPE_OWNER);
		if (!$ownerMember) {
			throw new \Exception(__('No channel owner available', 'wise-chat-pro'));
		}

		$this->userFeedService->create($ownerMember->getUserId(), 'channels.member.join.request', $channel->getId(), ['userId' => $this->authentication->getUser()->getId()]);
	}

	/**
	 * Removes own user-channel connection.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function removeUserChannel(Channel $channel) {
		$userChannels = $this->userChannelsDAO->getAllByUserIdAndChannelId($this->authentication->getUser()->getId(), $channel->getId());
		if (!$userChannels) {
			throw new \Exception('The channel is not on your list');
		}
		foreach ($userChannels as $userChannel) {
			$this->userChannelsDAO->deleteById($userChannel->getId());
		}
	}

	/**
	 * Removes the current user from the channel.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function quitChannelMembership(Channel $channel) {
		$this->deleteChannelMember($channel, $this->authentication->getUser()->getId(), false);
	}

	/**
	 * Adds a channel to the current user's list.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function addUserChannel(Channel $channel) {
		$userChannels = $this->userChannelsDAO->getAllByUserIdAndChannelId($this->authentication->getUser()->getId(), $channel->getId());
		if (!$userChannels) {
			if (!$this->isChannelMember($channel)) {
				throw new \Exception('No permission to add user channel');
			}

			if ($this->isConstantChannel($channel)) {
				throw new \Exception(__('Could not add a constant channel to the list', 'wise-chat-pro'));
			}

			$userChannel= new UserChannel();
			$userChannel->setSort($this->userChannelsDAO->getMaxSortUserId($this->authentication->getUser()->getId()) + 10);
			$userChannel->setUserId($this->authentication->getUser()->getId());
			$userChannel->setChannelId($channel->getId());
			$this->userChannelsDAO->save($userChannel);
		}
	}

	/**
	 * Confirms user membership and adds the channel to the user's list.
	 *
	 * @param Channel $channel
	 * @return void
	 * @throws Exception
	 */
	public function confirmChannelMembership(Channel $channel) {
		$member = $this->membersDAO->getByChannelIdAndUserId($channel->getId(), $this->authentication->getUser()->getId(), false);
		if ($member) {
			$member->setConfirmed(true);
			$this->membersDAO->save($member);

			$this->addUserChannel($channel);
		} else {
			throw new \Exception(__('No pending invitations', 'wise-chat-pro'));
		}
	}

	public function sendChannelNotifications(Channel $channel, array $wpUserIDs) {
		if (!$this->canEdit($channel)) {
			throw new \Exception('Permission denied');
		}

		if ($channel->getType() !== Channel::TYPE_PUBLIC) {
			throw new \Exception('Public channels only');
		}

		foreach ($wpUserIDs as $wpUserIDEncrypted) {
			$wpUserID = WiseChatCrypt::decryptFromString($wpUserIDEncrypted);
			$user = $this->usersDAO->getLatestByWordPressId($wpUserID);
			if ($user) {
				$this->userFeedService->create($user->getId(), 'channels.notification', $channel->getId());
			}
		}
	}

}