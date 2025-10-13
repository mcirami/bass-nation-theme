<?php

namespace Kainex\WiseChatPro\Services\User;

use Exception;
use Kainex\WiseChatPro\Model\Channel\Channel;

/**
 * Wise Chat user authorization service.
 */
class WiseChatAuthorization {
    const PROPERTY_NAME = 'channel_authorization';

    /**
     * @var WiseChatUserService
     */
    private $userService;

	/**
	 * @param WiseChatUserService $userService
	 */
	public function __construct(WiseChatUserService $userService) {
		$this->userService = $userService;
	}

	/**
	 * Grants access to the channel for the current user.
	 *
	 * @param Channel $channel
	 * @throws Exception
	 */
    public function markAuthorizedForChannel($channel) {
        $grants = $this->userService->getProperty(self::PROPERTY_NAME);
        if (!is_array($grants)) {
            $grants = array();
        }

        $grants[$channel->getId()] = $channel->getPassword();
        $this->userService->setProperty(self::PROPERTY_NAME, $grants);
    }
}