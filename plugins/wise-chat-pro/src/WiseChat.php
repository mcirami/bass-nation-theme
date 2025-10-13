<?php

namespace Kainex\WiseChatPro;

use Kainex\WiseChatPro\DAO\WiseChatEmoticonsDAO;
use Kainex\WiseChatPro\Rendering\WiseChatCssRenderer;
use Kainex\WiseChatPro\Rendering\WiseChatRenderer;
use Kainex\WiseChatPro\Rendering\WiseChatTemplater;
use Kainex\WiseChatPro\Services\Message\WiseChatMessageReactionsService;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\Services\User\WiseChatUserService;
use Kainex\WiseChatPro\Services\WiseChatAttachmentsService;
use Kainex\WiseChatPro\Services\WiseChatHttpRequestService;
use Kainex\WiseChatPro\Services\WiseChatService;

/**
 * WiseChat core class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChat {
	
	/**
	* @var WiseChatOptions
	*/
	private $options;

	/**
	 * @var WiseChatEmoticonsDAO
	 */
	private $emoticonsDAO;
	
	/**
	* @var WiseChatCssRenderer
	*/
	private $cssRenderer;
	
	/**
	* @var WiseChatService
	*/
	private $service;
	
	/**
	* @var WiseChatAttachmentsService
	*/
	private $attachmentsService;

	/**
	 * @var WiseChatMessageReactionsService
	 */
	protected $messageReactionsService;

	 /**
     * @var WiseChatHttpRequestService
     */
    private $httpRequestService;

	/** @var WiseChatTemplater */
	private $templater;
	
	/**
	* @var array
	*/
	private $shortCodeOptions = array();

	/**
	 * @param WiseChatOptions $options
	 * @param WiseChatEmoticonsDAO $emoticonsDAO
	 * @param WiseChatCssRenderer $cssRenderer
	 * @param WiseChatService $service
	 * @param WiseChatAttachmentsService $attachmentsService
	 * @param WiseChatMessageReactionsService $messageReactionsService
	 * @param WiseChatHttpRequestService $httpRequestService
	 * @param WiseChatTemplater $templater
	 */
	public function __construct(WiseChatOptions $options, WiseChatEmoticonsDAO $emoticonsDAO, WiseChatCssRenderer $cssRenderer, WiseChatService $service, WiseChatAttachmentsService $attachmentsService, WiseChatMessageReactionsService $messageReactionsService, WiseChatHttpRequestService $httpRequestService, WiseChatTemplater $templater) {
		$this->options = $options;
		$this->emoticonsDAO = $emoticonsDAO;
		$this->cssRenderer = $cssRenderer;
		$this->service = $service;
		$this->attachmentsService = $attachmentsService;
		$this->messageReactionsService = $messageReactionsService;
		$this->httpRequestService = $httpRequestService;
		$this->templater = $templater;
	}

	/**
	 * Loads Composer class loader.
	 */
	public static function requireComposerClassLoader() {
		require_once(dirname(__DIR__).'/vendor/autoload.php');
	}
	
	/*
	* Registers and enqueues all necessary resources (scripts or styles).
	*/
	public function enqueueResources() {
		if ($this->options->isOptionEnabled('enable_voice_messages', false)) {
			wp_enqueue_script('wise-chat-pro-web', plugins_url('assets/js/libs/WebAudioRecorder.min.js', dirname(__FILE__)), array('jquery'), WISE_CHAT_PRO_VERSION, true);
		}
		wp_enqueue_style('wise-chat-pro-libs', plugins_url('assets/css/wise-chat-pro-libs.min.css', dirname(__FILE__)), array(), WISE_CHAT_PRO_VERSION);
		if (getenv('WC_ENV') === 'DEV') {
			wp_enqueue_script('wise-chat-pro', plugins_url('assets/js/wise-chat-pro.js', dirname(__FILE__)), array('jquery'), WISE_CHAT_PRO_VERSION.'.'.time(), true);
			wp_enqueue_style('wise-chat-pro-core', plugins_url('assets/css/wise-chat-pro.min.css', dirname(__FILE__)), array(), WISE_CHAT_PRO_VERSION);
		} else {
			wp_enqueue_script('wise-chat-pro', plugins_url('assets/js/wise-chat-pro.min.js', dirname(__FILE__)), array('jquery'), WISE_CHAT_PRO_VERSION.'.'.time(), true);
			wp_enqueue_style('wise-chat-pro-core', plugins_url('assets/css/wise-chat-pro.min.css', dirname(__FILE__)), array(), WISE_CHAT_PRO_VERSION);
		}
	}

	/**
	 * Shortcode backend function: [wise-chat]
	 *
	 * @param array $attributes
	 * @return string
	 * @throws Exception
	 */
	public function getRenderedShortcode($attributes) {
		if (!is_array($attributes)) {
			$attributes = array();
		}
		$attributes['channel'] = $this->service->getValidChatChannelName(
			array_key_exists('channel', $attributes) ? $attributes['channel'] : 'global'
		);
		
		$this->options->replaceOptions($attributes);
		$this->shortCodeOptions = $attributes;

		$channels = array_filter((array) $this->options->getOption('channel', array()));

		return $this->getRenderedChat($channels);
	}

	/**
	 * Returns rendered chat window.
	 *
	 * @param array $channelNames
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getRenderedChat($channelNames) {
		$this->requireComposerClassLoaderIfNecessary();

		$channels = $this->service->createAndGetChannels($channelNames);
		$chatId = $this->service->getChatID();

		$customEmoticons = null;
		if ($this->options->isOptionEnabled('custom_emoticons_enabled', false)) {
			$customEmoticons = array();
			foreach ($this->emoticonsDAO->getAll() as $emoticon) {
				$attachment = wp_get_attachment_image_src($emoticon->getAttachmentId(), $this->options->getOption('custom_emoticons_emoticon_width', 'thumbnail'));
				if ($attachment !== false) {
					$customEmoticons[] = array(
						'id' => $emoticon->getId(),
						'url' => $attachment[0],
						'urlFull' => wp_get_attachment_url($emoticon->getAttachmentId()),
					);
				}
			}
		}

		$jsOptions = array(
			'chatId' => $chatId,
			'checksum' => $this->getCheckSum(),
			'isMultisite' => is_multisite(),
			'blogId' => get_current_blog_id(),
			'theme' => $this->options->getEncodedOption('theme', 'balloon'),
			'themeClassName' => 'wc'.ucfirst($this->options->getEncodedOption('theme', 'balloon')).'Theme',
			'baseDir' => $this->options->getBaseDir(),
			'mode' => $this->options->getIntegerOption('mode', 0),
			'channelIds' => array_map(function($channel) { return $channel->getId(); }, $channels),
			'nowTime' => gmdate('c', time()),
			'messagesOrder' => $this->options->getEncodedOption('messages_order', '') == 'descending' ? 'descending' : 'ascending',
			'debug' => $this->options->isOptionEnabled('enabled_debug', false),
			'disableChannelsRestore' => $this->options->isOptionEnabled('disable_channels_restore'),
			'interface' => array(
				'auth' => array(
					'mode' => $this->options->getOption('auth_mode', 'auto'),
					'username' => array(
						'fields' => array_filter(json_decode($this->options->getOption('auth_username_fields', '[]')), function($field) { return $field->name ? true : false; }),
						'intro' => $this->options->getOption('auth_username_intro_template', '')
					),
					'error' => $this->httpRequestService->getRequestParam('authenticationError')
				),
				'chat' => array(
					'title' => __('Chat', 'wise-chat-pro'),
					'publicEnabled' => $this->options->getIntegerOption('mode', 0) === 0 && !($this->options->isOptionEnabled('classic_disable_channel', false))
						|| $this->options->getIntegerOption('mode', 0) === 1 && !($this->options->isOptionEnabled('fb_disable_channel', false)),
					'classic' => array(
						'channelsView' => $this->options->getOption('classic_channels_interface', 'tabs'),
						'grid' => array(
							'cols' => $this->options->getIntegerOption('classic_grid_cols', 2),
							'height' => $this->options->getIntegerOption('classic_grid_height', 550)
						)
					),
					'fb' => array(
						'topOffset' => $this->options->getIntegerOption('fb_users_list_top_offset', 0),
						'bottomOffset' => $this->options->getIntegerOption('fb_bottom_offset', 0),
						'bottomThreshold' => $this->options->getIntegerOption('fb_bottom_offset_threshold', 0),
						'browserMinimizable' => $this->options->isOptionEnabled('fb_browser_minimize_enabled', true),
						'minimizeOnStart' => $this->options->isOptionEnabled('fb_minimize_on_start', false),
						'location' => $this->options->getEncodedOption('fb_location', 'right')
					),
					'mobile' => array(
						'tabs' => array(
							'chats' => $this->options->isOptionEnabled('mobile_mode_tab_chats_enabled', true),
						)
					)
				),
				'channel' => array(
					'inputLocation' => $this->options->getEncodedOption('input_controls_location') === 'top' ? 'top' : 'bottom',
					'directEnabled' => $this->options->isOptionEnabled('enable_private_messages'),
					'direct' => array(
						'closeConfirmation' => $this->options->isOptionEnabled('direct_channel_close_confirmation', false),
						'title' => $this->options->getEncodedOption('direct_channel_title', '')
					),
					'logOffOnCloseLast' => $this->options->isOptionEnabled('chat_log_off_on_close_last'),
					'destroyOnCloseLast' => $this->options->isOptionEnabled('chat_destroy_on_close_last')
				),
				'message' => array(
					'compact' => in_array($this->options->getEncodedOption('theme', 'balloon'), array('lightgray', 'colddark', 'airflow', 'balloon')),
					'timeMode' => $this->options->getEncodedOption('messages_time_mode'),
					'dateFormat' => trim($this->options->getEncodedOption('messages_date_format')),
					'timeFormat' => trim($this->options->getEncodedOption('messages_time_format')),
					'senderMode' => $this->options->getIntegerOption('link_wp_user_name', 3),
					'approvingMode' => $this->options->getIntegerOption('approving_messages_mode', 1),
					'approvalConfirmation' => $this->options->isOptionEnabled('enable_approval_confirmation', true),
					'links' => $this->options->isOptionEnabled('allow_post_links'),
					'attachments' => $this->options->isOptionEnabled('enable_attachments_uploader'),
					'attachmentsVideoPlayer' => $this->options->isOptionEnabled('attachments_video_player', true),
					'attachmentsSoundPlayer' => $this->options->isOptionEnabled('attachments_sound_player', true),
					'images' => $this->options->isOptionEnabled('allow_post_images'),
					'imagesViewer' => $this->options->getEncodedOption('images_viewer', 'internal'),
					'yt' => $this->options->isOptionEnabled('enable_youtube'),
					'ytWidth' => $this->options->getIntegerOption('youtube_width', 186),
					'ytHeight' => $this->options->getIntegerOption('youtube_height', 105),
					'tt' => $this->options->isOptionEnabled('enable_twitter_hashtags'),
					'edit' => $this->options->isOptionEnabled('enable_edit_own_messages', false),
					'reactions' => $this->messageReactionsService->getInitialConfiguration()
				),
				'input' => array(
					'userName' => $this->options->isOptionEnabled('show_user_name'),
					'submit' => $this->options->isOptionEnabled('show_message_submit_button'),
					'multiline' => $this->options->isOptionEnabled('multiline_support'),
					'multilineEasy' => $this->options->isOptionEnabled('multiline_easy_mode', false),
					'maxLength' => $this->options->getIntegerOption('message_max_length', 100),
					'emoticons' => array(
						'enabled' => $this->options->isOptionEnabled('show_emoticon_insert_button', true),
						'set' => $this->options->getIntegerOption('emoticons_enabled', 1),
						'size' => $this->options->getIntegerOption('emoticons_size', 32),
						'custom' => $customEmoticons,
						'customPopupWidth' => $this->options->getIntegerOption('custom_emoticons_popup_width', 0),
						'customPopupHeight' => $this->options->getIntegerOption('custom_emoticons_popup_height', 0),
						'customEmoticonMaxWidthInPopup' => $this->options->getIntegerOption('custom_emoticons_emoticon_max_width_in_popup', 0),
						'baseURL' => $this->options->getEmoticonsBaseURL(),
					),
					'gifs' => array(
						'enabled' => $this->options->isOptionEnabled('gifs_enabled', true),
						'limit' => $this->options->getIntegerOption('gifs_limit', 10),
						'apiKey' => $this->options->getEncodedOption('gifs_api_key'),
						'country' => $this->options->getEncodedOption('gifs_country', "US"),
						'language' => $this->options->getEncodedOption('gifs_language', "en_US"),
					),
					'images' => array(
						'enabled' => $this->options->isOptionEnabled('enable_images_uploader'),
						'sizeLimit' => $this->options->getIntegerOption('images_size_limit', 3145728),
					),
					'attachments' => array(
						'enabled' => $this->options->isOptionEnabled('enable_attachments_uploader'),
						'extensionsList' => $this->attachmentsService->getAllowedExtensionsList(),
						'validFileFormats' => $this->attachmentsService->getAllowedFormats(),
						'sizeLimit' => $this->attachmentsService->getSizeLimit()
					),
					'sounds' => array(
						'enabled' => $this->options->isOptionEnabled('enable_voice_messages', false),
						'maxLength' => in_array($this->options->getIntegerOption('voice_message_max_length', 60), range(1, 300))
							? $this->options->getIntegerOption('voice_message_max_length', 60)
							: 60,
						'mp3BitRate' => in_array($this->options->getIntegerOption('voice_message_mp3_bitrate', 160), range(64, 320))
							? $this->options->getIntegerOption('voice_message_mp3_bitrate', 160)
							: 160,
					)
				),
				'customization' => array(
					'userNameLengthLimit' => $this->options->getIntegerOption('user_name_length_limit', 25),
				),
				'browser' => array(
					'enabled' => $this->options->isOptionEnabled('show_users', true),
					'searchSubChannels' => $this->options->isOptionEnabled('show_users_list_search_box', true),
					'location' => $this->options->getEncodedOption('browser_location') === 'left' ? 'left' : 'right',
					'status' => $this->options->isOptionEnabled('show_users_online_offline_mark', true),
					'mode' => $this->options->getEncodedOption('browser_mode', 'full-channels')
				),
				'recent' => array(
					'enabled' => $this->options->isOptionEnabled('users_list_offline_enable', true) && $this->options->isOptionEnabled('enable_private_messages', false),
					'excerpts' =>  $this->options->isOptionEnabled('recent_excerpts_enabled', true),
					'status' =>  $this->options->isOptionEnabled('recent_status_enabled', false)
				),
				'incoming' => array(
					'enabled' => !$this->options->isOptionEnabled('disable_incoming_chats'),
					'confirm' => $this->options->isOptionEnabled('private_message_confirmation', true),
					'focus' => $this->options->isOptionEnabled('private_message_autofocus', true),
				),
				'counter' => array(
					'onlineUsers' => $this->options->isOptionEnabled('show_users_counter', false)
				),
				'streams' => array(
					'video' => array(
						'calls' => array(
							'enabled' => $this->options->isOptionEnabled('video_calls_enabled', false),
							'callingTimeout' => $this->options->getIntegerOption('video_calls_calling_timeout', 25),
							'callingSound' => $this->options->isOptionEnabled('video_calls_calling_sound_enabled', true),
							'incomingSound' => $this->options->isOptionEnabled('video_calls_incoming_call_sound_enabled', true)
						)
					)
				)
			),
			'engines' => array(
				'ajax' => array(
					'apiEndpointBase' => $this->getEndpointBase(),
					'apiMessagesEndpointBase' => $this->getMessagesEndpointBase(),
					'apiWPEndpointBase' => $this->getWPEndpointBase(),
					'refresh' => intval($this->options->getEncodedOption('messages_refresh_time', 3000)),
				)
			),
			'rights' => array(
				'receiveMessages' => !$this->options->isOptionEnabled('write_only', false), // TODO: review
			),

			'notifications' => array(
				'newChat' => array(
					'sound' => $this->options->getEncodedOption('chat_sound_notification'),
				),
				'newMessage' => array(
					'title' => $this->options->isOptionEnabled('enable_title_notifications'),
					'sound' => $this->options->getEncodedOption('sound_notification'),
					'mode' => $this->options->getEncodedOption('sound_notification_mode'),
				),
				'userLeft' => array(
					'sound' => $this->options->getEncodedOption('leave_sound_notification'),
					'browserHighlight' => $this->options->isOptionEnabled('enable_leave_notification', true),
				),
				'userJoined' => array(
					'sound' => $this->options->getEncodedOption('join_sound_notification'),
					'browserHighlight' => $this->options->isOptionEnabled('enable_join_notification', true),
				),
				'mentioned' => array(
					'sound' => $this->options->getEncodedOption('mentioning_sound_notification'),
				)
			),

			'i18n' => array(
				'loadingChat' => __('Loading the chat ...', 'wise-chat-pro'),
				'loading' => __('Loading ...', 'wise-chat-pro'),
				'sending' => __('Sending ...', 'wise-chat-pro'),
				'send' => __('Send', 'wise-chat-pro'),
				'hint' => __('Enter message here', 'wise-chat-pro'),
				'customize' => __('Customize', 'wise-chat-pro'),
				'secAgo' => __('sec. ago', 'wise-chat-pro'),
				'minAgo' => __('min. ago', 'wise-chat-pro'),
				'yesterday' => __('yesterday', 'wise-chat-pro'),
				'insertIntoMessage' => __('Insert into message', 'wise-chat-pro'),
				'users' => __('Users', 'wise-chat-pro'),
				'channels' => __('Channels', 'wise-chat-pro'),
				'channel' => __('Channel', 'wise-chat-pro'),
				'recent' => __('Recent', 'wise-chat-pro'),
				'chats' => __('Chats', 'wise-chat-pro'),
				'usersAndChannels' => __('Users and Channels', 'wise-chat-pro'),
				'noChannels' => $this->options->getEncodedOption('message_no_channels', __('No channels open.', 'wise-chat-pro')),
				'noChats' => $this->options->getEncodedOption('message_no_chats', __('No chats open.', 'wise-chat-pro')),
				'enterUserName' => __('Enter your username', 'wise-chat-pro'),
				'logIn' => __('Log in', 'wise-chat-pro'),
				'logInUsing' => __('Log in using', 'wise-chat-pro'),
				'logInAnonymously' => __('Log in anonymously', 'wise-chat-pro'),
				'onlineUsers' => __('Online users', 'wise-chat-pro'),
			)
		);

		/**
		 * Filters the configuration of the chat. The configuration is then used in the front-end rendering code.
		 *
		 * @since 3.5.5
		 *
		 * @param array $jsOptions Chat's configuration array
		 */
		$jsOptions = apply_filters('wc_chat_js_configuration', $jsOptions);

		$this->templater->setTemplateFile('/templates/main-react.tpl');

		$data = array(
			'chatId' => $chatId,
			'title' => __('Chat', 'wise-chat-pro'),
			'themeClassName' => 'wc'.ucfirst($this->options->getEncodedOption('theme', 'balloon')).'Theme'.(!$this->options->isOptionEnabled('auto_start', true) ? ' wcInvisible' : ''),
			'loading' => __('Loading the chat ...', 'wise-chat-pro'),
			'classicMode' => $this->options->getIntegerOption('mode', 0) === 0,
			'sidebarMode' => $this->options->getIntegerOption('mode', 0) === 1,
			'sidebarModeLeft' => $this->options->getIntegerOption('mode', 0) === 1 && $this->options->getEncodedOption('fb_location', 'right') === 'left',
			'baseDir' => $this->options->getBaseDir(),
			'jsOptionsEncoded' => htmlspecialchars(json_encode($jsOptions), ENT_QUOTES, 'UTF-8'),
			'cssDefinitions' => $this->cssRenderer->getCssDefinition($chatId),
			'customCssDefinitions' => $this->cssRenderer->getCustomCssDefinition()
		);

		return $this->templater->render($data);
	}

    /**
     * @return string
     */
    private function getCheckSum() {
		$checkSumData = is_array($this->shortCodeOptions) ? $this->shortCodeOptions : array();
		if ($this->options->isOptionEnabled('enable_buddypress', false) && function_exists("bp_is_group") && bp_is_group()) {
			$checkSumData['_bpg'] = bp_get_group_id();
		}

        return base64_encode(WiseChatCrypt::encryptToString(serialize($checkSumData)));
    }

    /**
     * @return string
     */
	private function getEndpointBase() {
		$endpointBase = get_site_url().'/wp-admin/admin-ajax.php';
		if ($this->options->getEncodedOption('ajax_engine', null) === 'gold') {
			$endpointBase = get_site_url().'/?wc-gold-engine';
		} else if (in_array($this->options->getEncodedOption('ajax_engine', null), array('lightweight', 'ultralightweight'))) {
			$endpointBase = plugin_dir_url(__FILE__).'Endpoints/';
		}
		
		return $endpointBase;
	}

	/**
	 * @return string
	 */
	private function getMessagesEndpointBase() {
		if ($this->options->getEncodedOption('ajax_engine', null) === 'gold') {
			$endpointBase = get_site_url().'/?wc-gold-engine';
		} else if ($this->options->getEncodedOption('ajax_engine', null) === 'ultralightweight') {
			$endpointBase = plugin_dir_url(__FILE__).'Endpoints/Ultra/index.php';
		} else {
			$endpointBase = $this->getEndpointBase();
		}
		return $endpointBase;
	}

 	/**
     * @return string
	 * @see <2d0828650954222216cfce75c0ba4cc9e826e6477f775353>
     */
	private function getWPEndpointBase() {
		return get_site_url().'/wp-admin/admin-ajax.php';
	}

	/**
	 * Loads Composer class loader only if necessary.
	 */
	private function requireComposerClassLoaderIfNecessary() {
		if ($this->service->isExternalLoginEnabled()) {
			self::requireComposerClassLoader();
		}
	}

}