<?php
/**
 * Plugin Name: PMPro - One Trial Only (Meta + Stripe Customer Guard)
 * Description: Prevents trial hopping by allowing only one trial per WP user. Blocks trials for users who already have a Stripe customer ID. Stores a permanent "trial used" flag.
 * Author: Your Team
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

final class PMPro_One_Trial_Only_Guard {

	// Permanent per-user flags
	const string META_TRIAL_USED      = '_pmpro_trial_used';
	const string META_TRIAL_USED_AT   = '_pmpro_trial_used_at';

	// PMPro Stripe stores customer id here
	const string META_STRIPE_CUST_KEY = 'pmpro_stripe_customerid'; // PMPro Stripe stores customer id here

	// Device/IP guards
	const string COOKIE_NAME          = 'pmpro_trial_used';   // set in browser after trial is used
	const int COOKIE_DAYS          = 365;                  // how long to block in that browser
	// Turn on while testing, then set false
	const DEBUG = false;
// Request flag so cost-text filters can match what checkout-level did
	private static $trial_removed_this_request = false;

	public static function init() {
		// Decide whether a level "has a trial" for this user.
		add_filter('pmpro_checkout_level', [__CLASS__, 'filter_pmpro_checkout_level'], 1, 1);

		add_filter('pmpro_level_cost_text', [__CLASS__, 'filter_level_cost_text'], 20, 4);
		add_filter('pmpro_levels_cost_text', [__CLASS__, 'filter_levels_cost_text'], 20, 4);

		// After checkout, mark trial used if this checkout included a trial.
		add_action('pmpro_after_checkout', [__CLASS__, 'action_pmpro_after_checkout'], 20, 2);

		add_filter('pmpro_stripe_checkout_session_parameters', [__CLASS__, 'filter_stripe_checkout_session_parameters'], 20, 2);

		add_action('init', [__CLASS__, 'debug_spy_stripe_hooks'], 0);

		add_action('wp_footer', [__CLASS__, 'debug_log_stripe_checkout_text'], 99);

		add_action('wp_loaded', [__CLASS__, 'debug_spy_stripe_hooks'], 0);
	}

	/**
	 * Filter whether the given level should be considered to have a trial for this user.
	 *
	 * @param $level
	 *
	 * @return bool
	 */
	public static function filter_pmpro_checkout_level($level) {
		if (empty($level) || empty($level->id)) {
			return $level;
		}

		// If this level doesn't even have a trial, do nothing.
		$trial_limit = isset($level->trial_limit) ? (int) $level->trial_limit : 0;


		$user_id = get_current_user_id();
		$blocked = self::trial_should_be_blocked($user_id);

		if (self::DEBUG) {
			self::log('pmpro_checkout_level', [
				'user_id' => $user_id,
				'level_id' => $level->id,
				'trial_limit_before' => $trial_limit,
				'blocked' => $blocked ? 1 : 0,
				'has_cookie' => !empty($_COOKIE[self::COOKIE_NAME]) ? 1 : 0,
				'has_trial_meta' => $user_id ? (get_user_meta($user_id, self::META_TRIAL_USED, true) ? 1 : 0) : null,
				'stripe_customer' => $user_id ? (get_user_meta($user_id, self::META_STRIPE_CUST_KEY, true) ? 1 : 0) : null,
				'request' => [
					'pmpro_level' => isset($_REQUEST['level']) ? sanitize_text_field((string)$_REQUEST['level']) : null,
					'action' => isset($_REQUEST['action']) ? sanitize_text_field((string)$_REQUEST['action']) : null,
				]
			]);
		}

		// If the level has a trial and user is blocked, strip it.
		if ($trial_limit > 0 && $blocked) {
			$level->trial_limit  = 0;
			$level->trial_amount = 0.00;
			self::$trial_removed_this_request = true;

			if (self::DEBUG) {
				self::log('trial_stripped', [
					'user_id' => $user_id,
					'level_id' => $level->id,
				]);
			}
		}

		return $level;
	}

	private static function trial_should_be_blocked($user_id): bool {
		// 1) Device/browser cookie (blocks “new email, same browser”)
		if (!empty($_COOKIE[self::COOKIE_NAME])) {
			return true;
		}

		// 2) Per-user permanent flag
		if (!empty($user_id)) {
			if (get_user_meta($user_id, self::META_TRIAL_USED, true)) {
				return true;
			}

			// 4) Stripe customer exists => no trial
			// Stripe customer id exists
			if (get_user_meta($user_id, self::META_STRIPE_CUST_KEY, true)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * After checkout completes, if the order appears to include a trial, permanently mark it used.
	 */
	public static function action_pmpro_after_checkout($user_id, $morder): void {
		if (empty($user_id) || empty($morder) || empty($morder->membership_id)) {
			return;
		}

		if (!function_exists('pmpro_getLevel')) {
			return;
		}

		// Get the level used at checkout. Prefer order-level data if present.
		$level = pmpro_getLevel((int)$morder->membership_id);;
		if (empty($level)) return;

		$trial_limit = isset($level->trial_limit) ? (int) $level->trial_limit : 0;

		if (self::DEBUG) {
			self::log('pmpro_after_checkout', [
				'user_id' => $user_id,
				'level_id' => (int)$morder->membership_id,
				'trial_limit_on_level' => $trial_limit,
				'initial_payment' => isset($morder->InitialPayment) ? $morder->InitialPayment : null,
			]);
		}

		// If this level has a trial, we permanently mark the user as having used one.
		if ($trial_limit > 0) {
			if (!get_user_meta($user_id, self::META_TRIAL_USED, true)) {
				update_user_meta($user_id, self::META_TRIAL_USED, 1);
				update_user_meta($user_id, self::META_TRIAL_USED_AT, current_time('mysql'));
			}

			self::set_device_trial_cookie();
		}

	}

	private static function set_device_trial_cookie(): void {
		if (headers_sent()) {
			return;
		}

		$expire = time() + (DAY_IN_SECONDS * (int) self::COOKIE_DAYS);

		setcookie(self::COOKIE_NAME, '1', [
				'expires'  => $expire,
				'path'     => COOKIEPATH ?: '/',
				'domain'   => COOKIE_DOMAIN ?: '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
		]);

		$_COOKIE[self::COOKIE_NAME] = '1';
	}

	public static function filter_level_cost_text($cost_text, $level, $tags = true, $short = false) {
		if (empty($level) || empty($level->id)) {
			return $cost_text;
		}

		// If we stripped the trial this request, force a non-trial cost string
		if (self::$trial_removed_this_request || self::trial_should_be_blocked(get_current_user_id())) {
			$checkout_level = apply_filters('pmpro_checkout_level', $level);
			$trial_limit = isset($checkout_level->trial_limit) ? (int) $checkout_level->trial_limit : 0;

			// If trial is gone, ensure the text doesn't mention it.
			if ($trial_limit <= 0) {
				$amt   = !empty($checkout_level->billing_amount) ? pmpro_formatPrice($checkout_level->billing_amount) : '$0.00';
				$cycle = !empty($checkout_level->cycle_number) ? (int) $checkout_level->cycle_number : 1;
				$per   = !empty($checkout_level->cycle_period) ? strtolower($checkout_level->cycle_period) : 'month';
				$unit  = ($cycle === 1) ? $per : "{$cycle} {$per}s";

				return sprintf('%s per %s.', $amt, $unit);
			}
		}

		return $cost_text;
	}

	public static function filter_levels_cost_text($r, $levels, $tags = true, $short = false) {
		if (empty($levels) || !is_array($levels)) return $r;

		// On checkout there is usually a single level in play; update the string using that level.
		// We rebuild cost text through the single-level filter so behavior stays consistent.
		$level = reset($levels);
		if (empty($level)) return $r;

		return self::filter_level_cost_text($r, $level, $tags, $short);
	}

	private static function log($label, array $data = []): void {
		// Writes to wp-content/debug.log when WP_DEBUG_LOG is enabled.
		error_log('[PMPro Trial Guard] ' . $label . ' ' . wp_json_encode($data));
	}

	//debug helpers
	public static function debug_log_cost_text($cost_text, $level, $tags = true, $short = false) {
		// Only log on checkout
		if (!function_exists('pmpro_is_checkout') || !pmpro_is_checkout()) {
			return $cost_text;
		}

		$uid = get_current_user_id();
		$trial_limit = isset($level->trial_limit) ? (int) $level->trial_limit : null;

		self::log('COST_TEXT (single)', [
			'user_id' => $uid,
			'level_id' => isset($level->id) ? $level->id : null,
			'trial_limit' => $trial_limit,
			'blocked' => self::trial_should_be_blocked($uid) ? 1 : 0,
			'cost_text' => $cost_text,
			'uri' => $_SERVER['REQUEST_URI'] ?? null,
			'request' => [
				'level' => $_REQUEST['level'] ?? null,
				'action' => $_REQUEST['action'] ?? null,
			],
		]);

		return $cost_text;
	}

	public static function debug_log_levels_cost_text($r, $levels, $tags = true, $short = false) {
		if (!function_exists('pmpro_is_checkout') || !pmpro_is_checkout()) {
			return $r;
		}

		$level = (is_array($levels) && !empty($levels)) ? reset($levels) : null;
		$uid = get_current_user_id();

		self::log('COST_TEXT (plural)', [
			'user_id' => $uid,
			'level_id' => $level->id ?? null,
			'trial_limit' => isset($level->trial_limit) ? (int)$level->trial_limit : null,
			'blocked' => self::trial_should_be_blocked($uid) ? 1 : 0,
			'text' => $r,
			'uri' => $_SERVER['REQUEST_URI'] ?? null,
		]);

		return $r;
	}

	/**
	 * Captures final checkout HTML and logs the exact snippets containing "trial".
	 * This is the "show me where it came from" hammer.
	 */
	public static function debug_checkout_output_buffer() {
		if (!function_exists('pmpro_is_checkout') || !pmpro_is_checkout()) {
			return;
		}

		// Start buffering the full page output
		ob_start(function($html) {
			$needles = [
				'trial',
				'3 day',
				'3-day',
				'free',
				'after your',
				'after  your',
				'9.99 per',
				'$9.99',
				'&#36;9.99',
			];

			$found_any = false;
			foreach ($needles as $needle) {
				if (stripos($html, $needle) !== false) {
					$found_any = true;
					break;
				}
			}

			if ($found_any) {
				$snippets = [];
				foreach ($needles as $needle) {
					$pos = stripos($html, $needle);
					if ($pos === false) continue;

					$start = max(0, $pos - 250);
					$end   = min(strlen($html), $pos + 250);
					$snippets[$needle] = substr($html, $start, $end - $start);

					// cap to a few needles
					if (count($snippets) >= 5) break;
				}

				self::log('FINAL_HTML contains pricing/trial needle', [
					'user_id' => get_current_user_id(),
					'uri' => $_SERVER['REQUEST_URI'] ?? null,
					'snippets' => $snippets,
				]);
			}

			return $html; // IMPORTANT: must return HTML so page still renders
		});
	}

	public static function filter_stripe_subscription_args($args, $order, $level) {
		$user_id = !empty($order->user_id) ? (int)$order->user_id : get_current_user_id();
		if (!self::trial_should_be_blocked($user_id)) {
			return $args;
		}

		// Remove any trial settings Stripe might honor
		unset($args['trial_period_days']);
		unset($args['trial_end']);

		// Some implementations pass trial settings nested under subscription_data
		if (!empty($args['subscription_data']) && is_array($args['subscription_data'])) {
			unset($args['subscription_data']['trial_period_days']);
			unset($args['subscription_data']['trial_end']);
		}

		self::log('STRIPE subscription args trial stripped', [
			'user_id' => $user_id,
			'level_id' => $level->id ?? null,
			'keys_present' => array_values(array_intersect(array_keys($args), ['trial_period_days','trial_end','subscription_data'])),
		]);

		return $args;
	}

	public static function filter_stripe_checkout_session_args($args, $order) {
		$user_id = !empty($order->user_id) ? (int)$order->user_id : get_current_user_id();
		if (!self::trial_should_be_blocked($user_id)) {
			return $args;
		}

		// Stripe Checkout Sessions often put subscription settings here:
		// $args['subscription_data']['trial_period_days'] or trial_end
		if (!empty($args['subscription_data']) && is_array($args['subscription_data'])) {
			unset($args['subscription_data']['trial_period_days']);
			unset($args['subscription_data']['trial_end']);
		}

		// Some variants might place trial keys at top-level too
		unset($args['trial_period_days']);
		unset($args['trial_end']);

		self::log('STRIPE checkout session args trial stripped', [
			'user_id' => $user_id,
			'has_subscription_data' => !empty($args['subscription_data']) ? 1 : 0,
		]);

		return $args;
	}

	public static function debug_log_stripe_checkout_text() {
		if (!function_exists('pmpro_is_checkout') || !pmpro_is_checkout()) return;
		if (get_current_user_id() !== 11434) return;
		?>
		<script>
			(function () {
				console.log('[PMPro Trial Guard] checkout URL:', window.location.href);
			})();
		</script>
		<?php
	}

	public static function debug_spy_stripe_hooks() {
		// Only while debugging
		if (!defined('WP_DEBUG') || !WP_DEBUG) return;
		if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) return;

		// Only for your test user
		if (get_current_user_id() !== 11434) return;

		// Only on the checkout URL (works even if pmpro_is_checkout() isn't ready/true)
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		if (stripos($uri, '/membership-checkout/') === false) return;

		static $seen = [];

		// "all" is a special hook that fires for every hook.
		// Use add_action, and accept a bunch of args safely.
		add_action('all', function () use (&$seen) {
			$hook = current_filter();

			// Focus on Stripe-related hooks
			if (stripos($hook, 'stripe') === false) {
				return;
			}

			// Log each hook only once per request
			if (isset($seen[$hook])) return;
			$seen[$hook] = true;

			error_log('[PMPro Trial Guard] SPY hook fired: ' . $hook);
		}, 1, 99);

		// Prove spy is armed
		error_log('[PMPro Trial Guard] SPY armed on ' . $uri);
	}

	public static function filter_stripe_checkout_session_parameters($params, $order) {
		$user_id = 0;

		// PMPro orders usually have user_id
		if (!empty($order) && !empty($order->user_id)) {
			$user_id = (int) $order->user_id;
		} else {
			$user_id = get_current_user_id();
		}

		// Only enforce when trial should be blocked
		if (!self::trial_should_be_blocked($user_id)) {
			return $params;
		}

		// Stripe Checkout (subscription mode) puts trial config inside subscription_data.
		if (!empty($params['subscription_data']) && is_array($params['subscription_data'])) {
			unset($params['subscription_data']['trial_period_days']);
			unset($params['subscription_data']['trial_end']);
		}

		// Some implementations may pass trial keys at top-level (rare, but safe to remove)
		unset($params['trial_period_days']);
		unset($params['trial_end']);

		// Helpful debug while testing
		self::log('STRIPE session params trial stripped', [
			'user_id' => $user_id,
			'has_subscription_data' => !empty($params['subscription_data']) ? 1 : 0,
			'subscription_data_keys' => !empty($params['subscription_data']) && is_array($params['subscription_data'])
				? array_keys($params['subscription_data'])
				: [],
		]);

		return $params;
	}
}

PMPro_One_Trial_Only_Guard::init();


// --- WP-CLI command: mark trial used for anyone with past PMPro orders ---
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * Mark PMPro "trial used" for any user who has previously purchased (has order history).
	 *
	 * Usage:
	 *   wp pmpro mark-trial-used-from-orders --dry-run
	 *   wp pmpro mark-trial-used-from-orders
	 *
	 * Options:
	 *   --dry-run   Only show counts; do not update usermeta.
	 */
	WP_CLI::add_command( 'pmpro mark-trial-used-from-orders', function ( $args, $assoc_args ) {

		global $wpdb;

		$dry_run = isset( $assoc_args['dry-run'] );

		$orders_table  = $wpdb->prefix . 'pmpro_membership_orders';
		$meta_key_used = '_pmpro_trial_used';
		$meta_key_at   = '_pmpro_trial_used_at';

		// Treat these as "they previously checked out / had a subscription at some point".
		// Excludes failures and pending attempts.
		$valid_statuses = [ 'success', 'cancelled', 'refunded' ];

		$placeholders = implode( ',', array_fill( 0, count( $valid_statuses ), '%s' ) );

		// Pull distinct user IDs who have qualifying orders.
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT user_id
				 FROM {$orders_table}
				 WHERE user_id > 0
				   AND status IN ({$placeholders})",
				...$valid_statuses
			)
		);

		if ( empty( $user_ids ) ) {
			WP_CLI::success( 'No matching users found in pmpro_membership_orders.' );

			return;
		}

		$total = count( $user_ids );

		// Of those, how many already have the meta set?
		$already   = 0;
		$to_update = [];

		foreach ( $user_ids as $uid ) {
			$uid = (int) $uid;
			$has = get_user_meta( $uid, $meta_key_used, true );
			if ( ! empty( $has ) ) {
				$already ++;
				continue;
			}
			$to_update[] = $uid;
		}

		$pending = count( $to_update );

		WP_CLI::log( "Users with qualifying PMPro orders: {$total}" );
		WP_CLI::log( "Already marked ({$meta_key_used}=1): {$already}" );
		WP_CLI::log( "Will be marked now: {$pending}" );

		if ( $dry_run ) {
			WP_CLI::success( 'Dry run complete. No usermeta updated.' );

			return;
		}

		// Mark them.
		$now = current_time( 'mysql' );

		$updated = 0;
		foreach ( $to_update as $uid ) {
			update_user_meta( $uid, $meta_key_used, 1 );
			update_user_meta( $uid, $meta_key_at, $now );
			$updated ++;
		}

		WP_CLI::success( "Done. Marked {$updated} users as trial-used." );
	} );
}
