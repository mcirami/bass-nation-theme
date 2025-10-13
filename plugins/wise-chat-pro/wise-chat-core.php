<?php
/*
	Plugin Name: Wise Chat Pro
	Version: 3.6.3
	Plugin URI: https://kainex.pl/projects/wp-plugins/wise-chat-pro
	Description: Fully-featured chat plugin for WordPress. Supports multiple channels, private messages, multisite installation, bad words filtering, themes, appearance settings, avatars, filters, bans and more.
	Author: Kainex
	Author URI: https://kainex.pl
	Text Domain: wise-chat-pro
*/

use Kainex\WiseChatPro\Admin\Pages\Chat;
use Kainex\WiseChatPro\Container;
use Kainex\WiseChatPro\Endpoints\WiseChatAuthEndpoint;
use Kainex\WiseChatPro\Endpoints\WiseChatMaintenanceEndpoint;
use Kainex\WiseChatPro\Endpoints\WiseChatMessageEndpoint;
use Kainex\WiseChatPro\Endpoints\WiseChatMessagesEndpoint;
use Kainex\WiseChatPro\Endpoints\WiseChatUserCommandEndpoint;
use Kainex\WiseChatPro\Integrations\Buddypress\WiseChatBuddyPressGroupExtension;
use Kainex\WiseChatPro\Integrations\Buddypress\WiseChatBuddyPressMemberProfileExtensions;
use Kainex\WiseChatPro\Integrations\Elementor\WiseChatElementor;
use Kainex\WiseChatPro\Integrations\WordPress\WiseChatAdminBarExtensions;
use Kainex\WiseChatPro\Integrations\WordPress\WiseChatBlocks;
use Kainex\WiseChatPro\Integrations\WordPress\WiseChatUsersExtensions;
use Kainex\WiseChatPro\Loader;
use Kainex\WiseChatPro\Services\User\WiseChatExternalAuthentication;
use Kainex\WiseChatPro\Services\User\WiseChatUserService;
use Kainex\WiseChatPro\Services\WiseChatImagesService;
use Kainex\WiseChatPro\Shortcodes\WiseChatButtonShortcode;
use Kainex\WiseChatPro\Shortcodes\WiseChatLiveChatShortcode;
use Kainex\WiseChatPro\Widgets\WiseChatLiveChatWidget;
use Kainex\WiseChatPro\WiseChat;
use Kainex\WiseChatPro\WiseChatAdminPages;
use Kainex\WiseChatPro\WiseChatInstaller;
use Kainex\WiseChatPro\WiseChatOptions;
use Kainex\WiseChatPro\WiseChatServiceConnector;
use Kainex\WiseChatPro\WiseChatSettings;
use Kainex\WiseChatPro\WiseChatStatsShortcode;
use Kainex\WiseChatPro\WiseChatWidget;

