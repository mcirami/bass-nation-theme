<?php

namespace Kainex\WiseChatPro\Integrations;

use Kainex\WiseChatPro\Container;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\Services\ClientSide\WiseChatClientSide;

/**
 * WiseChat integrations helper class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatHelper {

	/**
	 * TODO: FIX!
	 *
	 * @param integer $wordPressUserId
	 * @return string
	 */
	public static function getDirectChannelId($wordPressUserId) {
		/** @var WiseChatUsersDAO $usersDAO */
		$usersDAO = Container::getInstance()->get(WiseChatUsersDAO::class);

		/** @var WiseChatClientSide $clientSide */
		$clientSide = Container::getInstance()->get(WiseChatClientSide::class);


		$id = $usersDAO->getLatestByWordPressId($wordPressUserId);

		return $clientSide->encryptDirectChannelId($id ? $id->getId() : 'v'.$wordPressUserId);
	}

}