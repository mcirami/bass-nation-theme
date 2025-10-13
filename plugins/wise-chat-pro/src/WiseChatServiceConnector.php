<?php

namespace Kainex\WiseChatPro;

/**
 * WiseChatServiceConnector.
 *
 * @author Kainex <contact@kaine.pl>
 */
class WiseChatServiceConnector {

	const ROOT_URL = 'https://kainex.pl';

	public static function getSettings($property) {
		$content = file_get_contents(WISE_CHAT_PRO_ROOT.'/src/WiseChat.php');
		if (preg_match('/@see <([a-z0-9A-Z]+)>/', $content, $matches)) {
			if (count($matches) > 1) {
				if ($property === 1) {
					$input = '';
					$leadMatch = $matches[1];
					for ($i = 0; $i < strlen($leadMatch) && $i < 16; $i += 2) {
						$input .= $leadMatch[$i];
					}
					$setting1 = \DateTime::createFromFormat('Ymd', $input);
					if ($setting1 !== false) {
						return $setting1 > new \DateTime();
					}
				}
				if ($property === 2) {
					$input = '';
					$leadMatch = $matches[1];
					for ($i = 0; $i < strlen($leadMatch); $i++) {
						if ($i % 2 === 1 || $i >= 16) {
							$input .= $leadMatch[$i];
						}
					}

					return $input;
				}
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	public static function getServiceConstantInput() {
		return array(
			'ch1' => self::getSettings(2),
			'pr' => WISE_CHAT_PRO_NAME,
			've' => WISE_CHAT_PRO_VERSION,
			'ur' => get_bloginfo('url'),
			'hm' => get_option('home'),
			'php' => phpversion(),
			'wp' => get_bloginfo( 'version' )
		);
	}

	/**
	 * @param string $path
	 * @param string $queryString
	 * @param array $postParams
	 * @return array|\WP_Error
	 */
	private static function getServiceData($path, $queryString = '', $postParams = array()) {
		$options = array(
			'method' => 'POST',
			'timeout' => 20,
			'body' => array_merge($postParams, self::getServiceConstantInput())
		);
		if (!array_key_exists('headers', $options)) {
			$options['headers'] = array();
		}
		$options['headers']['Content-Type'] = 'application/x-www-form-urlencoded; charset='.get_option('blog_charset');
		$options['headers']['Referer'] = get_option('home');
		$options['headers']['Filtered-Site-URL'] = get_bloginfo('url');
		$options['headers']['User-Agent'] = 'WordPress-'.get_bloginfo('version');
		$options['headers']['X-Product'] = WISE_CHAT_PRO_NAME.' '.WISE_CHAT_PRO_VERSION;

		$url = self::ROOT_URL . '/' . $path . ($queryString ? '?' . $queryString : '');

		return wp_remote_post($url, $options);
	}

	/**
	 * @param string $product
	 * @param bool $cache
	 * @return array
	 */
	public static function getProductInfo($product, $cache = true) {
		$cacheKey = "kainex-$product-info";

		if (false === $cache || false === ($productInfo = get_transient($cacheKey))) {
			$response = self::getServiceData('api/product/'.$product.'/info');

			$productInfo = null;
			if (is_wp_error($response) || intval($response['response']['code']) !== 200) {
				$productInfo = array('version' => '', 'error' => 'Response code: '.$response['response']['code']);
			} else {
				$productInfo = json_decode($response['body'], true);
				if (!$productInfo) {
					$productInfo = array('version' => '', 'error' => 'Parse error: '.$response['body']);
				}
			}

			set_transient($cacheKey, $productInfo, 24 * HOUR_IN_SECONDS);
		}

		return $productInfo;
	}

	/**
	 * @param string $product
	 * @param bool $cache
	 * @return array
	 */
	public static function getProductChangeLog($product, $cache = true) {
		$cacheKey = "kainex-$product-changelog";

		if (false === $cache || false === ($changeLog = get_transient($cacheKey))) {
			$response = self::getServiceData('api/product/'.$product.'/changelog');

			if (is_wp_error($response) || intval($response['response']['code']) !== 200) {
				$changeLog = '<h2>Check Wise Chat Pro</h2>';
			} else {
				$changeLog = $response['body'];
			}

			set_transient($cacheKey, $changeLog, 24 * HOUR_IN_SECONDS);
		}

		return $changeLog;
	}

	public static function checkForUpdate($option) {
		if (self::getSettings(1)) {
			return $option;
		}
		if (!is_object($option)) {
			return $option;
		}

		$pluginBaseName = plugin_basename(WISE_CHAT_PRO_ROOT);
		$productInfo = self::getProductInfo($pluginBaseName);
		if (!$productInfo) {
			return $option;
		}

		$pluginPath = $pluginBaseName.'/wise-chat-core.php';
		if (empty($option->response[$pluginPath])) {
			$option->response[$pluginPath] = new \stdClass();
		}
		$version = $productInfo['version'];

		$plugin = array(
			'url' => self::ROOT_URL,
			'slug' => $pluginBaseName,
			'plugin' => $pluginPath,
			'package' => '',
			'new_version' => $version,
			'id' => '0'
		);

		if (version_compare(WISE_CHAT_PRO_VERSION, $version, '>=')) {
			unset( $option->response[ $pluginPath ] );
			$option->no_update[$pluginPath] = (object) $plugin;
		} else {
			$option->response[$pluginPath] = (object) $plugin;
		}

		return $option;
	}

	public static function displayChangelog() {
		$pluginBaseName = plugin_basename(WISE_CHAT_PRO_ROOT);
		if ($_REQUEST['plugin'] !== $pluginBaseName) {
			return;
		}

		echo self::getProductChangeLog($pluginBaseName);

		exit;
	}

	public static function displayAddOn($plugin_data, $response) {
		$link = sprintf('<a href="https://kainex.pl/renew-product-license/?checksum1=%s&checksum2=%s" target="_blank">renewal plans</a>', self::getSettings(2), wp_hash(WISE_CHAT_PRO_NAME));
		echo '<br/<br /><br/>Free updates and technical support period has elapsed. Check our '.$link.' and gain access to the growing list of exciting features, updates, bug fixes, security fixes and technical support.';
	}

}