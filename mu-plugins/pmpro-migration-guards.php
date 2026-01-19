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
add_filter('pmpro_cancel_previous_subscriptions',
	function ($cancel, $user_id) {
		// If user has a Stripe customer on file, skip canceling previous subs at *other* gateways.
		$has_stripe_customer = (bool) get_user_meta($user_id, 'pmpro_stripe_customerid', true);
		return $has_stripe_customer ? false : $cancel;
}, 10, 2);

// 2) Only block gateway-cancel calls when they are NOT for a Stripe order.
//    This lets real cancels of Stripe subs from your site proceed normally.
add_filter('pmpro_gateway_cancel_subscription_at_gateway', function ($ok_to_cancel, $order) {
	if (empty($order) || empty($order->user_id)) {
		return $ok_to_cancel;
	}

	// If PMPro is attempting to cancel a non-Stripe order (e.g., braintree/paypal)
	// but this user has a Stripe customer attached, block it so we don't touch Stripe.
	if (!empty($order->gateway) && $order->gateway !== 'stripe') {
		$has_stripe_customer = (bool) get_user_meta($order->user_id, 'pmpro_stripe_customerid', true);
		if ($has_stripe_customer) {
			return false; // block accidental Stripe cancels caused by other gateways' events
		}
	}

	// For Stripe orders (user-initiated cancel via your site), allow.
	return $ok_to_cancel;
}, 10, 2);