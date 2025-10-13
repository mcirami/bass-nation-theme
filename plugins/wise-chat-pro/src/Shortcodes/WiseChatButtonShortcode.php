<?php

namespace Kainex\WiseChatPro\Shortcodes;

use Exception;
use Kainex\WiseChatPro\WiseChat;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatButtonShortcode {

	/**
	* @var WiseChatOptions
	*/
	protected $options;

	const CHAT_BUTTON_OPTIONS = array(
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
	    'chat_destroy_on_close_last' => '1'
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
	 * Renders shortcode
	 *
	 * @param array $attributes
	 * @return string
	 * @throws Exception
	 */
    public function getRenderedShortcode($attributes) {
        if (!is_array($attributes)) {
            $attributes = array();
        }

		if (!isset($attributes['to'])) {
			return 'Error: missing "to" attribute';
		}

	    $attributes = array_merge(self::CHAT_BUTTON_OPTIONS, $attributes);
	    $attributes['disable_channels_restore'] = true;
	    $attributes['disable_incoming_chats'] = true;
	    $attributes['auto_start'] = false;
	    $attributes['auto_open_remember'] = false;
        $attributes['auto_open'] = [intval($attributes['to'])];
        $attributes['auth_username_intro_template'] = $this->options->getOption('live_chat_intro_template', '');
		$attributes['message_no_channels'] = __('User is not available.', 'wise-chat-pro');
        $attributes['message_no_chats'] = __('User is not available.', 'wise-chat-pro');

	    $label = htmlspecialchars($attributes['label'] ?? 'Chat');
	    $class = htmlspecialchars($attributes['class'] ?? '');

		return '<a href="#" class="wcChatButton '.$class.'" data-wc-template="'.htmlspecialchars($this->wiseChat->getRenderedShortcode($attributes)).'">'.$label.'</a>';
    }
}