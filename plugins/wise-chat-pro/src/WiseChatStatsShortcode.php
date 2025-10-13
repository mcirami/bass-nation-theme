<?php

namespace Kainex\WiseChatPro;

use Kainex\WiseChatPro\DAO\WiseChatChannelsDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Rendering\WiseChatRenderer;
use Kainex\WiseChatPro\Services\WiseChatMessagesService;
use Kainex\WiseChatPro\Services\WiseChatService;

/**
 * Shortcode that renders Wise Chat basic statistics for given channel.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatStatsShortcode {
    /**
     * @var WiseChatOptions
     */
    private $options;

    /**
     * @var WiseChatService
     */
    private $service;

    /**
     * @var WiseChatMessagesService
     */
    private $messagesService;

    /**
     * @var WiseChatChannelsDAO
     */
    private $channelsDAO;

    /**
     * @var WiseChatRenderer
     */
    private $renderer;

	/**
	 * @param WiseChatOptions $options
	 * @param WiseChatService $service
	 * @param WiseChatMessagesService $messagesService
	 * @param WiseChatChannelsDAO $channelsDAO
	 * @param WiseChatRenderer $renderer
	 */
	public function __construct(WiseChatOptions $options, WiseChatService $service, WiseChatMessagesService $messagesService, WiseChatChannelsDAO $channelsDAO, WiseChatRenderer $renderer) {
		$this->options = $options;
		$this->service = $service;
		$this->messagesService = $messagesService;
		$this->channelsDAO = $channelsDAO;
		$this->renderer = $renderer;
	}

	/**
     * Renders shortcode: [wise-chat-channel-stats]
     *
     * @param array $attributes
     * @return string
     */
    public function getRenderedChannelStatsShortcode($attributes) {
        if (!is_array($attributes)) {
            $attributes = array();
        }

        $attributes['channel'] = $this->service->getValidChatChannelName(
            array_key_exists('channel', $attributes) ? $attributes['channel'] : ''
        );

        $channel = $this->channelsDAO->getByName($attributes['channel']);
        if ($channel !== null) {
            $this->options->replaceOptions($attributes);

            $this->messagesService->startUpMaintenance();

            /**
             * Filters HTML outputted by channel stats shortcode:
             * [wise-chat-channel-stats template="Channel: {channel} Messages: {messages} Users: {users}"]
             *
             * @param string $html A HTML code outputted by channel stats shortcode
             * @param Channel $channel The channel
             *@since 2.3.2
             *
             */
            return apply_filters('wc_chat_channel_stats_html', $this->renderer->getRenderedChannelStats($channel), $channel);
        } else {
            return 'ERROR: channel does not exist';
        }
    }
}