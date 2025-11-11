<?php
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Subscription;

/*
	Add this code to your active theme's functions.php or a custom plugin
	to change the PayPal button image on the PMPro checkout page.
*/
function my_pmpro_paypal_button_image($url)
{
	return "https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-large.png";
}
//add_filter('pmpro_paypal_button_image', 'my_pmpro_paypal_button_image');

add_filter('pmpro_send_email', function($send, $email){
	if (!empty($email->template) && in_array($email->template, ['cancelled','cancelled_admin'])) {
		return false; // suppress member + admin cancel emails during migration
	}
	return $send;
}, 10, 2);

/**
 * Set the Stripe payment method used at checkout as the default for the customer.
 * Works with Paid Memberships Pro using Stripe Checkout.
 */
/*add_action( 'pmpro_stripe_checkout_session_completed', function( $session ) {
	error_log( '$session->customer: ' . $session->customer );
	error_log( '$session->payment_method: ' . $session->payment_method );

	if ( empty( $session->customer ) || empty( $session->payment_method ) ) {
		return;
	}

	try {

		$stripe_secret_key =  pmpro_getOption( 'stripe_secretkey' );
		if (pmpro_getOption('gateway_environment') === 'sandbox') {
			$stripe_secret_key = get_option('pmpro_stripe_secretkey_test');
		}
		// Initialize Stripe client with PMPro's stored secret key.
		$stripe = new StripeClient($stripe_secret_key) ;

		// Update the customer's default payment method.
		$stripe->customers->update(
			$session->customer,
			[
				'invoice_settings' => [
					'default_payment_method' => $session->payment_method,
				],
			]
		);

		// Optional: Update any active subscriptions to use this payment method.
		$subscriptions = $stripe->subscriptions->all(['customer' => $session->customer, 'limit' => 10]);
		foreach ( $subscriptions->data as $sub ) {
			$stripe->subscriptions->update(
				$sub->id,
				['default_payment_method' => $session->payment_method]
			);
		}

	} catch ( Exception $e ) {
		error_log( 'Stripe default payment method update failed: ' . $e->getMessage() );
	}
});*/

add_action('pmpro_after_checkout', 'set_stripe_default_payment_method', 10, 2);
function set_stripe_default_payment_method($user_id, $order) {
	global $gateway;

	// Only run for Stripe gateway
	if (strtolower($order->Gateway->gateway) !== 'stripe') {
		return;
	}

	error_log('subscription_transaction_id:' . print_r($order->subscription_transaction_id,true));

	try {
		// Get Stripe customer ID
		//$stripe_customer_id = $order->Gateway->customer->id;
		//$payment_method_id = $order->payment_method_id;

		// Initialize Stripe
		if (!class_exists('\Stripe\Stripe')) {
			require_once(PMPRO_DIR . '/includes/lib/Stripe/init.php');
		}

		$stripe_secret_key = get_option('pmpro_stripe_secretkey');
		if (pmpro_getOption('gateway_environment') === 'sandbox') {
			$stripe_secret_key = get_option('pmpro_stripe_secretkey_test');
		}

		$stripe = new StripeClient($stripe_secret_key);

		$subscription = $stripe->subscriptions->retrieve($order->subscription_transaction_id);
		//$customer = $stripe->customers->retrieve($subscription->customer);


		$paymentMethod = $stripe->customers->allPaymentMethods($subscription->customer, ['limit'=> 5]);
		$payment_methodID = strval($paymentMethod[0]->id);

		// Set as default payment method for invoices/subscriptions
		$stripe->customers->update(
			$subscription->customer,
			[
				'invoice_settings' => [
					'default_payment_method' => $payment_methodID
				]
			]
		);

		// Optional: Also set on subscription if this is a recurring membership
		if (!empty($order->subscription_transaction_id)) {
			$stripe->subscriptions->update(
				$order->subscription_transaction_id,
				[
					'default_payment_method' => $payment_methodID
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