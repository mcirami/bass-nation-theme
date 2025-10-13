<?php

namespace Kainex\WiseChatPro\Services;

use Exception;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\UserBansDAO;
use Kainex\WiseChatPro\DAO\WiseChatMessagesDAO;
use Kainex\WiseChatPro\Model\UserBan;
use Kainex\WiseChatPro\Model\WiseChatUser;
use Kainex\WiseChatPro\Services\User\WiseChatActions;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * WiseChat bans services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class UserBansService extends UserBansDAO {

	private WiseChatActions $actions;
	private WiseChatUsersDAO $usersDAO;
	private WiseChatMessagesDAO $messagesDAO;
	private WiseChatAuthentication $authentication;
	private WiseChatOptions $options;

	/**
	 * @param WiseChatActions $actions
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatMessagesDAO $messagesDAO
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatOptions $options
	 */
	public function __construct(WiseChatActions $actions, WiseChatUsersDAO $usersDAO, WiseChatMessagesDAO $messagesDAO, WiseChatAuthentication $authentication, WiseChatOptions $options) {
		$this->actions = $actions;
		$this->usersDAO = $usersDAO;
		$this->messagesDAO = $messagesDAO;
		$this->authentication = $authentication;
		$this->options = $options;
	}

	/**
	 * Bans the user by message ID.
	 *
	 * @param integer $messageId
	 *
	 * @throws Exception If the message or user was not found
	 */
	public function banByMessageId($messageId) {
		$message = $this->messagesDAO->get($messageId);
		if ($message === null) {
			throw new Exception('Message was not found');
		}

		$mode = $this->options->getOption('bans_mode', 'userId');
		$user = $this->usersDAO->get($message->getUserId());
		if ($user !== null) {
			if ($mode === 'userId') {
				$this->banUser($user);
			} else {
				$this->banIP($user->getIp());
			}

			$this->actions->publishAction('reload', array(), $user->getId());

			return;
		}

		throw new Exception('User was not found');
	}


	/**
	 * Creates and saves a new ban on IP address if the IP was not banned previously.
	 *
	 * @param string $ip Given IP address
	 *
	 * @return boolean Returns true the ban was created
	 * @throws Exception
	 */
	public function banIP($ip) {
		if ($this->getByIp($ip) === null) {
			$userBan = new UserBan();
			$userBan->setCreated(time());
			$userBan->setIp($ip);
			$this->save($userBan);

			/**
			 * Fires once IP address has been banned.
			 *
			 * @since 2.3.2
			 *
			 * @param string $ip Banned IP address
			 */
			do_action("wc_ip_banned", $ip);

			return true;
		}

		return false;
	}

	/**
	 * Creates and saves a ban.
	 *
	 * @param WiseChatUser $user
	 *
	 * @return boolean Returns true the ban was created
	 * @throws Exception
	 */
	public function banUser($user) {
		if ($this->getByUserId($user->getId()) === null) {
			$userBan = new UserBan();
			$userBan->setCreated(time());
			$userBan->setIp($user->getIp());
			$userBan->setUserId($user->getId());
			$this->save($userBan);

			/**
			 * Fires once user has been banned.
			 *
			 * @since 2.3.2
			 *
			 * @param integer $userId Banned user
			 */
			do_action("wc_user_banned", $user->getId());

			return true;
		}

		return false;
	}

	/**
	 * Checks if user is banned,
	 *
	 * @return bool
	 */
	public function isBanned() {
		$ip = '';
		if (is_array($_SERVER) && array_key_exists('SERVER_ADDR', $_SERVER)) {
			$ip = $_SERVER['SERVER_ADDR'];
		}
		if (is_array($_SERVER) && array_key_exists('LOCAL_ADDR', $_SERVER)) {
			$ip = $_SERVER['LOCAL_ADDR'];
		}

		$mode = $this->options->getOption('bans_mode', 'userId');
		if ($mode === 'userId') {
			if ($this->authentication->isAuthenticated()) {
				return $this->getByUserId($this->authentication->getUserIdOrNull()) !== null;
			} else {
				return $this->getByIp($ip) !== null;
			}
		} else {
			return $this->getByIp($ip) !== null;
		}
	}

}
