<?php 

namespace Kainex\WiseChatPro\Admin;

use Kainex\WiseChatPro\WiseChatSettings;

/**
 * Wise Chat live chat settings tab class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatLiveChatTab extends WiseChatAbstractTab {

	public function getFields() {
		return array(
			array('_section', 'Live Chat Settings',
				'Use [wise-chat-live-chat] shortcode or Wise Chat Live Chat widget to open Wise Chat Pro in live chat mode. Then please define the list of operators below. 
				An operator is assigned to each new visitor randomly. Online operators are prioritized. Once a visitor closes the chat a new operator is chosen on the next visit.
				'
			),
			array('live_chat_operator_page', 'Chat Operator Page', 'operatorPageCallback', 'void'),
			array(
				'live_chat_intro_template', 'Auth Intro', 'multilineFieldCallback', 'multilinestring',
				'Text displayed above the authentication form. Allowed tags: [span], [img], [link]<br />'.
				'<a href="https://kainex.pl/projects/wp-plugins/wise-chat-pro/documentation/templates/" target="_blank">Read about templates</a>'
			),
			array('live_chat_operators', 'Operators', 'operatorsCallback', 'void'),
			array('live_chat_operator_add', ' ', 'operatorAddCallback', 'void'),
			array('_section', 'Operator Page Settings'),
			array('live_chat_operators_height', 'Height', 'stringFieldCallback', 'string', 'Allowed values: a number with or without an unit (px or %), default: 500px'),
		);
	}
	
	public function getDefaultValues() {
		return array(
			'live_chat_operators' => array(),
			'live_chat_intro_template' => '',
			'live_chat_operators_height' => '700px'
		);
	}

	public function deleteOperatorAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'deleteOperator')) {
			return;
		}

		$index = $_GET['index'];
		if ($index) {
			$index = intval($index);
			$operators = (array) $this->options->getOption('live_chat_operators', array());
			if ($index < count($operators)) {
				unset($operators[$index]);
				$this->options->setOption('live_chat_operators', array_values($operators));
				$this->options->saveOptions();
				$this->addMessage('Operator has been removed from the access list');
			}
		}
	}

	public function addOperatorAction() {
		if (!current_user_can(WiseChatSettings::CAPABILITY) || !wp_verify_nonce($_GET['nonce'], 'addOperator')) {
			return;
		}

		$newOperator = trim($_GET['newOperator']);
		if (!$newOperator) {
			$this->addErrorMessage('Please specify user login');
		} else {
			$wpUser = $this->usersDAO->getWpUserByLogin($newOperator);
			if ($wpUser === null) {
				$this->addErrorMessage('The user login is not correct');
			} else {
				$operators = (array) $this->options->getOption('live_chat_operators', array());
				$operators[] = $wpUser->ID;
				$this->options->setOption('live_chat_operators', $operators);
				$this->options->saveOptions();

				$this->addMessage("Operator has been added to the list");
			}
		}
	}

	public function operatorPageCallback() {
		print('<input type="button" class="button button-primary button-large" value="Go To Live Chat Operator Page" onclick="window.location=\'admin.php?page=wise-chat-pro-chat-page\'">');
	}

	public function operatorsCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG);
		$users = (array) $this->options->getOption('live_chat_operators', array());

		$html = "<div style='height: 150px; overflow-y: auto; border: 1px solid #aaa; padding: 5px;'>";
		if (count($users) == 0) {
			$html .= '<small>No live chat operators were added yet</small>';
		} else {
			$html .= '<table class="wp-list-table widefat fixed striped users wcCondensedTable">';
			$html .= '<tr><th style="width:100px">ID</th><th>Login</th><th>Display Name</th><th></th></tr>';
			foreach ($users as $userKey => $userID) {
				$deleteURL = $url . '&wc_action=deleteOperator&wc_tab=liveChat&index=' . $userKey . '&nonce='.wp_create_nonce('deleteOperator');
				$deleteLink = "<a href='{$deleteURL}' onclick='return confirm(\"Are you sure?\")'>Delete</a><br />";
				$user = $this->usersDAO->getWpUserByID(intval($userID));
				if ($user !== null) {
					$html .= sprintf("<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>", $userID, $user->user_login, $user->display_name, $deleteLink);
				} else {
					$html .= sprintf("<tr><td>%d</td><td colspan='2'>Unknown user</td><td>%s</td></tr>", $userID, $deleteLink);
				}
			}
			$html .= '</table>';
		}
		$html .= "</div>";
		$html .= '<p class="description">A list of live chat operators.</p>';
		print($html);
	}

	public function operatorAddCallback() {
		$url = admin_url("options-general.php?page=".WiseChatSettings::MENU_SLUG."&wc_action=addOperator&wc_tab=liveChat&nonce=".wp_create_nonce('addOperator'));

		printf(
			'<input type="text" value="" placeholder="User login" id="newOperator" name="newOperator" class="wcUserLoginHint" />'.
			'<a class="button-secondary" href="%s" title="Adds user to access list" onclick="%s">Add User</a>',
			wp_nonce_url($url),
			'this.href += \'&newOperator=\' + encodeURIComponent(jQuery(\'#newOperator\').val());'
		);
	}

}