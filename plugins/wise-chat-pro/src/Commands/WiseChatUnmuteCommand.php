<?php

namespace Kainex\WiseChatPro\Commands;

/**
 * WiseChat command: /unmute
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatUnmuteCommand extends WiseChatAbstractCommand {
	public function execute() {
		$mode = $this->options->getOption('mutes_mode', 'userId');
		$input = isset($this->arguments[0]) ? $this->arguments[0] : null;
		if ($input === null) {
			if ($mode === 'userId') {
				$this->addMessage('Please specify username');
			} else {
				$this->addMessage('Please specify the IP address');
			}
            return;
        }

		$user = $this->usersDAO->getLatestByName($input);
        if ($user === null) {
            $this->addMessage('User was not found');
			return;
        }

		if ($mode === 'userId') {
			$userMute = $this->mutesDAO->getByUserId($user->getId());
	        if ($userMute !== null) {
	            $this->mutesDAO->deleteById($userMute->getId());
	            $this->addMessage("User is no longer muted");
	        } else {
	            $this->addMessage('Could not find muted user');
	        }
		} else {
			$userMute = $this->mutesDAO->getByIp($user->getIp());
	        if ($userMute !== null) {
	            $this->mutesDAO->deleteById($userMute->getId());
	            $this->addMessage("IP address is no longer muted");
	        } else {
	            $this->addMessage('Could not find muted IP address');
	        }
		}
	}
}