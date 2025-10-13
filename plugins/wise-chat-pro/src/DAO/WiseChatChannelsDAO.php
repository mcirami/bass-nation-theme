<?php

namespace Kainex\WiseChatPro\DAO;

use Exception;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\WiseChatInstaller;

/**
 * Wise Chat channels DAO
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatChannelsDAO extends AbstractDAO {

	const DEFAULT_CONFIGURATION = [
		'enableImages' => true,
		'enableAttachments' => true,
		'enableVoiceMessages' => true
	];

	protected function getTableName(): string {
		return WiseChatInstaller::getChannelsTable();
	}

	/**
	 * Creates or updates the channel and returns it.
	 *
	 * @param Channel $channel
	 *
	 * @return Channel
	 * @throws Exception On validation error
	 */
	public function save(Channel $channel): Channel {
		// low-level validation:
		if ($channel->getName() === null) {
			throw new Exception('Name of the channel cannot equal null');
		}

		$channel->setId($this->persist([
			'id' => $channel->getId(),
			'name' => $channel->getName(),
			'password' => $channel->getPassword(),
			'type' => $channel->getType(),
			'configuration' => json_encode($channel->getConfiguration())
		]));

		return $channel;
	}

	/**
	 * Returns channel by ID.
	 *
	 * @param integer $id
	 *
	 * @return Channel|null
	 */
	public function get(int $id): ?Channel {
		$raw = $this->getOneBy(['id' => [$id, '%d']]);

		return $raw ? $this->populateData($raw) : null;
	}

	/**
	 * Returns channels by IDs.
	 *
	 * @param integer[] $ids
	 *
	 * @return Channel[]
	 */
	public function getAllById(array $ids): array {
		if (!$ids) {
			return [];
		}

		return array_map([$this, 'populateData'], $this->getAllBy(['id' => [$ids, '%d']]));
	}

	/**
	 * @param array $filters
	 * @return Channel[]
	 */
	public function searchChannels(array $filters): array {
		$keyword = $filters['search'];

		$channels = array_map([$this, 'populateData'], $this->getAllBy(['name' => [$keyword, '%s', 'like']], ['name', 'asc']));

		return $channels;
	}

	/**
	 * Returns all channels sorted by name.
	 *
	 * @return Channel[]
	 */
	public function getAll(): array {
		global $wpdb;

		$channels = array();
		$table = WiseChatInstaller::getChannelsTable();
		$sql = sprintf('SELECT * FROM %s ORDER BY name ASC;', $table);
		$results = $wpdb->get_results($sql);
		if (is_array($results)) {
			foreach ($results as $result) {
				$channels[] = $this->populateData($result);
			}
		}

		return $channels;
	}

	/**
	 * Returns channel by name.
	 *
	 * @param string $name
	 *
	 * @return Channel|null
	 */
	public function getByName($name) {
		global $wpdb;

		$name = addslashes($name);
		$table = WiseChatInstaller::getChannelsTable();
		$sql = sprintf('SELECT * FROM %s WHERE name = "%s";', $table, $name);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $this->populateData($results[0]);
		}

		return null;
	}

	/**
	 * Returns channels by names. The method is cached.
	 *
	 * @param string[] $names
	 *
	 * @return Channel[]
	 */
	public function getByNames($names) {
		global $wpdb;
		static $cache = array();

		$names = array_filter(array_map('addslashes', $names));
		if (count($names) === 0) {
			return array();
		}
		$namesCondition = implode("', '", $names);

		$cacheKey = md5($namesCondition);
		if (array_key_exists($cacheKey, $cache)) {
			return $cache[$cacheKey];
		}

		$table = WiseChatInstaller::getChannelsTable();
		$sql = sprintf("SELECT * FROM %s WHERE name IN ('%s');", $table, $namesCondition);
		$results = $wpdb->get_results($sql);
		$channels = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$channels[] = $this->populateData($result);
			}
		}

		$cache[$cacheKey] = $channels;

		return $channels;
	}

	/**
	 * Gets user's personal list of channels.
	 *
	 * @param $userId
	 * @return Channel[]
	 */
	public function getUserChannels($userId) {
		global $wpdb;

		$channelMembersTable = WiseChatInstaller::getChannelMembersTable();
		$userChannelsTable = WiseChatInstaller::getUserChannelsTable();
		$table = WiseChatInstaller::getChannelsTable();
		$sql = sprintf(
			"SELECT c.*
			FROM %s uc
			LEFT JOIN %s c ON (c.id = uc.channel_id)
			WHERE uc.user_id = %d AND (c.type = %d OR EXISTS ( SELECT * FROM %s m WHERE m.user_id = uc.user_id AND m.channel_id = uc.channel_id AND m.confirmed = 1 ) );",
			$userChannelsTable, $table, $userId, Channel::TYPE_PUBLIC, $channelMembersTable
		);

		$results = $wpdb->get_results($sql);
		$channels = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$channels[] = $this->populateData($result);
			}
		}

		return $channels;
	}

	/**
	 * Converts raw object into WiseChatChannel object.
	 *
	 * @param \stdClass $rawChannelData
	 *
	 * @return Channel
	 */
	protected function populateData(\stdClass $rawChannelData): object {
		$channel = new Channel();
		if ($rawChannelData->id > 0) {
			$channel->setId(intval($rawChannelData->id));
		}
		$channel->setName($rawChannelData->name);
		$channel->setPassword($rawChannelData->password);
		if ($rawChannelData->type > 0) {
			$channel->setType(intval($rawChannelData->type));
		}
		if ($rawChannelData->configuration) {
			$channel->setConfiguration(json_decode($rawChannelData->configuration, true));
		}

		return $channel;
	}

}