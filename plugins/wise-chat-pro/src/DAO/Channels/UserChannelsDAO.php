<?php

namespace Kainex\WiseChatPro\DAO\Channels;

use Exception;
use Kainex\WiseChatPro\DAO\AbstractDAO;
use Kainex\WiseChatPro\Model\UserChannel;
use Kainex\WiseChatPro\WiseChatInstaller;

/**
 * Wise Chat user channels list.
 *
 * @author Kainex <contact@kainex.pl>
 */
class UserChannelsDAO extends AbstractDAO {

	protected function getTableName(): string {
		return WiseChatInstaller::getUserChannelsTable();
	}

	/**
	 * Creates or updates the channel member and returns it.
	 *
	 * @param UserChannel $userChannel
	 *
	 * @return UserChannel
	 * @throws Exception On validation error
	 */
	public function save(UserChannel $userChannel): UserChannel {
		if ($userChannel->getUserId() === null) {
			throw new Exception('Please provide user ID');
		}
		if ($userChannel->getChannelId() === null) {
			throw new Exception('Please provide channel ID');
		}

		$userChannel->setId($this->persist([
			'id' => $userChannel->getId(),
			'user_id' => $userChannel->getUserId(),
			'channel_id' => $userChannel->getChannelId(),
			'sort' => $userChannel->getSort()
		]));

		return $userChannel;
	}

	/**
	 * @param integer $userId
	 *
	 * @return UserChannel[]
	 */
	public function getByUserId(int $userId): array {
		$rawArray = $this->getAllBy(['user_id' => [$userId, '%d']], ['sort', 'asc']);

		$userChannels = [];
		foreach ($rawArray as $result) {
			$userChannels[] = $this->populateData($result);
		}

		return $userChannels;
	}

	/**
	 * Returns by user ID and channel ID.
	 *
	 * @param integer $userId
	 * @param integer $channelId
	 * @return UserChannel[]
	 */
	public function getAllByUserIdAndChannelId(int $userId, int $channelId): array {
		$rawArray = $this->getAllBy(['channel_id' => [$channelId, '%d'], 'user_id' => [$userId, '%d']], ['sort', 'asc']);

		$userChannels = [];
		foreach ($rawArray as $result) {
			$userChannels[] = $this->populateData($result);
		}

		return $userChannels;
	}

	/**
	 * Returns max sort by user ID.
	 *
	 * @param integer $userId
	 *
	 * @return integer
	 */
	public function getMaxSortUserId(int $userId): int {
		global $wpdb;

		$sql = $wpdb->prepare('SELECT max(`sort`) AS maxSort FROM %i WHERE `user_id` = %d;', $this->getTableName(), $userId);
		$results = $wpdb->get_results($sql);
		return is_array($results) ? (int) $results[0]->maxSort : 0;
	}

	/**
	 * Converts raw object into WiseChatUserChannel object.
	 *
	 * @param \stdClass $rawRow
	 *
	 * @return UserChannel
	 */
	protected function populateData(\stdClass $rawRow): UserChannel {
		$channel = new UserChannel();
		if ($rawRow->id > 0) {
			$channel->setId(intval($rawRow->id));
		}
		$channel->setUserId($rawRow->user_id);
		$channel->setChannelId($rawRow->channel_id);
		$channel->setSort(intval($rawRow->sort));

		return $channel;
	}

	/**
	 * Deletes the channel from all lists.
	 *
	 * @param int $channelId
	 * @return void
	 */
	public function deleteByChannelId(int $channelId) {
		$this->deleteBy(['channel_id' => [$channelId, '%d']]);
	}
}