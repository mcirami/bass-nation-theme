<?php

namespace Kainex\WiseChatPro\DAO\Message;

use Exception;
use Kainex\WiseChatPro\Model\Message\MessageReactionLog;
use Kainex\WiseChatPro\WiseChatInstaller;

/**
 * Wise Chat message reaction log DAO.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMessageReactionLogDAO {

	/**
	* @var string
	*/
	private $table;

	public function __construct() {
		$this->table = WiseChatInstaller::getReactionsLogTable();
	}

	/**
	 * Creates or updates the reaction and returns it.
	 *
	 * @param MessageReactionLog $reactionLog
	 *
	 * @return MessageReactionLog
	 * @throws Exception On validation error
	 */
	public function save($reactionLog) {
		global $wpdb;

		// prepare action data:
		$columns = array(
			'time' => $reactionLog->getTime(),
			'message_id' => $reactionLog->getMessageId(),
			'user_id' => $reactionLog->getUserId(),
			'reaction_id' => $reactionLog->getReactionId(),
		);

		// update or insert:
		if ($reactionLog->getId() !== null) {
			$wpdb->update($this->table, $columns, array('id' => $reactionLog->getId()), '%s', '%d');
		} else {
			$wpdb->insert($this->table, $columns);
			$reactionLog->setId($wpdb->insert_id);
		}

		return $reactionLog;
	}

	/**
	 * Returns reaction logs by message ID.
	 *
	 * @param integer $id
	 *
	 * @return MessageReactionLog[]
	 */
	public function getAllByMessageId($id) {
		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE message_id = %d ORDER BY time ASC;', $this->table, intval($id));
		$results = $wpdb->get_results($sql);
		$logs = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$logs[] = $this->populateData($result);
			}
		}

		return $logs;
	}

	/**
	 * Returns reaction logs by message ID and user ID.
	 *
	 * @param integer $messageId
	 * @param integer $userId
	 * @return MessageReactionLog[]
	 */
	public function getAllByMessageIdAndUserId($messageId, $userId) {
		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE message_id = %d AND user_id = %d ORDER BY time ASC;', $this->table, intval($messageId), intval($userId));
		$results = $wpdb->get_results($sql);
		$logs = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$logs[] = $this->populateData($result);
			}
		}

		return $logs;
	}

	/**
	 * Returns reaction logs by message IDs and user ID.
	 *
	 * @param integer[] $messageIds
	 * @param integer $userId
	 * @return MessageReactionLog[]
	 */
	public function getAllByMessageIdsAndUserId($messageIds, $userId) {
		if (count($messageIds) === 0) {
			return array();
		}

		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE message_id IN (%s) AND user_id = %d ORDER BY time ASC;', $this->table, implode(', ', $messageIds), intval($userId));
		$results = $wpdb->get_results($sql);
		$logs = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$logs[] = $this->populateData($result);
			}
		}

		return $logs;
	}

	/**
	 * Returns reaction logs by message ID and user ID and reaction ID.
	 *
	 * @param integer $messageId
	 * @param integer $userId
	 * @param integer$reactionId
	 * @return MessageReactionLog[]
	 */
	public function getAllByMessageIdAndUserIdAndReactionId($messageId, $userId, $reactionId) {
		global $wpdb;

		$sql = sprintf('SELECT * FROM %s WHERE message_id = %d AND user_id = %d AND reaction_id = %d ORDER BY time ASC;', $this->table, intval($messageId), intval($userId), intval($reactionId));
		$results = $wpdb->get_results($sql);
		$logs = array();
		if (is_array($results)) {
			foreach ($results as $result) {
				$logs[] = $this->populateData($result);
			}
		}

		return $logs;
	}

	/**
	 * Deletes reaction logs by message ID and user ID and reaction ID.
	 *
	 * @param integer $messageId
	 * @param integer $userId
	 * @param integer$reactionId
	 */
	public function deleteAllByMessageIdAndUserIdAndReactionId($messageId, $userId, $reactionId) {
		global $wpdb;

		$sql = sprintf('DELETE FROM %s WHERE message_id = %d AND user_id = %d AND reaction_id = %d;', $this->table, intval($messageId), intval($userId), intval($reactionId));
		$wpdb->get_results($sql);
	}

	/**
	 * Removes all reactions logs.
	 */
	public function deleteAll() {
		global $wpdb;

        $wpdb->get_results(sprintf("DELETE FROM %s;", $this->table));
	}

	/**
	 * Converts raw object into WiseChatMessageReactionLog object.
	 *
	 * @param \stdClass $rawData
	 *
	 * @return MessageReactionLog
	 */
	private function populateData($rawData) {
		$reaction = new MessageReactionLog();
		if ($rawData->id > 0) {
			$reaction->setId(intval($rawData->id));
		}
		$reaction->setTime($rawData->time);
		$reaction->setMessageId($rawData->message_id);
		$reaction->setUserId($rawData->user_id);
		$reaction->setReactionId(intval($rawData->reaction_id));

		return $reaction;
	}
}