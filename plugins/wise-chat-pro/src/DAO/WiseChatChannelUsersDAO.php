<?php

namespace Kainex\WiseChatPro\DAO;

use Exception;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\Model\Channel\ChannelUser;
use Kainex\WiseChatPro\WiseChatInstaller;

/**
 * User status class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatChannelUsersDAO {

	/**
	 * @var WiseChatUsersDAO
	 */
	private $usersDAO;

	/**
	 * @var WiseChatChannelsDAO
	 */
	protected $channelsDAO;

	/**
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatChannelsDAO $channelsDAO
	 */
	public function __construct(WiseChatUsersDAO $usersDAO, WiseChatChannelsDAO $channelsDAO) {
		$this->usersDAO = $usersDAO;
		$this->channelsDAO = $channelsDAO;
	}

	/**
	 * Creates or updates the status of user.
	 *
	 * @param ChannelUser $channelUser
	 *
	 * @return ChannelUser
	 * @throws Exception On validation error
	 */
	public function save($channelUser) {
		global $wpdb;

		if ($channelUser->getUserId() === null) {
			throw new Exception('User ID is required');
		}

		// prepare channel-user data:
		$table = WiseChatInstaller::getChannelUsersTable();
		$columns = array(
			'user_id' => $channelUser->getUserId(),
			'channel_id' => $channelUser->getChannelId(),
			'active' => $channelUser->isActive() === true ? '1' : '0',
			'last_activity_time' => $channelUser->getLastActivityTime()
		);

		// update or insert:
		if ($channelUser->getId() !== null) {
			$wpdb->update($table, $columns, array('id' => $channelUser->getId()), '%s', '%d');
		} else {
			$wpdb->insert($table, $columns);
			$channelUser->setId($wpdb->insert_id);
		}

		return $channelUser;
	}

	/**
	 * Returns the status of the user. It returns the most recent version.
	 *
	 * @param integer $userId
	 *
	 * @return ChannelUser|null
	 */
	public function getByUserId($userId) {
		global $wpdb;

		$table = WiseChatInstaller::getChannelUsersTable();
		$sql = sprintf(
			'SELECT * FROM %s WHERE `user_id` = %d ORDER BY `last_activity_time` DESC LIMIT 1;', $table, intval($userId)
		);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $this->populateData($results[0]);
		}

		return null;
	}

	/**
	 * Checks if the user is online (status: active).
	 *
	 * @param integer $userId
	 * @return bool
	 */
	public function isOnline($userId) {
		$status = $this->getByUserId($userId);

		return $status !== null && $status->isActive();
	}

	/**
	 * @param integer $userId
	 * @param boolean $status
	 * @throws Exception
	 */
	public function setStatus($userId, $status) {
		$userStatus = $this->getByUserId($userId);
		if ($userStatus) {
			$userStatus->setActive($status);
			$this->save($userStatus);
		}
	}

	/**
	 * Returns all active unique users.
	 *
	 * @param integer[] $limitToWordPressUsersIds
	 * @return ChannelUser[]
	 */
	public function getAllActive($limitToWordPressUsersIds = array()) {
		global $wpdb;

		$table = WiseChatInstaller::getChannelUsersTable();
		$usersTable = WiseChatInstaller::getUsersTable();
		$sql = sprintf(
			'SELECT cu.* FROM %s AS cu '.
			'LEFT JOIN %s AS u ON (u.id = cu.user_id) '.
			'WHERE cu.active = 1 '.
			(count($limitToWordPressUsersIds) > 0 ? ' AND u.wp_id IN ('.implode(', ', $limitToWordPressUsersIds).')' : '').
			'ORDER BY u.name ASC '.
			'LIMIT 1000;',
			$table, $usersTable
		);
		$channelUsersRaw = $wpdb->get_results($sql);

		/** @var ChannelUser[] $channelUsers */
		$channelUsers = array();
		foreach ($channelUsersRaw as $channelUserRaw) {
			$channelUser = $this->populateData($channelUserRaw);
			$channelUsers[$channelUser->getUserId()] = $channelUser;
		}

		// load related users:
		$users = $this->usersDAO->getAll(array_keys($channelUsers));
		foreach ($users as $user) {
			$channelUsers[$user->getId()]->setUser($user);
		}

		return array_values($channelUsers);
	}

	/**
	 * Returns all active WiseChatChannelUser objects by channel IDs.
	 *
	 * @param integer[] $channelIds
	 * @param integer[] $limitToUsers
	 * @return ChannelUser[]
	 */
	public function getAllActiveByChannelIds($channelIds, $limitToUsers = array()) {
		global $wpdb;

		$table = WiseChatInstaller::getChannelUsersTable();
		$usersTable = WiseChatInstaller::getUsersTable();
		$sql = sprintf(
			'SELECT cu.* FROM %s AS cu '.
			'LEFT JOIN %s AS u ON (u.id = cu.user_id) '.
			'WHERE cu.active = 1 AND cu.channel_id IN (%s) '.
			(count($limitToUsers) > 0 ? ' AND u.wp_id IN ('.implode(', ', $limitToUsers).')' : '').
			'ORDER BY u.name ASC '.
			'LIMIT 1000;',
			$table, $usersTable, implode(', ', $channelIds)
		);
		$channelUsersRaw = $wpdb->get_results($sql);

		/** @var ChannelUser[][] $channelUsersToComplete */
		$channelUsersToComplete = array();
		$channelUsers = array();
		foreach ($channelUsersRaw as $channelUserRaw) {
			$channelUser = $this->populateData($channelUserRaw);
			$channelUsers[] = $channelUser;
			$channelUsersToComplete[$channelUser->getUserId()][] = $channelUser;
		}

		// load related users:
		$users = $this->usersDAO->getAll(array_keys($channelUsersToComplete));
		foreach ($users as $user) {
			if (array_key_exists($user->getId(), $channelUsersToComplete)) {
				foreach ($channelUsersToComplete[$user->getId()] as $channelUser) {
					$channelUser->setUser($user);
				}
			}
		}

		return $channelUsers;
	}

	/**
	 * Updates the status of statuses older than the given amount of seconds.
	 *
	 * @param boolean $active
	 * @param integer $time
	 */
	public function updateActiveForOlderByLastActivityTime($active, $time) {
		global $wpdb;

		$table = WiseChatInstaller::getChannelUsersTable();
		$threshold = time() - $time;

		$wpdb->get_results(
			sprintf("UPDATE %s SET active = %d WHERE `last_activity_time` < %d;", $table, $active === true ? 1 : 0, $threshold)
		);
	}

	/**
	 * Deletes statuses older than the given amount of seconds.
	 *
	 * @param integer $time
	 */
	public function deleteOlderByLastActivityTime($time) {
		global $wpdb;

		$table = WiseChatInstaller::getChannelUsersTable();
		$threshold = time() - $time;

		$wpdb->get_results(
			sprintf("DELETE FROM %s WHERE `last_activity_time` < %s;", $table, $threshold)
		);
	}

	/**
	 * Converts stdClass object into WiseChatChannelUser object.
	 *
	 * @param \stdClass $channelUserRaw
	 *
	 * @return ChannelUser
	 */
	private function populateData($channelUserRaw) {
		$channelUser = new ChannelUser();
		if ($channelUserRaw->id > 0) {
			$channelUser->setId(intval($channelUserRaw->id));
		}
		if ($channelUserRaw->user_id > 0) {
			$channelUser->setUserId(intval($channelUserRaw->user_id));
		}
		if ($channelUserRaw->channel_id > 0) {
			$channelUser->setChannelId(intval($channelUserRaw->channel_id));
		}
		$channelUser->setActive($channelUserRaw->active == '1');
		if ($channelUserRaw->last_activity_time > 0) {
			$channelUser->setLastActivityTime(intval($channelUserRaw->last_activity_time));
		}

		return $channelUser;
	}

	/**
	 * Returns the number of online users.
	 *
	 * @return integer
	 */
	public function countOnlineUsers() {
		global $wpdb;

		$table = WiseChatInstaller::getChannelUsersTable();
		$sql = sprintf('SELECT count(DISTINCT `user_id`) AS quantity FROM %s WHERE `active` = 1;', $table);
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			$result = $results[0];

			return $result->quantity;
		}

		return 0;
	}

	/**
	* Checks whether the given user name belongs to a different user.
	*
	* @param string $userName Username to check
	* @param boolean $includeActiveOnly
	*
	* @return boolean
	*/
	public function isUserNameOccupied($userName, $includeActiveOnly = false) {
		global $wpdb;

		$userName = addslashes($userName);
		$table = WiseChatInstaller::getChannelUsersTable();
		$usersTable = WiseChatInstaller::getUsersTable();
		$activeOnlyCondition = $includeActiveOnly ? ' AND usc.active = 1 ' : '';
		$sql = sprintf(
			'SELECT * '.
			'FROM %s AS usc '.
			'LEFT JOIN %s AS us ON (usc.user_id = us.id) '.
			'WHERE us.name = "%s" %s LIMIT 1;',
			$table, $usersTable, $userName, $activeOnlyCondition
		);
		$results = $wpdb->get_results($sql);
		
		return is_array($results) && count($results) > 0;
	}
	
}