<?php

namespace Kainex\WiseChatPro\Widgets;

use Kainex\WiseChatPro\Shortcodes\WiseChatLiveChatShortcode;
use Kainex\WiseChatPro\WiseChat;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Wise Chat live chat widget.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatLiveChatWidget extends \WP_Widget {

	/**
	* @var WiseChatOptions
	*/
	protected $options;

	/** @var WiseChat $wiseChat */
	private $wiseChat;

	/**
	 * @param WiseChatOptions $options
	 */
	public function __construct(WiseChatOptions $options, WiseChat $wiseChat) {
		$widgetOps = array('classname' => 'WiseChatLiveChatWidget', 'description' => 'Displays Wise Chat in live chat mode' );
		parent::__construct('WiseChatLiveChatWidget', 'Wise Chat Live Chat', $widgetOps);
		$this->options = $options;
		$this->wiseChat = $wiseChat;
	}

	public function form($instance) {
		$instance = wp_parse_args((array) $instance, array('options' => ''));

		$options = $instance['options'];
		?>
            <p>
                <label for="<?php echo $this->get_field_id('options'); ?>">
                    Advanced options: <input class="widefat" id="<?php echo $this->get_field_id('options'); ?>"
                                    name="<?php echo $this->get_field_name('options'); ?>"
                                    type="text" value="<?php echo esc_attr($options); ?>" />
                </label>
            </p>
		<?php
	}

	public function update($newInstance, $oldInstance) {
		$instance = $oldInstance;
		$instance['options'] = $newInstance['options'];

		return $instance;
	}

	public function widget($args, $instance) {
		extract($args, EXTR_SKIP);

		$options = $instance['options'];

		$parsedOptions = shortcode_parse_atts($options);
		if (!is_array($parsedOptions)) {
			$parsedOptions = array();
		}

		$attributes = array_merge(WiseChatLiveChatShortcode::LIVE_CHAT_OPTIONS, $parsedOptions);
		$attributes['auto_open'] = (array) $this->options->getOption('live_chat_operators', array());
		$attributes['message_no_chats'] = $attributes['message_no_channels'] = __('No live chat operators available.', 'wise-chat-pro');
		$attributes['auth_username_intro_template'] = $this->options->getOption('live_chat_intro_template', '');
		$attributes['direct_channel_title'] = __('Chat', 'wise-chat-pro');

		echo $this->wiseChat->getRenderedShortcode($attributes);
	}
}