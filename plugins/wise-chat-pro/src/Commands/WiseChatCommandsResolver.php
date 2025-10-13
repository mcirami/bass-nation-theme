<?php

namespace Kainex\WiseChatPro\Commands;

use Exception;
use Kainex\WiseChatPro\Container;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Model\WiseChatUser;
use Kainex\WiseChatPro\Services\WiseChatMessagesService;

/**
 * WiseChat commands resolver.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatCommandsResolver {
	
	/**
	* @var WiseChatUsersDAO
	*/
	private $usersDAO;

	/**
	 * @var WiseChatMessagesService
	 */
	private $messagesService;

	/**
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatMessagesService $messagesService
	 */
	public function __construct(WiseChatUsersDAO $usersDAO, WiseChatMessagesService $messagesService) {
		$this->usersDAO = $usersDAO;
		$this->messagesService = $messagesService;
	}

	/**
	* Checks whether given message is an admin command and executes it if so.
	*
	* @param WiseChatUser $user
	* @param WiseChatUser $systemUser
	* @param Channel $channel Name of the channel
	* @param string $message Content of the possible command
	*
	* @return boolean True if the message is processed and is not needed to be displayed
	*/
	public function resolve($user, $systemUser, $channel, $message) {
		if ($this->isPotentialCommand($message) && $this->usersDAO->isWpUserAdminLogged()) {
			// print typed command (visible only for admins):
			$this->messagesService->addMessage($user, $channel, $message, array(), true);
		
			// execute command:
			$resolver = $this->getCommandResolver($channel, $message);
			if ($resolver !== null) {
				$resolver->execute();
			} else {
				$this->messagesService->addMessage($systemUser, $channel, 'Command not found', array(), true);
			}
		
			return true;
		}
		
		return false;
	}
	
	/**
	* Tokenizes command and returns command resolver.
	*
	* @param Channel $channel Name of the channel
	* @param string $command The command
	*
	* @return WiseChatAbstractCommand
	*/
	private function getCommandResolver($channel, $command) {
        try {
			/** @var WiseChatAbstractCommand $commandObject */
			$commandObject = Container::getInstance()->get($this->getClassNameFromCommand($command));

            $tokens = $this->getTokenizedCommand($command);
            array_shift($tokens);

			$commandObject->setChannel($channel);
			$commandObject->setArguments($tokens);

            return $commandObject;
        } catch (Exception $e) {
            return null;
        }
	}
	
	/**
	* Checks if a text can be recognized as a command.
	*
	* @param string $text The potential command
	*
	* @return boolean
	*/
	private function isPotentialCommand($text) {
		return $text && strpos($text, '/') === 0;
	}
	
	private function getTokenizedCommand($command) {
		$command = trim(trim($command), '/');
		$matches = array();
		preg_match_all('/"(?:\\\\.|[^\\\\"])*"|\S+/', $command, $matches);

		if (is_array($matches) && count($matches) > 0) {
			$matchesResult = array();
			foreach ($matches[0] as $match) {
				$matchesResult[] = trim($match, '"');
			}

			return $matchesResult;
		} else {
			return array();
		}
	}
	
	private function getClassNameFromCommand($command) {
		$tokens = $this->getTokenizedCommand($command);
		$commandName = str_replace('/', '', ucfirst($tokens[0]));
		
		return "\Kainex\WiseChatPro\Commands\WiseChat{$commandName}Command";
	}
}