if (!defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

define('WISE_CHAT_PRO_VERSION', '3.6.3');

define('WISE_CHAT_PRO_ROOT', plugin_dir_path(__FILE__));
define('WISE_CHAT_PRO_NAME', 'Wise Chat Pro');
define('WISE_CHAT_PRO_SLUG', strtolower(str_replace(' ', '_', WISE_CHAT_PRO_NAME)));

// class loader:
require_once(dirname(__FILE__).'/src/Loader.php');
Loader::install();

// DI container:
$container = Container::getInstance();

/** @var WiseChatOptions $options */
$options = $container->get(WiseChatOptions::class);

add_action('wp_enqueue_scripts', [$container->get(WiseChat::class), 'enqueueResources']);

function wise_chat_load_plugin_textdomain() {
	load_plugin_textdomain('wise-chat-pro', false, basename(dirname(__FILE__)).'/languages/');
}
add_action('plugins_loaded', 'wise_chat_load_plugin_textdomain');

if ($options->isOptionEnabled('enabled_debug')) {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
}

// store path for usage in engines:
WiseChatOptions::storeEngineConfig();

if (is_admin()) {
	WiseChatInstaller::setup(__FILE__);

    /** @var WiseChatSettings $settings */
	$settings = $container->get(WiseChatSettings::class);
    // initialize plugin settings page:
	$settings->initialize();

	if (defined('WISE_CHAT_PRO_VERSION_LIVE')) {
		/** @var WiseChatAdminPages $adminPages */
		$adminPages = $container->get(WiseChatAdminPages::class);
		$adminPages->initialize();
	} else {
		/** @var Chat $adminPages */
		$adminPages = $container->get(Chat::class);
		$adminPages->initialize();
	}

	add_action('admin_enqueue_scripts', function() use ($container) {
		wp_enqueue_media();
		/** @var WiseChat $wiseChat */
		$wiseChat = $container->get(WiseChat::class);
		$wiseChat->enqueueResources();
		wp_localize_script('wise-chat-pro', '_wiseChatData', array('siteUrl' => get_site_url()));
	});

	$pluginPath = plugin_basename(WISE_CHAT_PRO_ROOT).'/wise-chat-core.php';
	add_filter('transient_update_plugins', array($container->get(WiseChatServiceConnector::class), 'checkForUpdate'));
	add_filter('site_transient_update_plugins', array($container->get(WiseChatServiceConnector::class), 'checkForUpdate'));
	add_action('install_plugins_pre_plugin-information', array($container->get(WiseChatServiceConnector::class), 'displayChangelog'), 9);
	add_action('in_plugin_update_message-'.$pluginPath, array($container->get(WiseChatServiceConnector::class), 'displayAddOn'), 10, 2);
}

// register action that detects when WordPress user logs in / logs out:
function wise_chat_after_setup_theme_action() {
    /** @var WiseChatUserService $userService */
	$userService = Container::getInstance()->get(WiseChatUserService::class);
	$userService->switchUser();
}
add_action('after_setup_theme', 'wise_chat_after_setup_theme_action');

// register chat shortcode:
function wise_chat_shortcode($atts) {
	/** @var WiseChat $wiseChat */
	$wiseChat = Container::getInstance()->get(WiseChat::class);
	return $wiseChat->getRenderedShortcode($atts);
}
add_shortcode('wise-chat', 'wise_chat_shortcode');

// register chat channel stats shortcode:
function wise_chat_channel_stats_shortcode($atts) {
	/** @var WiseChatStatsShortcode $wiseChatStatsShortcode */
	$wiseChatStatsShortcode = Container::getInstance()->get(WiseChatStatsShortcode::class);
	return $wiseChatStatsShortcode->getRenderedChannelStatsShortcode($atts);
}
add_shortcode('wise-chat-channel-stats', 'wise_chat_channel_stats_shortcode');

add_action('init', array(Container::getInstance()->get(WiseChatUsersExtensions::class), 'install'));
if (defined('WISE_CHAT_PRO_VERSION_LIVE')) {
	add_action('admin_bar_menu', array(Container::getInstance()->get(WiseChatAdminBarExtensions::class), 'installLiveChat'), 999);

	// register live chat widget:
	function wise_chat_live_chat_widget() {
		/** @var WiseChatLiveChatWidget $widget */
		$widget = Container::getInstance()->get(WiseChatLiveChatWidget::class);
		register_widget($widget);
	}
	add_action('widgets_init', 'wise_chat_live_chat_widget');

	// register live chat:
	function wise_chat_live_chat_shortcode($atts) {
		/** @var WiseChatLiveChatShortcode $shortcode */
		$shortcode = Container::getInstance()->get(WiseChatLiveChatShortcode::class);
		return $shortcode->getRenderedShortcode($atts);
	}
	add_shortcode('wise-chat-live-chat', 'wise_chat_live_chat_shortcode');
} else {
	add_action('admin_bar_menu', array(Container::getInstance()->get(WiseChatAdminBarExtensions::class), 'installChat'), 999);
}

// register chat button:
function wise_chat_button_chat_shortcode($atts) {
	/** @var WiseChatButtonShortcode $shortcode */
	$shortcode = Container::getInstance()->get(WiseChatButtonShortcode::class);
	return $shortcode->getRenderedShortcode($atts);
}
add_shortcode('wise-chat-button', 'wise_chat_button_chat_shortcode');

// chat function:
function wise_chat($channel = null) {
	/** @var WiseChat $wiseChat */
	$wiseChat = Container::getInstance()->get(WiseChat::class);
	echo $wiseChat->getRenderedChat(!is_array($channel) ? array($channel) : $channel);
}

// register chat widget:
function wise_chat_widget() {
	/** @var WiseChatWidget $widget */
	$widget = Container::getInstance()->get(WiseChatWidget::class);
	register_widget($widget);
}
add_action('widgets_init', 'wise_chat_widget');

add_action('init', array(Container::getInstance()->get(WiseChatBlocks::class), 'register'));

// register action that auto-removes images generate by the chat (the additional thumbnail):
function wise_chat_action_delete_attachment($attachmentId) {
	/** @var WiseChatImagesService $wiseChatImagesService */
	$wiseChatImagesService = Container::getInstance()->get(WiseChatImagesService::class);
	$wiseChatImagesService->removeRelatedImages($attachmentId);
}
add_action('delete_attachment', 'wise_chat_action_delete_attachment');

function wise_chat_bp_init() {
	/** @var WiseChatOptions $options */
	$options = Container::getInstance()->get(WiseChatOptions::class);
	if ($options->isOptionEnabled('enable_buddypress', false) && (bp_is_active( 'groups' ) || class_exists('BP_Group_Extension', false))) {
		bp_register_group_extension(WiseChatBuddyPressGroupExtension::class);
	}
	if ($options->isOptionEnabled('enable_buddypress', false)) {
		Container::getInstance()->get(WiseChatBuddyPressMemberProfileExtensions::class);
	}
}
add_action('bp_include', 'wise_chat_bp_init');

// register action that handles authentication features:
function wise_chat_authentication_action() {
	/** @var WiseChatExternalAuthentication $externalAuthentication */
	$externalAuthentication = Container::getInstance()->get(WiseChatExternalAuthentication::class);
	$externalAuthentication->handleRedirects();
	$externalAuthentication->handleAuthentication();
}
add_action('template_redirect', 'wise_chat_authentication_action');

// Endpoints fo AJAX requests:
function wise_chat_endpoint_messages() {
	/** @var WiseChatMessagesEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = Container::getInstance()->get(WiseChatMessagesEndpoint::class);
	$wiseChatEndpoints->messagesEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_messages_endpoint", 'wise_chat_endpoint_messages');
add_action("wp_ajax_wise_chat_messages_endpoint", 'wise_chat_endpoint_messages');

function wise_chat_endpoint_past_messages() {
	/** @var WiseChatMessagesEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = Container::getInstance()->get(WiseChatMessagesEndpoint::class);
	$wiseChatEndpoints->pastMessagesEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_past_messages_endpoint", 'wise_chat_endpoint_past_messages');
add_action("wp_ajax_wise_chat_past_messages_endpoint", 'wise_chat_endpoint_past_messages');

function wise_chat_endpoint_message() {
	/** @var WiseChatMessageEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = Container::getInstance()->get(WiseChatMessageEndpoint::class);
	$wiseChatEndpoints->messageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_message_endpoint", 'wise_chat_endpoint_message');
add_action("wp_ajax_wise_chat_message_endpoint", 'wise_chat_endpoint_message');

function wise_chat_endpoint_get_message() {
	/** @var WiseChatMessageEndpoint $wiseChatEndpoints */
	$wiseChatEndpoints = Container::getInstance()->get(WiseChatMessageEndpoint::class);
	$wiseChatEndpoints->getMessageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_get_message_endpoint", 'wise_chat_endpoint_get_message');
add_action("wp_ajax_wise_chat_get_message_endpoint", 'wise_chat_endpoint_get_message');

function wise_chat_endpoint_maintenance() {
	/** @var WiseChatMaintenanceEndpoint $endpoint */
	$endpoint = Container::getInstance()->get(WiseChatMaintenanceEndpoint::class);
	$endpoint->maintenanceEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_maintenance_endpoint", 'wise_chat_endpoint_maintenance');
add_action("wp_ajax_wise_chat_maintenance_endpoint", 'wise_chat_endpoint_maintenance');

function wise_chat_endpoint_prepare_image() {
	/** @var WiseChatUserCommandEndpoint $endpoint */
	$endpoint = Container::getInstance()->get(WiseChatUserCommandEndpoint::class);
	$endpoint->prepareImageEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_prepare_image_endpoint", 'wise_chat_endpoint_prepare_image');
add_action("wp_ajax_wise_chat_prepare_image_endpoint", 'wise_chat_endpoint_prepare_image');

function wise_chat_endpoint_user_command() {
	/** @var WiseChatUserCommandEndpoint $endpoint */
	$endpoint = Container::getInstance()->get(WiseChatUserCommandEndpoint::class);
	$endpoint->userCommandEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_user_command_endpoint", 'wise_chat_endpoint_user_command');
add_action("wp_ajax_wise_chat_user_command_endpoint", 'wise_chat_endpoint_user_command');

function wise_chat_endpoint_auth() {
	/** @var WiseChatAuthEndpoint $endpoint */
	$endpoint = Container::getInstance()->get(WiseChatAuthEndpoint::class);
	$endpoint->authEndpoint();
}
add_action("wp_ajax_nopriv_wise_chat_auth_endpoint", 'wise_chat_endpoint_auth');
add_action("wp_ajax_wise_chat_auth_endpoint", 'wise_chat_endpoint_auth');

function wise_chat_admin_user_search() {
	/** @var WiseChatSettings $wiseChatSettings */
	$wiseChatSettings = Container::getInstance()->get(WiseChatSettings::class);
	$wiseChatSettings->userSearchEndpoint();
}
add_action("wp_ajax_wise_chat_admin_user_search", 'wise_chat_admin_user_search');

function wise_chat_profile_update($userId, $oldUserData) {
	/** @var WiseChatUserService $wiseChatUserService */
	$wiseChatUserService = Container::getInstance()->get(WiseChatUserService::class);
	$wiseChatUserService->onWpUserProfileUpdate($userId, $oldUserData);
}
add_action("profile_update", 'wise_chat_profile_update', 10, 2);

function wise_chat_elementor($widgetsManager) {
	/** @var WiseChatElementor $wiseChatElementor */
	$wiseChatElementor = Container::getInstance()->get(WiseChatElementor::class);
	$wiseChatElementor->register($widgetsManager);
}
add_action('elementor/widgets/register', 'wise_chat_elementor');