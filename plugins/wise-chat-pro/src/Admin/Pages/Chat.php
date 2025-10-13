<?php

namespace Kainex\WiseChatPro\Admin\Pages;

use Kainex\WiseChatPro\WiseChatOptions;

/**
 * WiseChat admin pages.
 *
 * @author Kainex <contact@kaine.pl>
 */
class Chat {

	/**
	* @var WiseChatOptions
	*/
	protected $options;

	const CHAT_PAGE_CAPABILITY = WISE_CHAT_PRO_SLUG.'_chat_page';

	/**
	 * @param WiseChatOptions $options
	 */
	public function __construct(WiseChatOptions $options) {
		$this->options = $options;
	}

	/**
	* Initializes settings page link in admin menu.
	*/
	public function initialize() {
		add_action('admin_menu', array($this, 'addPages'));
	}

	public function addPages() {
		add_submenu_page(
	        null,
	        'Wise Chat Pro: Chat Page',
	        'Wise Chat Pro: Chat Page',
	        'read',
	        'wise-chat-pro-chat',
	        array($this, 'renderChatPage')
	    );
	}

	/**
	 * Renders chat page.
	 */
	public function renderChatPage() {
		$options = array(
			'browser_mode' => 'recent-with-current',
			'mode' => '0',
	        'enable_private_messages' => '1',
	        'show_users' => '1',
	        'classic_disable_channel' => '1',
	        'users_list_offline_enable' => '1',
	        'show_users_counter' => '0',
	        'allow_change_user_name' => '0',
		    'direct_channel_close_confirmation' => '1',
			'users_list_width' => '25',
			'recent_excerpts_enabled' => '0',
			'recent_status_enabled' => '1',
			'private_message_autofocus' => '0',
			'chat_height' => $this->options->getEncodedOption('live_chat_operators_height', '700px')
		);
		echo wise_chat_shortcode($options);
	}
}