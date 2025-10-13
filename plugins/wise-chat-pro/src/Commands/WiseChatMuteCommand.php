<?php

namespace Kainex\WiseChatPro\Commands;

/**
 * WiseChat command: /mute [userName] [duration]
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMuteCommand extends WiseChatAbstractCommand {
	public function execute() {
		$mode = $this->options->getOption('mutes_mode', 'userId');
		$userName = isset($this->arguments[0]) ? $this->arguments[0] : null;
        if ($userName === null) {
            $this->addMessage('Please specify the user');
            return;
        }
		
        $user = $this->usersDAO->getLatestByName($userName);
        if ($user === null) {
            $this->addMessage('User was not found');
            return;
        }

        $duration = $this->userMutesService->getDurationFromString($this->arguments[1]);
		if ($mode === 'userId') {
			if ($this->userMutesService->muteUser($user, $duration)) {
				$this->addMessage(sprintf('User %s [%s] has been muted, time: %d seconds', $user->getName(), $user->getIp(), $duration));
			} else {
				$this->addMessage("User " . $user->getName() . " is already muted");
			}
		} else {
			if ($this->userMutesService->muteIP($user->getIp(), $duration)) {
				$this->addMessage(sprintf('IP address [%s] has been muted, time: %d seconds', $user->getIp(), $duration));
			} else {
				$this->addMessage("IP address " . $user->getIp() . " is already muted");
			}
		}
	}
}