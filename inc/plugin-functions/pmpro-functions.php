<?php
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\Subscription;

/*
	Add this code to your active theme's functions.php or a custom plugin
	to change the PayPal button image on the PMPro checkout page.
*/
function my_pmpro_paypal_button_image($url)
{
	return "https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-large.png";
}
add_filter('pmpro_paypal_button_image', 'my_pmpro_paypal_button_image');

add_filter('pmpro_send_email', function($send, $email){
	if (!empty($email->template) && in_array($email->template, ['cancelled','cancelled_admin'])) {
		return false; // suppress member + admin cancel emails during migration
	}
	return $send;
}, 10, 2);

add_action('pmpro_after_checkout', 'set_stripe_default_payment_method', 10, 2);
function set_stripe_default_payment_method($user_id, $order) {
	global $gateway;

	// Only run for Stripe gateway
	if (strtolower($gateway) !== 'stripe') {
		error_log('$gateway !== stripe :' . $gateway);
		return;
	}

	// Make sure we have the necessary Stripe data
	if (empty($order->payment_method_id) || empty($order->Gateway->customer)) {
		error_log('PMPro Stripe empty($order->payment_method_id) || empty($order->Gateway->customer): ');
		return;
	}

	try {
		// Get Stripe customer ID
		$stripe_customer_id = $order->Gateway->customer->id;
		$payment_method_id = $order->payment_method_id;

		// Initialize Stripe
		if (!class_exists('\Stripe\Stripe')) {
			require_once(PMPRO_DIR . '/includes/lib/Stripe/init.php');
		}

		$stripe_secret_key = get_option('pmpro_stripe_secretkey');
		if (pmpro_getOption('gateway_environment') === 'sandbox') {
			$stripe_secret_key = get_option('pmpro_stripe_secretkey_test');
		}

		Stripe::setApiKey($stripe_secret_key);

		// Attach payment method to customer (if not already attached)
		$payment_method = PaymentMethod::retrieve($payment_method_id);

		if (empty($payment_method->customer)) {
			$payment_method->attach(['customer' => $stripe_customer_id]);
		}

		// Set as default payment method for invoices/subscriptions
		Customer::update(
			$stripe_customer_id,
			[
				'invoice_settings' => [
					'default_payment_method' => $payment_method_id
				]
			]
		);

		// Optional: Also set on subscription if this is a recurring membership
		if (pmpro_isLevelRecurring($order->membership_level) && !empty($order->subscription_transaction_id)) {
			Subscription::update(
				$order->subscription_transaction_id,
				[
					'default_payment_method' => $payment_method_id
				]
			);
		}

	} catch ( ApiErrorException $e) {
		// Log error for debugging
		error_log('PMPro Stripe Default PM Error: ' . $e->getMessage());
	} catch (\Exception $e) {
		error_log('PMPro Stripe Default PM Error: ' . $e->getMessage());
	}
}

/**
 * Require user's checking out for any level that requires billing to match their IP address with billing country address fields.
 * Only works with levels that require billing fields.
 * Please install and activate the following plugin - https://wordpress.org/plugins/geoip-detect/
 */
/*function pmpro_require_location_match_IP($continue)
{

	global $pmpro_requirebilling;

	// If something else is wrong or billing fields aren't required, don't run this code further.
	if (!$continue || !$pmpro_requirebilling) {
		return $continue;
	}

	// If GEOIP plugin not installed, bail.
	if (!function_exists('geoip_detect2_get_info_from_current_ip')) {
		pmpro_setMessage("Unable to obtain user's location. Function 'geoip_detect2_get_info_from_current_ip' does not exist.", "pmpro_error");
		return false;
	}

	// Compare IP with billing fields.
	$ip_country = geoip_detect2_get_info_from_current_ip()->country->isoCode;
	$billing_country = isset($_REQUEST['bcountry']) ? sanitize_text_field($_REQUEST['bcountry']) : '';

	// Unable to get IP Country.
	if (empty($ip_country)) {
		pmpro_setMessage("Unable to obtain user's location.", "pmpro_error");
		return false;
	}

	// Unable to get billing field.
	if (empty($billing_country)) {
		pmpro_setMessage("Unable to get billing country field.", "pmpro_error");
		return false;
	}

	if ($ip_country == $billing_country) {
		$okay = true;
	} else {
		pmpro_setMessage("Your location does not match your billing location.", "pmpro_error");
		$okay = false;
	}

	return $okay;
}
add_filter('pmpro_registration_checks', 'pmpro_require_location_match_IP');*/