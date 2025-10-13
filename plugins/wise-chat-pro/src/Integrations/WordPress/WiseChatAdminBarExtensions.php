<?php

namespace Kainex\WiseChatPro\Integrations\WordPress;

use Kainex\WiseChatPro\DAO\WiseChatChannelsDAO;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\Services\WiseChatChannelsService;
use Kainex\WiseChatPro\Services\WiseChatPendingChatsService;
use Kainex\WiseChatPro\WiseChatAdminPages;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Extensions to the admin bar profile.
 */
class WiseChatAdminBarExtensions {

	/**
	 * @var WiseChatPendingChatsService
	 */
	private $pendingChatsService;

	/**
	 * @var WiseChatAuthentication
	 */
	private $authentication;

	/**
	 * @var WiseChatChannelsDAO
	 */
	private $channelsDAO;

	/**
	* @var WiseChatOptions
	*/
	private $options;

	/**
	 * @param WiseChatPendingChatsService $pendingChatsService
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatChannelsDAO $channelsDAO
	 * @param WiseChatOptions $options
	 */
	public function __construct(WiseChatPendingChatsService $pendingChatsService, WiseChatAuthentication $authentication, WiseChatChannelsDAO $channelsDAO, WiseChatOptions $options) {
		$this->pendingChatsService = $pendingChatsService;
		$this->authentication = $authentication;
		$this->channelsDAO = $channelsDAO;
		$this->options = $options;
	}

	public function installChat($wp_admin_bar) {
		if (!is_user_logged_in()) {
			return;
		}

		$wp_admin_bar->add_node(array(
			'id' => 'wise_chat_pro_chat',
			'title' => 'Chat'.$this->getMessagesCounter(),
			'href' => admin_url('admin.php?page=wise-chat-pro-chat'),
		));
	}

	public function installLiveChat($wp_admin_bar) {
		if (!$this->options->isOptionEnabled('admin_bar_chat_menu_enabled', true) || !is_user_logged_in() || !current_user_can(WiseChatAdminPages::CHAT_PAGE_CAPABILITY)) {
			return;
		}

		$wp_admin_bar->add_node(array(
			'id' => 'wise_chat_pro',
			'title' => 'Chat'.$this->getMessagesCounter(),
			'href' => admin_url('admin.php?page=wise-chat-pro-chat-page'),
		));

		if (current_user_can('manage_options')) {
			$wp_admin_bar->add_node(array(
				'parent' => 'wise_chat_pro',
				'id' => 'wise_chat_pro_settings',
				'title' => 'Settings',
				'href' => admin_url('options-general.php?page=wise-chat-admin'),
				'meta' => false
			));
		}
	}

	protected function getMessagesCounter() {
		$pmChannel = $this->channelsDAO->getByName(WiseChatChannelsService::PRIVATE_MESSAGES_CHANNEL);
		if (!$pmChannel) {
			return '';
		}

		$this->pendingChatsService->getUnreadMessages($this->authentication->getUser(), $pmChannel);

		$duplicationCheck = array();
		$unread = $this->pendingChatsService->getUnreadMessages($this->authentication->getUser(), $pmChannel);
		foreach ($unread as $message) {
			$duplicationCheck[$message->getUserId()] = 1;
		}

		$messagesCount = count($duplicationCheck);

		if (!$messagesCount) {
			return '';
		}

		$text = sprintf(_n('%s message', '%s messages', $messagesCount, 'wise-chat-pro'), number_format_i18n($messagesCount));

		return sprintf(' <div class="wp-core-ui wp-ui-notification wc-counter"><span aria-hidden="true">%d</span><span class="screen-reader-text">%s</span></div>', $messagesCount, $text);
	}

}