<?php

	use Kainex\WiseChatPro\Container;
	use Kainex\WiseChatPro\Endpoints\WiseChatMessagesEndpoint;
	use Kainex\WiseChatPro\Endpoints\WiseChatUserCommandEndpoint;
	use Kainex\WiseChatPro\Loader;
	use Kainex\WiseChatPro\Services\WiseChatImagesService;
	use Kainex\WiseChatPro\WiseChatOptions;

	define('DOING_AJAX', true);
	define('SHORTINIT', true);
	
	if (!isset($_REQUEST['action'])) {
	    http_response_code(400);
        die(json_encode(['error' => 'No action specified']));
	}
	header('Content-Type: text/html');
	header('Cache-Control: no-cache');
	header('Pragma: no-cache');

	ini_set('html_errors', 0);

	// class loader:
	require_once(dirname(__DIR__).'/Loader.php');
	Loader::install();

	require_once(dirname(__FILE__).'/wp_core.php');
	send_nosniff_header();

	// DI container:
	$container = Container::getInstance();

	/** @var WiseChatOptions $options */
	$options = $container->get(WiseChatOptions::class);

	if ($options->isOptionEnabled('enabled_debug')) {
		error_reporting(E_ALL);
		ini_set("display_errors", 1);
	}

	// removing images downloaded by the chat:
	/** @var WiseChatImagesService $wiseChatImagesService */
	$wiseChatImagesService = $container->get(WiseChatImagesService::class);
	add_action('delete_attachment', array($wiseChatImagesService, 'removeRelatedImages'));
	
	$action = $_REQUEST['action'];
	if ($action === 'wise_chat_messages_endpoint') {
		/** @var WiseChatMessagesEndpoint $endpoint */
		$endpoint = $container->get(WiseChatMessagesEndpoint::class);
		$endpoint->messagesEndpoint();
	} else if ($action === 'wise_chat_prepare_image_endpoint') {
		/** @var WiseChatUserCommandEndpoint $endpoint */
		$endpoint = $container->get(WiseChatUserCommandEndpoint::class);
		$endpoint->prepareImageEndpoint();
	} else if ($action === 'check') {
		die('OK');
	} else {
		http_response_code(400);
        die(json_encode(['error' => 'Invalid action']));
	}