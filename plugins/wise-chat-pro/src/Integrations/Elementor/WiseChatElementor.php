<?php

namespace Kainex\WiseChatPro\Integrations\Elementor;

use Kainex\WiseChatPro\Container;
use Kainex\WiseChatPro\Integrations\Elementor\Addons\WiseChatAddon;

/**
 * WiseChat Elementor integration class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatElementor {

	public function register($widgetsManager) {
		$widgetsManager->register(Container::getInstance()->get(WiseChatAddon::class));
	}

}