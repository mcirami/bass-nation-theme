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

	public static function init() {
		// Decide whether a level "has a trial" for this user.
		add_filter('pmpro_checkout_level', [__CLASS__, 'filter_pmpro_checkout_level'], 20);

		// After checkout, mark trial used if this checkout included a trial.
		add_action('pmpro_after_checkout', [__CLASS__, 'action_pmpro_after_checkout'], 20, 2);

		add_filter('pmpro_level_cost_text', [__CLASS__, 'filter_level_cost_text'], 20, 4);
		add_filter('pmpro_levels_cost_text', [__CLASS__, 'filter_levels_cost_text'], 20, 4);
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
		if ($trial_limit <= 0) {
			return $level;
		}

		$user_id = get_current_user_id();

		if (self::trial_should_be_blocked($user_id)) {
			$level->trial_limit  = 0;
			$level->trial_amount = 0.00;

			// Optional: if you ever set a trial billing limit, this makes it irrelevant.
			// Leave other pricing alone (initial_payment, billing_amount, cycle, etc.)
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
			$trial_used = get_user_meta($user_id, self::META_TRIAL_USED, true);
			if (!empty($trial_used)) {
				return true;
			}

			// 4) Stripe customer exists => no trial
			$stripe_customer_id = get_user_meta($user_id, self::META_STRIPE_CUST_KEY, true);
			if (!empty($stripe_customer_id)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * After checkout completes, if the order appears to include a trial, permanently mark it used.
	 */
	public static function action_pmpro_after_checkout($user_id, $morder): void {
		if (empty($user_id) || empty($morder)) {
			return;
		}

		// Get the level used at checkout. Prefer order-level data if present.
		$level = null;

		if (!empty($morder->membership_level)) {
			$level = $morder->membership_level;
		} elseif (method_exists($morder, 'getMembershipLevelAtCheckout')) {
			$level = $morder->getMembershipLevelAtCheckout();
		}

		if (empty($level)) {
			return;
		}

		$trial_limit = isset($level->trial_limit) ? (int) $level->trial_limit : 0;
		if ($trial_limit <= 0) {
			return;
		}

		// Mark permanent per-user flags
		if (!get_user_meta($user_id, self::META_TRIAL_USED, true)) {
			update_user_meta($user_id, self::META_TRIAL_USED, 1);
			update_user_meta($user_id, self::META_TRIAL_USED_AT, current_time('mysql'));
		}

		// Mark browser cookie
		self::set_device_trial_cookie();

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

		// Only change text if THIS visitor is not eligible for a trial (same logic as before)
		$user_id = get_current_user_id();

		// If trial should be blocked, rewrite the display text
		if (self::trial_should_be_blocked($user_id)) {
			// IMPORTANT: use the checkout-adjusted level (trial stripped) so text matches billing.
			$checkout_level = apply_filters('pmpro_checkout_level', $level);

			// Build a clean non-trial message.
			$amt   = !empty($checkout_level->billing_amount) ? pmpro_formatPrice($checkout_level->billing_amount) : '$0.00';
			$cycle = !empty($checkout_level->cycle_number) ? (int) $checkout_level->cycle_number : 1;
			$per   = !empty($checkout_level->cycle_period) ? strtolower($checkout_level->cycle_period) : 'month';
			$unit  = ($cycle === 1) ? $per : "{$cycle} {$per}s";

			return sprintf('%s per %s.', $amt, $unit);
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

}

PMPro_One_Trial_Only_Guard::init();
