<?php

namespace Kainex\WiseChatPro\Services\Message;

use Exception;
use Kainex\WiseChatPro\DAO\Message\WiseChatMessageReactionDAO;
use Kainex\WiseChatPro\DAO\Message\WiseChatMessageReactionLogDAO;
use Kainex\WiseChatPro\Model\Message\Message;
use Kainex\WiseChatPro\Model\Message\MessageReaction;
use Kainex\WiseChatPro\Model\Message\MessageReactionLog;
use Kainex\WiseChatPro\Services\User\WiseChatActions;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * WiseChat messages reactions services.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatMessageReactionsService {

	/**
	 * @var WiseChatActions
	 */
	protected $actions;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	 * @var WiseChatMessageReactionDAO
	 */
	private $reactionDAO;

	/**
	 * @var WiseChatMessageReactionLogDAO
	 */
	private $reactionLogDAO;

	/**
	 * @var WiseChatOptions
	 */
	private $options;

	/**
	 * @var array
	 */
	private $cacheReactionByMessage = array();

	/**
	 * @var array
	 */
	private $cacheUserReactionsByMessage;

	/**
	 * @param WiseChatActions $actions
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatMessageReactionDAO $reactionDAO
	 * @param WiseChatMessageReactionLogDAO $reactionLogDAO
	 * @param WiseChatOptions $options
	 */
	public function __construct(WiseChatActions $actions, WiseChatAuthentication $authentication, WiseChatMessageReactionDAO $reactionDAO, WiseChatMessageReactionLogDAO $reactionLogDAO, WiseChatOptions $options) {
		$this->actions = $actions;
		$this->authentication = $authentication;
		$this->reactionDAO = $reactionDAO;
		$this->reactionLogDAO = $reactionLogDAO;
		$this->options = $options;
	}

	/**
	 * @return array
	 */
	public function getInitialConfiguration() {
		if ($this->isEnabled()) {
			$reactionsList = array();

			switch ($this->options->getOption('reactions_mode', 'like')) {
				case 'like':
					$reactionsList = array($this->getDefaultReaction('like'));
					break;
				case 'like_love':
					$reactionsList = array($this->getDefaultReaction('like'), $this->getDefaultReaction('love'));
					break;
				case 'like_love_sad':
					$reactionsList = array($this->getDefaultReaction('like'), $this->getDefaultReaction('love'), $this->getDefaultReaction('sad'));
					break;
				case 'custom':
					$custom = json_decode($this->options->getOption('reactions_custom', '[]'), true);
					if (is_array($custom)) {
						foreach ($custom as $reaction) {
							$imageUrl = wp_get_attachment_url($reaction['image']);
							$imageSmUrl = wp_get_attachment_url($reaction['imageSm']);

							if ($imageUrl && $imageSmUrl && $reaction['action'] && $reaction['active']) {
								$reactionsList[] = array(
									'id' => intval($reaction['id']),
									'action' => $reaction['action'],
									'active' => $reaction['active'],
									'icon' => $imageUrl,
									'iconSm' => $imageSmUrl,
								);
							}
						}
					}
			}

			return array(
				'enabled' => true,
				'mode' => $this->options->getOption('reactions_mode', 'like'),
				'group' => $this->options->isOptionEnabled('reactions_buttons_group', false),
				'buttonMode' => $this->options->getOption('reactions_buttons_mode', 'icon_text'),
				'list' => $reactionsList
			);
		}

		return array(
			'enabled' => false
		);
	}

	private function getIconURL($icon) {
		return $this->options->getIconsURL().'reactions/'.$icon;
	}

	private function getDefaultReaction($reactionAlias) {
		switch ($reactionAlias) {
			case 'like':
				$icon = $this->getIconURL('like.svg');
				switch ($this->options->getOption('theme', 'balloon')) {
					case 'colddark':
						$icon = $this->getIconURL('like-light.svg');
						break;
					case 'balloon':
						$icon = $this->getIconURL('like-dark.svg');
						break;
				}

				return array(
					'id' => 1,
					'class' => 'wcReactionLike',
					'action' => __('Like', 'wise-chat-pro'),
					'active' => __('I like it', 'wise-chat-pro'),
					'icon' => $icon,
					'iconSm' => $icon
				);
			case 'love':
				return array(
					'id' => 2,
					'class' => 'wcReactionLove',
					'action' => __('Love', 'wise-chat-pro'),
					'active' => __('Love', 'wise-chat-pro'),
					'icon' => $this->getIconURL('love.svg'),
					'iconSm' => $this->getIconURL('love.svg')
				);
			case 'sad':
				return array(
					'id' => 3,
					'class' => 'wcReactionSad',
					'action' => __('Sad', 'wise-chat-pro'),
					'active' => __('Sad', 'wise-chat-pro'),
					'icon' => $this->getIconURL('sad.svg'),
					'iconSm' => $this->getIconURL('sad.svg')
				);
		}

		return null;
	}

	/**
	 * If reactions features are enabled at all.
	 *
	 * @return bool
	 */
	public function isEnabled() {
		return $this->options->getOption('reactions_mode', 'like') ? true : false;
	}

	/**
	 * @param Message $message
	 * @param integer $reactionId
	 * @throws Exception
	 */
	public function toggleReaction($message, $reactionId) {
		if (!$this->isEnabled()) {
			return;
		}

		if ($reactionId < 1 || $reactionId > 7) {
			throw new \Exception('Unknown reaction');
		}

		$reaction = $this->reactionDAO->getByMessageId($message->getId());
		$reactionLogs = $this->reactionLogDAO->getAllByMessageIdAndUserIdAndReactionId($message->getId(), $this->authentication->getUserIdOrNull(), $reactionId);
		if (!$reaction) {
			$reaction = new MessageReaction();
			$reaction->setMessageId($message->getId());
		}
		$reaction->setUpdated(time());

		$getter = 'getReaction'.$reactionId;
		$setter = 'setReaction'.$reactionId;

		if (count($reactionLogs) > 0) {
			$newValue = $reaction->$getter() - 1;
			$reaction->$setter($newValue >= 0 ? $newValue : 0);

			// remove log:
			$this->reactionLogDAO->deleteAllByMessageIdAndUserIdAndReactionId($message->getId(), $this->authentication->getUserIdOrNull(), $reactionId);
		} else {
			$newValue = $reaction->$getter() + 1;
			$reaction->$setter($newValue >= 0 ? $newValue : 0);

			// add reaction log entry:
			$reactionLog = new MessageReactionLog();
			$reactionLog->setMessageId($message->getId());
			$reactionLog->setUserId($this->authentication->getUserIdOrNull());
			$reactionLog->setReactionId($reactionId);
			$reactionLog->setTime(time());
			$this->reactionLogDAO->save($reactionLog);
		}
		$this->reactionDAO->save($reaction);
	}

	/**
	 * Reads reactions corresponding to the messages and caches them.
	 *
	 * @param Message[] $messages
	 */
	public function cacheReactions($messages) {
		if (!$this->isEnabled()) {
			return;
		}

		foreach (array_chunk($messages, 100) as $messagesChunk) {
			$ids = array_map(function($message) { return $message->getId(); }, $messagesChunk);
			foreach ($ids as $id) {
				$this->cacheReactionByMessage[$id] = null;
				$this->cacheUserReactionsByMessage[$id] = null;
			}
			$reactions = $this->reactionDAO->getAllByMessageIds($ids);
			foreach ($reactions as $reaction) {
				$this->cacheReactionByMessage[$reaction->getMessageId()] = $reaction;
			}

			$reactionsLogs = $this->reactionLogDAO->getAllByMessageIdsAndUserId($ids, $this->authentication->getUserIdOrNull());
			foreach ($reactionsLogs as $reactionsLog) {
				if (!array_key_exists($reactionsLog->getMessageId(), $this->cacheUserReactionsByMessage)) {
					$this->cacheUserReactionsByMessage[$reactionsLog->getMessageId()] = array();
				}
				$this->cacheUserReactionsByMessage[$reactionsLog->getMessageId()][] = $reactionsLog->getReactionId();
			}
		}
	}

	/**
	 * Reads the reaction of a particular message. The method uses the internal cache.
	 *
	 * @param Message $message
	 * @return MessageReaction|null
	 */
	public function getReactionByMessage($message) {
		if (!$this->isEnabled()) {
			return null;
		}

		if (!array_key_exists($message->getId(), $this->cacheReactionByMessage)) {
			$this->cacheReactions(array($message));
		}

		return $this->cacheReactionByMessage[$message->getId()];
	}

	/**
	 * Reads the reaction of a particular message. The method uses the internal cache.
	 *
	 * @param Message $message
	 * @return MessageReaction|null
	 */
	public function getCurrentUserReactionByMessage($message) {
		if (!$this->isEnabled()) {
			return null;
		}

		if (!array_key_exists($message->getId(), $this->cacheUserReactionsByMessage)) {
			$this->cacheReactions(array($message));
		}

		return $this->cacheUserReactionsByMessage[$message->getId()];
	}

	/**
	 * Reads the reaction of a particular message. The method uses the internal cache.
	 *
	 * @param Message $message
	 * @param bool $includeCounters
	 * @param bool $includeOwn
	 * @return array|null
	 */
	public function getReactionsAsPlainArray($message, $includeCounters = true, $includeOwn = true) {
		if (!$this->isEnabled()) {
			return null;
		}

		$output = array();
		if ($includeCounters) {
			$reaction = $this->getReactionByMessage($message);
			$counters = null;
			if ($reaction) {
				$counters = array(
					1 => $reaction->getReaction1(),
					2 => $reaction->getReaction2(),
					3 => $reaction->getReaction3(),
					4 => $reaction->getReaction4(),
					5 => $reaction->getReaction5(),
					6 => $reaction->getReaction6(),
					7 => $reaction->getReaction7(),
				);
				$counters = array_filter($counters);
			}

			$output['counters'] = $counters;
		}
		if ($includeOwn) {
			$output['own'] = $this->getCurrentUserReactionByMessage($message);
		}

		return $output;
	}

	public function deleteAll() {
		$this->reactionDAO->deleteAll();
		$this->reactionLogDAO->deleteAll();
	}

}