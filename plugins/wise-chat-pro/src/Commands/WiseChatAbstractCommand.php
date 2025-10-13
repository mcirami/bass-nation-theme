<?php

namespace Kainex\WiseChatPro\Commands;

use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\DAO\UserMutesDAO;
use Kainex\WiseChatPro\DAO\WiseChatChannelUsersDAO;
use Kainex\WiseChatPro\DAO\WiseChatMessagesDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\Services\UserMutesService;
use Kainex\WiseChatPro\Services\WiseChatMessagesService;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * WiseChat abstract command.
 *
 * @author Kainex <contact@kaine.pl>
 */
abstract class WiseChatAbstractCommand {

	/**
	* @var mixed
	*/
	protected $arguments;

	protected Channel $channel;
	protected WiseChatMessagesDAO $messagesDAO;
	protected WiseChatUsersDAO $usersDAO;
	protected WiseChatChannelUsersDAO $channelUsersDAO;
	protected UserMutesDAO $mutesDAO;
	protected WiseChatAuthentication $authentication;
	protected UserMutesService $userMutesService;
	private WiseChatMessagesService $messagesService;
	protected WiseChatOptions $options;

	/**
	 * @param Channel $channel
	 * @param WiseChatMessagesDAO $messagesDAO
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatChannelUsersDAO $channelUsersDAO
	 * @param UserMutesDAO $mutesDAO
	 * @param WiseChatAuthentication $authentication
	 * @param UserMutesService $userMutesService
	 * @param WiseChatMessagesService $messagesService
	 * @param WiseChatOptions $options
	 */
	public function __construct(Channel $channel, WiseChatMessagesDAO $messagesDAO, WiseChatUsersDAO $usersDAO, WiseChatChannelUsersDAO $channelUsersDAO, UserMutesDAO $mutesDAO, WiseChatAuthentication $authentication, UserMutesService $userMutesService, WiseChatMessagesService $messagesService, WiseChatOptions $options) {
		$this->channel = $channel;
		$this->messagesDAO = $messagesDAO;
		$this->usersDAO = $usersDAO;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->mutesDAO = $mutesDAO;
		$this->authentication = $authentication;
		$this->userMutesService = $userMutesService;
		$this->messagesService = $messagesService;
		$this->options = $options;
	}

	public function setChannel(Channel $channel): void {
		$this->channel = $channel;
	}

	public function setArguments($arguments): void {
		$this->arguments = $arguments;
	}
	
	protected function addMessage($message) {
		$this->messagesService->addMessage($this->authentication->getSystemUser(), $this->channel, $message, array(), true, null, null, array('disableCrop' => true));
	}

    /**
     * Executes command using arguments.
     *
     * @return null
     */
    abstract public function execute();
}