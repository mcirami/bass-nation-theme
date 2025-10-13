<?php
/**
 * PMPro + Stripe: apply first-cycle coupon and pass metadata at checkout.
 * Requires: Stripe coupons set to duration=once.
 */
add_filter('pmpro_stripe_checkout_session_parameters', function ($params, $morder, $customer) {

	// 0) Detect the campaign (URL or cookie)
	$dc = isset($_REQUEST['pmpro_discount_code']) ? strtoupper(sanitize_text_field($_REQUEST['pmpro_discount_code'])) : '';;
	if ($dc !== 'BNATION25') return $params;

	// 1) Map PMPro level -> Stripe COUPON ID (duration=once)
	$level_id = isset($morder->membership_id) ? (int) $morder->membership_id : 0;

	// Use your TEST coupon IDs while testing; LIVE in production.
	$env = function_exists('pmpro_getOption') ? pmpro_getOption('gateway_environment') : 'sandbox';

	$map_live = [
		1 => '89mIT2xj', // Monthly 20% off
		2 => 'BN6OFF', // $6.00 off (3 Months 20% off)
		3 => '89mIT2xj', // 6 Months 20% off
		4 => '5YYMKqH2', // Annual 25% off
	];
	$map_test = [
		1 => 'bAm6KxOv',  // Monthly 20% off
		2 => 'BN6OFF', // $6.00 off (3 Months 20% off)
		3 => 'bAm6KxOv',  // 6 Months 20% off
		4 => 'diZsudJy', // Annual 25% off
	];

	$coupon_id = ($env === 'live') ? ($map_live[$level_id] ?? '') : ($map_test[$level_id] ?? '');
	if (!$coupon_id) return $params;

	// 2) Don’t mix allow_promotion_codes with a pre-applied discount
	unset($params['allow_promotion_codes']);

	// 3) Apply the one-cycle coupon to the Checkout Session
	$params['discounts'] = [['coupon' => $coupon_id]];

	// 4) Add helpful metadata for reconciliation
	$user = is_user_logged_in() ? wp_get_current_user() : null;
	$email = $user ? $user->user_email : (isset($_REQUEST['e']) ? sanitize_email($_REQUEST['e']) : '');

	$utm = [];
	foreach (['utm_source','utm_medium','utm_campaign','utm_content'] as $k) {
		if (!empty($_REQUEST[$k])) $utm[$k] = sanitize_text_field($_REQUEST[$k]);
	}

	$params['client_reference_id'] = (string) get_current_user_id();
	$params['metadata'] = array_merge([
		'wp_user_id'          => (string) get_current_user_id(),
		'wp_user_email'       => $email,
		'pmpro_level_id'      => (string) $level_id,
		'pmpro_discount_code' => $dc,
		'campaign'            => 'winback',
	], $utm);

	return $params;
}, 10, 3);
