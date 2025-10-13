<?php

namespace Kainex\WiseChatPro\Services\User;

use Exception;
use Kainex\WiseChatPro\DAO\User\UserFeedDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelUsersDAO;
use Kainex\WiseChatPro\Model\UserFeed;
use Kainex\WiseChatPro\Model\WiseChatUser;
use Kainex\WiseChatPro\Services\ClientSide\WiseChatClientSide;
use Kainex\WiseChatPro\Services\WiseChatChannelsService;
use Kainex\WiseChatPro\Services\WiseChatHttpRequestService;
use Kainex\WiseChatPro\WiseChatCrypt;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * User feed service.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserFeedService extends UserFeedDAO {

	const FEED_LIMIT = 5;

	/**
	 * @var WiseChatChannelsService
	 */
	protected $channelsService;

	/** @var WiseChatUserService */
	private $userService;

	/** @var WiseChatClientSide */
	private $clientSide;

	/**
	 * @var WiseChatChannelUsersDAO
	 */
	private $channelUsersDAO;

	/** @var WiseChatOptions */
	private $options;

	/**
	 * @var WiseChatHttpRequestService
	 */
	private $httpRequestService;

	/**
	 * @param WiseChatChannelsService $channelsService
	 * @param WiseChatUserService $userService
	 * @param WiseChatClientSide $clientSide
	 * @param WiseChatChannelUsersDAO $channelUsersDAO
	 * @param WiseChatOptions $options
	 * @param WiseChatHttpRequestService $httpRequestService
	 */
	public function __construct(WiseChatChannelsService $channelsService, WiseChatUserService $userService, WiseChatClientSide $clientSide, WiseChatChannelUsersDAO $channelUsersDAO, WiseChatOptions $options, WiseChatHttpRequestService $httpRequestService) {
		$this->channelsService = $channelsService;
		$this->userService = $userService;
		$this->clientSide = $clientSide;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->options = $options;
		$this->httpRequestService = $httpRequestService;
	}

	/**
	 * @param integer $userId
	 * @param string $type
	 * @param int|null $targetId
	 * @param array $data
	 * @return UserFeed
	 * @throws Exception
	 */
	public function create(int $userId, string $type, ?int $targetId = null, array $data = []): UserFeed {
    	$userFeed = new UserFeed();
		$userFeed->setUserId($userId);
		$userFeed->setType($type);
		$userFeed->setData($data);
		if ($targetId) {
			$userFeed->setTargetId($targetId);
		}

		$this->sendNewEntryNotifications($userFeed);

		return $this->save($userFeed);
	}

	private function sendNewEntryNotifications(UserFeed $userFeed) {
		if (!$this->options->isOptionEnabled('feed_notifications_new_entry_enabled', true)) {
			return;
		}

		$recipientUser = $this->userService->get($userFeed->getUserId());

		if ($recipientUser === null) {
			return;
		}

		if ($this->channelUsersDAO->isOnline($recipientUser->getId())) {
			return;
		}

		if ($recipientUser->getDataProperty('disableNotifications') === true) {
			return;
		}

		if (!($recipientUser->getWordPressId() > 0)) {
			return;
		}

		$wpUser = $this->userService->getWpUserByID($recipientUser->getWordPressId());
		if ($wpUser === null) {
			return;
		}

		$variables = array(
			'{id}' => $wpUser->ID,
			'{username}' => $wpUser->user_login,
			'{displayname}' => $wpUser->display_name,
			'{email}' => $wpUser->user_email,
			'{firstname}' => $wpUser->user_firstname,
			'{lastname}' => $wpUser->user_lastname,
			'{nickname}' => $wpUser->nickname,
			'{description}' => $wpUser->user_description,
			'{website}' => $wpUser->user_url,
			'{link}' => $this->httpRequestService->getReferrerURL()
		);

		$emailSubject = str_replace(array_keys($variables), array_values($variables), $this->options->getOption('feed_notifications_new_entry_subject'));
		$emailBody = str_replace(array_keys($variables), array_values($variables), $this->options->getOption('feed_notifications_new_entry_body'));

		wp_mail($wpUser->user_email, $emailSubject, $emailBody);
	}

	/**
	 * Returns latest user feed as plain array.
	 *
	 * @param WiseChatUser $user
	 * @param int $resultsPage
	 * @return array
	 */
	public function getLatest(WiseChatUser $user, ?int $afterId = null): array {
		$feed = $this->getByUserId($user->getId(), $afterId, self::FEED_LIMIT);

		$channelIDs = [];
		$userIDs = [];
		foreach ($feed as $feedItem) {
			if (preg_match('/^channels\./', $feedItem->getType())) {
				$channelIDs[] = $feedItem->getTargetId();
			}

			if (isset($feedItem->getData()['userId'])) {
				$userIDs[] = $feedItem->getData()['userId'];
			}
		}
		$channels = $this->channelsService->getAllById(array_unique($channelIDs));
		$channelsMap = [];
		foreach ($channels as $channel) {
			$channelsMap[$channel->getId()] = $channel;
		}
		$users = $this->userService->getAll($userIDs);
		$usersMap = [];
		foreach ($users as $user) {
			$usersMap[$user->getId()] = $user;
		}

		$output = [];
		foreach ($feed as $feedItem) {
			$plainFeedItem = [
				'id' => WiseChatCrypt::encryptToString($feedItem->getId()),
				'idRaw' => $feedItem->getId(),
				'type' => $feedItem->getType(),
				'seen' => $feedItem->isSeen(),
				'created' => $feedItem->getCreated()->format('Y-m-d H:i:s'),
			];
			if (preg_match('/^channels\./', $feedItem->getType()) && isset($channelsMap[$feedItem->getTargetId()])) {
				$channel = $channelsMap[$feedItem->getTargetId()];
				$plainFeedItem['channel'] = [ 'id' => $this->clientSide->encryptPublicChannelId($channel->getId()), 'name' => $channel->getName() ];
			}

			if (isset($feedItem->getData()['userId'])) {
				$relatedUserId = $feedItem->getData()['userId'];
				if (isset($usersMap[$relatedUserId])) {
					$user = $usersMap[$relatedUserId];
					$plainFeedItem['user'] = [
						'id' => WiseChatCrypt::encryptToString($user->getId()),
						'name' => $user->getName()
					];
				}
			}

			$output[] = $plainFeedItem;
		}

		return $output;
	}

	public function markAsSeen(int $userFeedEntryId, int $userId) {
		$userFeed = $this->get($userFeedEntryId);
		if (!$userFeed) {
			throw new \Exception('User feed entry not found');
		}
		if ($userFeed->getUserId() !== $userId) {
			throw new \Exception('Access denied');
		}
		$userFeed->setSeen(true);
		$this->save($userFeed);
	}

}