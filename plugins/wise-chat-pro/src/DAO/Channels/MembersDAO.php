<?php

namespace Kainex\WiseChatPro\DAO\Channels;

use Exception;
use Kainex\WiseChatPro\DAO\AbstractDAO;
use Kainex\WiseChatPro\Model\Channel\ChannelMember;
use Kainex\WiseChatPro\Model\WiseChatUser;
use Kainex\WiseChatPro\WiseChatInstaller;

/**
 * Members DAO
 *
 * @author Kainex <contact@kainex.pl>
 */
class MembersDAO extends AbstractDAO {

	protected function getTableName(): string {
		return WiseChatInstaller::getChannelMembersTable();
	}

	/**
	 * @param ChannelMember $member
	 *
	 * @return ChannelMember
	 * @throws Exception
	 */
	public function save(ChannelMember $member): ChannelMember {
		if ($member->getUserId() === null) {
			throw new Exception('Please provide user ID');
		}
		if ($member->getChannelId() === null) {
			throw new Exception('Please provide channel ID');
		}

		$member->setId($this->persist([
			'id' => $member->getId(),
			'user_id' => $member->getUserId(),
			'channel_id' => $member->getChannelId(),
			'confirmed' => $member->isConfirmed() === true ? '1' : '0',
			'type' => $member->getType()
		]));

		return $member;
	}

	/**
	 * Returns channel member by ID.
	 *
	 * @param integer $id
	 *
	 * @return ChannelMember|null
	 */
	public function get(int $id): ?ChannelMember {
		$raw = $this->getOneBy(['id' => [$id, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns all members in the channel. It also populates user property of the member.
	 *
	 * @param integer $channelId
	 * @return ChannelMember[]
	 */
	public function getAllByChannelId(int $channelId): array {
		global $wpdb;

		$members = array();
		$sql = $wpdb->prepare(
			'SELECT cm.*, u.name AS joined_user_name, u.wp_id AS joined_user_wp_id, u.avatar_url AS joined_user_avatar_url FROM %i cm 
			LEFT JOIN %i AS u ON (u.id = cm.user_id) 
			WHERE channel_id = %d AND u.id IS NOT NULL 
			ORDER BY u.name;',
			$this->getTableName(), WiseChatInstaller::getUsersTable(), $channelId
		);
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$member = $this->populateData($result);
				$user = new WiseChatUser();
				$user->setId($result->user_id);
				$user->setName($result->joined_user_name);
				$user->setAvatarUrl($result->joined_user_avatar_url);
				if ($result->joined_user_wp_id) {
					$user->setWordPressId((int) $result->joined_user_wp_id);
				}
				$member->setUser($user);

				$members[] = $member;
			}
		}

		return $members;
	}

	/**
	 * Returns member object by channel ID and user ID.
	 *
	 * @param integer $channelId
	 * @param integer $userId
	 * @param bool|null $confirmed
	 * @return ChannelMember|null
	 */
	public function getByChannelIdAndUserId(int $channelId, int $userId, ?bool $confirmed = null): ?ChannelMember {
		if ($confirmed !== null) {
			$raw = $this->getOneBy(['channel_id' => [$channelId, '%d'], 'user_id' => [$userId, '%d'], 'confirmed' => [$confirmed ? 1 : 0, '%d']]);
		} else {
			$raw = $this->getOneBy(['channel_id' => [$channelId, '%d'], 'user_id' => [$userId, '%d']]);
		}

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns member object by channel ID and type IDs.
	 *
	 * @param integer $channelId
	 * @param int $typeId
	 * @return ChannelMember|null
	 */
	public function getByChannelIdAndTypeId(int $channelId, int $typeId): ?ChannelMember {
		$raw = $this->getOneBy(['channel_id' => [$channelId, '%d'], 'type' => [$typeId, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns member objects by user ID.
	 *
	 * @param integer $userId
	 * @return ChannelMember[]
	 */
	public function getAllByUserId(int $userId): array {
		return array_map([$this, 'populateData'], $this->getAllBy(['user_id' => [$userId, '%d']]));
	}

	/**
	 * Converts raw object into WiseChatChannelMember object.
	 *
	 * @param \stdClass $rawRow
	 * @return ChannelMember
	 */
	protected function populateData(\stdClass $rawRow): ChannelMember {
		$member = new ChannelMember();
		if ($rawRow->id > 0) {
			$member->setId(intval($rawRow->id));
		}
		$member->setUserId($rawRow->user_id);
		$member->setChannelId($rawRow->channel_id);
		$member->setType(intval($rawRow->type));
		$member->setConfirmed($rawRow->confirmed == '1');

		return $member;
	}

	/**
	 * Deletes all members of a channel
	 *
	 * @param int $channelId
	 * @return void
	 */
	public function deleteByChannelId(int $channelId) {
		$this->deleteBy(['channel_id' => [$channelId, '%d']]);
	}

}