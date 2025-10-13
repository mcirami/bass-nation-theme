<?php

namespace Kainex\WiseChatPro\Rendering;

use Kainex\WiseChatPro\DAO\WiseChatChannelUsersDAO;
use Kainex\WiseChatPro\Model\Channel\Channel;
use Kainex\WiseChatPro\Services\WiseChatMessagesService;
use Kainex\WiseChatPro\WiseChatOptions;

/**
 * Wise Chat rendering class.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatRenderer {
	
	/**
	* @var WiseChatMessagesService
	*/
	private $messagesService;
	
	/**
	* @var WiseChatChannelUsersDAO
	*/
	private $channelUsersDAO;
	
	/**
	* @var WiseChatOptions
	*/
	private $options;
	
	/**
	* @var WiseChatTemplater
	*/
	private $templater;

	/**
	 * @param WiseChatMessagesService $messagesService
	 * @param WiseChatChannelUsersDAO $channelUsersDAO
	 * @param WiseChatOptions $options
	 * @param WiseChatTemplater $templater
	 */
	public function __construct(WiseChatMessagesService $messagesService, WiseChatChannelUsersDAO $channelUsersDAO, WiseChatOptions $options, WiseChatTemplater $templater) {
		$this->messagesService = $messagesService;
		$this->channelUsersDAO = $channelUsersDAO;
		$this->options = $options;
		$this->templater = $templater;
	}

	/**
	* Returns rendered channel statistics.
	*
	* @param Channel $channel
	*
	* @return string HTML source
	*/
	public function getRenderedChannelStats($channel) {
		if ($channel === null) {
			return 'ERROR: channel does not exist';
		}

		$variables = array(
			'channel' => $channel->getName(),
			'messages' => $this->messagesService->getNumberByChannelId($channel->getId())
		);
	
		return $this->getTemplatedString($variables, $this->options->getOption('template', 'ERROR: TEMPLATE NOT SPECIFIED'));
	}
	
	public function getTemplatedString($variables, $template, $encodeValues = true) {
		foreach ($variables as $key => $value) {
			$template = str_replace("{".$key."}", $encodeValues ? urlencode($value) : $value, $template);
		}

		$template = preg_replace('/{[0-9a-zA-Z_-]+}/', '', $template);
		
		return $template;
	}

}