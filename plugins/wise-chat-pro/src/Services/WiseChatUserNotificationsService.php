<?php

namespace Kainex\WiseChatPro\Services;

use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatSentNotificationsDAO;
use Kainex\WiseChatPro\DAO\WiseChatUserNotificationsDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\Model\WiseChatSentNotification;
use Kainex\WiseChatPro\Model\WiseChatUserNotification;

/**
 * WiseChat user notifications services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatUserNotificationsService {

	/**
	 * @var WiseChatChannelsService
	 */
	private $channelsService;

	/**
	 * @var WiseChatUserNotificationsDAO
	 */
	private $userNotificationsDAO;

	/**
	 * @var WiseChatSentNotificationsDAO
	 */
	private $sentNotificationsDAO;

	/**
	 * @var WiseChatUsersDAO
	 */
	private $usersDAO;

	/**
	 * @var WiseChatChannelUsersDAO
	 */
	private $channelUsersDAO;

	/**
	 * @var WiseChatHttpRequestService
	 */
	private $httpRequestService;

	/**
	 * @param WiseChatChannelsService $channelsService
	 * @param WiseChatUserNotificationsDAO $userNotificationsDAO
	 * @param WiseChatSentNotificationsDAO $sentNotificationsDAO
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatChannelUsersDAO $channelUsersDAO
	 * @param WiseChatHttpRequestService $httpRequestService
	 */
	public function __construct(WiseChatChannelsService $channelsService, WiseChatUserNotificationsDAO $userNotificationsDAO, WiseChatSentNotificationsDAO $sentNotificationsDAO, WiseChatUsersDAO $usersDAO, WiseChatChannelUsersDAO $channelUsersDAO, WiseChatHttpRequestService $httpRequestService) {
		$this->channelsService = $channelsService;
		$this->userNotificationsDAO = $userNotificationsDAO;
		$this->sentNotificationsDAO = $sentNotificationsDAO;
		$this->usersDAO = $usersDAO;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->httpRequestService = $httpRequestService;
	}

	/**
	 * Sends all notifications for message.
	 *
	 * @param Message $message
	 * @param Channel $channel
	 */
	public function send($message, $channel) {
		foreach ($this->userNotificationsDAO->getAll() as $notification) {
			$this->sendNotification($message, $channel, $notification);
		}
	}

	/**
	 * Sends the notification.
	 *
	 * @param Message $message
	 * @param Channel $channel
	 * @param WiseChatUserNotification $notification
	 */
	private function sendNotification($message, $channel, $notification) {
		if ($notification->getType() == 'email') {
			$this->sendEmailNotification($message, $channel, $notification);
		}
	}

	/**
	 * Sends e-mail notification.
	 *
	 * @param Message $message
	 * @param Channel $channel
	 * @param WiseChatUserNotification $notification
	 */
	private function sendEmailNotification($message, $channel, $notification) {
		$timeRange = null; // seconds
		$limitless = false;
		if ($notification->getFrequency() == 'daily') {
			$timeRange = 24 * 60 * 60;
		}
		if ($notification->getFrequency() == 'hourly') {
			$timeRange = 60 * 60;
		}
		if ($notification->getFrequency() == 'minute') {
			$timeRange = 60;
		}
		if ($notification->getFrequency() == 'limitless') {
			$limitless = true;
		}

		if (!$limitless) {
			if ($timeRange === null) {
				return;
			}

			if ($this->sentNotificationsDAO->wasNotificationSendForUser($notification->getId(), $channel->getId(), $message->getUserId(), $timeRange)) {
				return;
			}
		}

		$recipientUser = $this->usersDAO->get($message->getRecipientId());
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

		$recipientWordPressUser = $this->usersDAO->getWpUserByID($recipientUser->getWordPressId());
		if ($recipientWordPressUser === null) {
			return;
		}

		$sentNotification = new WiseChatSentNotification();
		$sentNotification->setNotificationId($notification->getId());
		$sentNotification->setSentTime(time());
		$sentNotification->setChannelId($channel->getId());
		$sentNotification->setUserId($message->getUserId());
		$this->sentNotificationsDAO->save($sentNotification);

		$channel = $this->channelsService->get($message->getChannelId());
		if (!$channel) {
			return;
		}

		// send the e-mail:
		$templateData = array(
			'${recipient}' => $recipientWordPressUser->display_name,
			'{recipient}' => $recipientWordPressUser->display_name,
			'${recipient-email}' => $recipientWordPressUser->user_email,
			'{recipient-email}' => $recipientWordPressUser->user_email,
			'${sender}' => strip_tags($message->getUserName()),
			'{sender}' => strip_tags($message->getUserName()),
			'${message}' => $message->getText(),
			'{message}' => $message->getText(),
			'${channel}' => $channel->getName(),
			'{channel}' => $channel->getName(),
			'${link}' => $this->httpRequestService->getReferrerURL(),
			'{link}' => $this->httpRequestService->getReferrerURL()
		);
		$emailSubject = str_replace(array_keys($templateData), array_values($templateData), $notification->getDetails()['subject']);
		$emailBody = str_replace(array_keys($templateData), array_values($templateData), $notification->getDetails()['content']);

		wp_mail($recipientWordPressUser->user_email, $emailSubject, $emailBody);
	}
}