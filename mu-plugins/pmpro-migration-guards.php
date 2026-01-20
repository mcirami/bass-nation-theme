<?php
/**
 * Guard during migration: prevent NON-Stripe events (e.g., Braintree webhook)
 * from canceling an active Stripe subscription. Still allow a normal, user-
 * initiated cancel for Stripe orders.
 *
 * Drop in: wp-content/mu-plugins/pmpro-migration-guards.php
 */

// 1) Don't cancel "previous subscriptions" at gateway if user already has a Stripe customer.
// This path is typically hit on gateway webhooks/level changes. Keep it narrow and temporary.
add_filter('pmpro_cancel_previous_subscriptions', function ($cancel, $user_id = 0) {

	$user_id = (int) $user_id;
	if ($user_id <= 0) {
		// PMPro didn't pass a user id in this context, so don't interfere.
		return $cancel;
	}

	// If user has a Stripe customer on file, skip canceling previous subs at other gateways.
	$has_stripe_customer = (bool) get_user_meta($user_id, 'pmpro_stripe_customerid', true);

	return $has_stripe_customer ? false : $cancel;

}, 10, 2);

// 2) Only block gateway-cancel calls when they are NOT for a Stripe order.
//    This lets real cancels of Stripe subs from your site proceed normally.
add_filter('pmpro_gateway_cancel_subscription_at_gateway', function ($ok_to_cancel, $order = null) {
	if (empty($order) || empty($order->user_id)) {
		return $ok_to_cancel;
	}

	if (!empty($order->gateway) && strtolower($order->gateway) !== 'stripe') {
		$has_stripe_customer = (bool) get_user_meta((int)$order->user_id, 'pmpro_stripe_customerid', true);
		if ($has_stripe_customer) {
			return false;
		}
	}

	return $ok_to_cancel;
}, 10, 2);