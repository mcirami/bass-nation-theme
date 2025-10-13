<?php

namespace Kainex\WiseChatPro\Services;

use Kainex\WiseChatPro\DAO\WiseChatNotificationsDAO;
use Kainex\WiseChatPro\DAO\WiseChatSentNotificationsDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\Model\WiseChatNotification;
use Kainex\WiseChatPro\Model\WiseChatSentNotification;

/**
 * WiseChat notifications services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatNotificationsService {

	/**
	 * @var WiseChatChannelsService
	 */
	private $channelsService;

	/**
	 * @var WiseChatNotificationsDAO
	 */
	private $notificationsDAO;

	/**
	 * @var WiseChatSentNotificationsDAO
	 */
	private $sentNotificationsDAO;

	/**
	 * @param WiseChatChannelsService $channelsService
	 * @param WiseChatNotificationsDAO $notificationsDAO
	 * @param WiseChatSentNotificationsDAO $sentNotificationsDAO
	 */
	public function __construct(WiseChatChannelsService $channelsService, WiseChatNotificationsDAO $notificationsDAO, WiseChatSentNotificationsDAO $sentNotificationsDAO) {
		$this->channelsService = $channelsService;
		$this->notificationsDAO = $notificationsDAO;
		$this->sentNotificationsDAO = $sentNotificationsDAO;
	}

	/**
	 * Sends all notifications for message.
	 *
	 * @param Message $message
	 * @param Channel $channel
	 */
	public function send($message, $channel) {
		foreach ($this->notificationsDAO->getAll() as $notification) {
			$this->sendNotification($message, $channel, $notification);
		}
	}

	/**
	 * Sends the notification.
	 *
	 * @param Message $message
	 * @param Channel $channel
	 * @param WiseChatNotification $notification
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
	 * @param WiseChatNotification $notification
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

			$sendNotificationFlag = false;

			if ($notification->getAction() == 'message') {
				if (!$this->sentNotificationsDAO->wasNotificationSend($notification->getId(), $channel->getId(), $timeRange)) {
					$sendNotificationFlag = true;
				}
			}

			if ($notification->getAction() == 'message-of-user') {
				if (!$this->sentNotificationsDAO->wasNotificationSendForUser($notification->getId(), $channel->getId(), $message->getUserId(), $timeRange)) {
					$sendNotificationFlag = true;
				}
			}

			if (!$sendNotificationFlag) {
				return;
			}
		}

		$sentNotification = new WiseChatSentNotification();
		$sentNotification->setNotificationId($notification->getId());
		$sentNotification->setSentTime(time());
		$sentNotification->setChannelId($channel->getId());
		if ($notification->getAction() == 'message-of-user') {
			$sentNotification->setUserId($message->getUserId());
		}
		$this->sentNotificationsDAO->save($sentNotification);

		$channel = $this->channelsService->get($message->getChannelId());
		if (!$channel) {
			return;
		}

		// send e-mail:
		$templateData = array(
			'${user}' => $message->getUserName(),
			'{user}' => $message->getUserName(),
			'${message}' => $message->getText(),
			'{message}' => $message->getText(),
			'${channel}' => $channel->getName(),
			'{channel}' => $channel->getName()
		);
		$emailSubject = str_replace(array_keys($templateData), array_values($templateData), $notification->getDetails()['subject']);
		$emailBody = str_replace(array_keys($templateData), array_values($templateData), $notification->getDetails()['content']);

		wp_mail($notification->getDetails()['recipientEmail'], $emailSubject, $emailBody);
	}
}