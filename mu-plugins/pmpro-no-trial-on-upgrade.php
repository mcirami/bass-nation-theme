<?php
/**
 * Plugin Name: PMPro - Remove Stripe Trial on Upgrades (Stripe Checkout)
 */

function db_is_pmpro_upgrade($morder) {
	$user_id = !empty($morder->user_id) ? (int) $morder->user_id : 0;
	if ($user_id <= 0) return false;

	$current = pmpro_getMembershipLevelForUser($user_id);
	$current_level_id = !empty($current->id) ? (int) $current->id : 0;

	$new_level_id = !empty($morder->membership_id) ? (int) $morder->membership_id : 0;

	// Upgrade/change = user already has a level, and it's different than the one being purchased.
	return ($current_level_id > 0 && $new_level_id > 0 && $new_level_id !== $current_level_id);
}

add_filter('pmpro_stripe_checkout_session_parameters', function($params, $morder, $customer = null) {

	if (empty($morder) || !db_is_pmpro_upgrade($morder)) {
		return $params; // New signup or unknown: leave trial behavior alone.
	}

	// Stripe Checkout subscription trial settings live here.
	if (!empty($params['subscription_data']) && is_array($params['subscription_data'])) {
		unset($params['subscription_data']['trial_end']);
		unset($params['subscription_data']['trial_period_days']);
		unset($params['subscription_data']['trial_settings']); // just in case
	}

	return $params;

}, 10, 3);