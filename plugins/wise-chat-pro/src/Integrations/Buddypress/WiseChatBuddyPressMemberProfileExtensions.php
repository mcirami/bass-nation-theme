<?php

namespace Kainex\WiseChatPro\Integrations\Buddypress;

use Kainex\WiseChatPro\Shortcodes\WiseChatButtonShortcode;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Wise Chat BuddyPress member profile extensions.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatBuddyPressMemberProfileExtensions {


	private WiseChatOptions $options;
    private WiseChatButtonShortcode $shortcode;

	public function __construct(WiseChatOptions $options, WiseChatButtonShortcode $shortcode) {
		$this->options = $options;
        $this->shortcode = $shortcode;

		$this->setUpHeader();
	}

	private function setUpHeader() {
		if ($this->options->isOptionEnabled('bp_member_profile_chat_button', false)) {
			add_action('bp_init', array($this, 'onInitialize'), 100);
		}
	}
	
	public function onInitialize() {
        add_action('bp_member_header_actions', array($this, 'displayChatButton'), 100);
	}

	public function displayChatButton() {
        $currentUser = wp_get_current_user();
        if (is_user_logged_in() && $currentUser->ID === bp_displayed_user_id()) {
            return;
        }

		$label = __('Chat Message', 'wise-chat-pro');
		$labelNoTags = htmlentities(strip_tags($label));

        $buttonSrc = $this->shortcode->getRenderedShortcode([
            'label' => $labelNoTags,
            'class' => 'send-message wise-chat-send-message',
            'to' => bp_displayed_user_id()
        ]);

		?>
			<div id="wise-chat" class="generic-button">
				<?php echo $buttonSrc; ?>
			</div>
		<?php
	}
}