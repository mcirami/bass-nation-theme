<?php

namespace Kainex\WiseChatPro\Integrations\WordPress;

/**
 * Extensions to user profile.
 */
class WiseChatUsersExtensions {

	public function install() {
		if (defined('WISE_CHAT_PRO_VERSION_LIVE')) {
			add_action('show_user_profile', array($this, 'installLiveChatFields'));
			add_action('edit_user_profile', array($this, 'installLiveChatFields'));

			add_action('personal_options_update', array($this, 'saveLiveChatFields'));
			add_action('edit_user_profile_update', array($this, 'saveLiveChatFields'));
		}
	}

	public function installLiveChatFields( $user ) { ?>
	    <h3>Wise Chat Live Add-ons</h3>

	    <table class="form-table">
			 <tr>
	            <th colspan="2"><label for="wc_live_chat_welcome_message">Live Chat Welcome Message</label></th>
			 </tr>
	        <tr>
	            <th><label for="wc_live_chat_welcome_message">WordPress Authenticated User</label></th>
	            <td>
		            <textarea name="wc_live_chat_welcome_message_wordpress_user" id="wc_live_chat_welcome_message_wordpress_user" rows="3" class="regular-text"><?php echo esc_attr( get_the_author_meta( 'wc_live_chat_welcome_message_wordpress_user', $user->ID ) ); ?></textarea>
	                <br />
	                <span class="description">
		                The message is sent to every WordPress user who connects to this user via Wise Chat Live widget / shortcode.<br />
		                Dynamic variables of the visitor: {role}, {roles}, {id}, {username}, {displayname}, {email}, {firstname}, {lastname}, {nickname}, {description}, {website}, {website-linked}, {avatar}, {avatar-src}, {name}, {name-linked}, {status}<br />
						<a href="https://kainex.pl/projects/wp-plugins/wise-chat-pro/documentation/templates/" target="_blank">Read about templates</a>
	                </span>
	            </td>
	        </tr>
			<tr>
	            <th><label for="wc_live_chat_welcome_message">User Authenticated by Name</label></th>
	            <td>
		            <textarea name="wc_live_chat_welcome_message" id="wc_live_chat_welcome_message" rows="3" class="regular-text"><?php echo esc_attr( get_the_author_meta( 'wc_live_chat_welcome_message', $user->ID ) ); ?></textarea>
	                <br />
	                <span class="description">
		                The message is sent to every visitor who connects to this user via Wise Chat Live widget / shortcode.<br />
		                Dynamic variables of the visitor: {username}, {field1}, {field2}, {field3}, {field4}, {field5}, {field6}, {field7}<br />
						<a href="https://kainex.pl/projects/wp-plugins/wise-chat-pro/documentation/templates/" target="_blank">Read about templates</a>
	                </span>
	            </td>
	        </tr>
	    </table>
	<?php }

	public function saveLiveChatFields($user_id) {
	    if ( ! current_user_can( 'edit_user', $user_id ) )
	        return false;

	    update_user_meta( $user_id, 'wc_live_chat_welcome_message', $_POST['wc_live_chat_welcome_message'] );
	    update_user_meta( $user_id, 'wc_live_chat_welcome_message_wordpress_user', $_POST['wc_live_chat_welcome_message_wordpress_user'] );
	}

}