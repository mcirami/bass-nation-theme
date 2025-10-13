<?php

namespace Kainex\WiseChatPro\Commands;

/**
 * WiseChat command: /mutes
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMutesCommand extends WiseChatAbstractCommand {
	public function execute() {
		$userMutes = $this->mutesDAO->getAll();
		
		if (is_array($userMutes) && count($userMutes) > 0) {
			$userIDs = array_map(function ($userMute) { return $userMute->getUserId(); }, $userMutes);
			$users = $this->usersDAO->getAll($userIDs);
			$usersMap = array();
			foreach ($users as $user) {
				$usersMap[$user->getId()] = $user->getName();
			}

			$output = array();
			foreach ($userMutes as $userMute) {
				$eta = $userMute->getTime() - time();
				if ($eta > 0) {
					$userName = $userMute->getUserId()
						? (isset($usersMap[$userMute->getUserId()]) ? $usersMap[$userMute->getUserId()] : 'Unknown User')
						: 'All users matching IP address';
					$output[] = sprintf("%s [%s] %s left", $userName, $userMute->getIp(), $this->getTimeSummary($eta));
				}
			}
			
			$this->addMessage("Currently muted users: \n".(count($output) > 0 ? implode("\n", $output) : ' empty list'));
		} else {
			$this->addMessage('No muted users');
		}
	}
	
	private function getTimeSummary($seconds) {
		$dateFirst = new \DateTime("@0");
		$dateSecond = new \DateTime("@$seconds");
		
		return $dateFirst->diff($dateSecond)->format('%a days, %h hours, %i minutes and %s seconds');
	}
}