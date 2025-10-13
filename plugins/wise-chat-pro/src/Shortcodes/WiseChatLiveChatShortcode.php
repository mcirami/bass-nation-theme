<?php

namespace Kainex\WiseChatPro\Shortcodes;

use Exception;
use Kainex\WiseChatPro\WiseChat;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Shortcode that renders live chat mode.
 * Live chat mode is a combination of options.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatLiveChatShortcode {

	/**
	* @var WiseChatOptions
	*/
	protected $options;

	const LIVE_CHAT_OPTIONS = array(
		'live_chat' => '1',
		'mode' => '1',
        'enable_private_messages' => '1',
        'show_users' => '0',
        'fb_disable_channel' => '1',
        'classic_disable_channel' => '1',
        'users_list_offline_enable' => '0',
        'show_users_counter' => '0',
        'allow_change_user_name' => '0',
        'allow_control_user_notifications' => '0',
        'allow_mute_sound' => '0',
	    'allow_change_text_color' => '0',
	    'mobile_mode_tab_chats_enabled' => '0',
	    'direct_channel_close_confirmation' => '1',
	    'auth_mode' => 'username',
	    'chat_log_off_on_close_last' => '1'
	);

    /**
     * @var WiseChat
     */
    private $wiseChat;

	/**
	 * @param WiseChatOptions $options
	 * @param WiseChat $wiseChat
	 */
	public function __construct(WiseChatOptions $options, WiseChat $wiseChat) {
		$this->options = $options;
		$this->wiseChat = $wiseChat;
	}


	/**
	 * Renders shortcode: [wise-chat-live-chat]
	 *
	 * @param array $attributes
	 * @return string
	 * @throws Exception
	 */
    public function getRenderedShortcode($attributes) {
        if (!is_array($attributes)) {
            $attributes = array();
        }
        
	    $attributes = array_merge(self::LIVE_CHAT_OPTIONS, $attributes);

        $attributes['auto_open'] = (array) $this->options->getOption('live_chat_operators', array());
        $attributes['auth_username_intro_template'] = $this->options->getOption('live_chat_intro_template', '');
        $attributes['direct_channel_title'] = __('Chat', 'wise-chat-pro');
		$attributes['disable_channels_restore'] = true;
	    $attributes['disable_incoming_chats'] = true;
        $attributes['message_no_channels'] = __('No live chat operators available.', 'wise-chat-pro');
        $attributes['message_no_chats'] = __('No live chat operators available.', 'wise-chat-pro');

		return $this->wiseChat->getRenderedShortcode($attributes);
    }
}