<?php

namespace Kainex\WiseChatPro\DAO\User;

use Exception;
use Kainex\WiseChatPro\DAO\AbstractDAO;
use Kainex\WiseChatPro\Model\UserFeed;
use Kainex\WiseChatPro\WiseChatInstaller;

/**
 * User feed.
 *
 * @author Kainex <contact@kainex.pl>
 */
class UserFeedDAO extends AbstractDAO {

	protected function getTableName(): string {
		return WiseChatInstaller::getUserFeedTable();
	}

	/**
	 * Creates or updates object.
	 *
	 * @param UserFeed $userFeed
	 *
	 * @return UserFeed
	 * @throws Exception On validation error
	 */
	public function save(UserFeed $userFeed): UserFeed {
		// low-level validation:
		if ($userFeed->getUserId() === null) {
			throw new Exception('Please provide user ID');
		}
		if ($userFeed->getType() === null) {
			throw new Exception('Please provide type');
		}

		$userFeed->setId($this->persist([
			'id' => $userFeed->getId(),
			'user_id' => $userFeed->getUserId(),
			'target_id' => $userFeed->getTargetId(),
			'type' => $userFeed->getType(),
			'created' => $userFeed->getCreated()->format('Y-m-d H:i:s'),
			'data' => json_encode($userFeed->getData()),
			'seen' => $userFeed->isSeen() === true ? '1' : '0',
		]));

		return $userFeed;
	}

	/**
	 * @param integer $id
	 *
	 * @return UserFeed|null
	 */
	public function get(int $id): ?UserFeed {
		$raw = $this->getOneBy(['id' => [$id, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * @param integer $userId
	 * @param int $limit
	 * @param int $offset
	 * @return UserFeed[]
	 */
	public function getByUserId(int $userId, ?int $afterId = null, int $limit = 5): array {
		$rawArray = $afterId
			? $this->getAllBy(['user_id' => [$userId, '%d'], 'id' => [$afterId, '%d', '<']], ['created', 'desc'], $limit)
			: $this->getAllBy(['user_id' => [$userId, '%d']], ['created', 'desc'], $limit);

		$userFeedArray = [];
		foreach ($rawArray as $result) {
			$userFeedArray[] = $this->populateData($result);
		}

		return $userFeedArray;
	}

	/**
	 * @param \stdClass $rawRow
	 *
	 * @return UserFeed
	 */
	protected function populateData(\stdClass $rawRow): UserFeed {
		$userFeed = new UserFeed();
		if ($rawRow->id > 0) {
			$userFeed->setId(intval($rawRow->id));
		}
		$userFeed->setUserId(intval($rawRow->user_id));
		if ($rawRow->target_id) {
			$userFeed->setTargetId(intval($rawRow->target_id));
		}
		$userFeed->setType($rawRow->type);
		$userFeed->setData(json_decode($rawRow->data, true));
		$userFeed->setCreated(\DateTime::createFromFormat('Y-m-d H:i:s', $rawRow->created));
		$userFeed->setSeen($rawRow->seen == '1');

		return $userFeed;
	}
}