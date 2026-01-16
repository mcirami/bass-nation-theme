<?php
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice;
use Stripe\PaymentMethod;
use Stripe\Stripe;
use Stripe\Subscription;

$env = pmpro_getOption('gateway_environment');
if ($env === 'sandbox') {
	if (defined('STRIPE_TEST_SECRET_KEY') && !getenv('STRIPE_TEST_SECRET_KEY')) {
		putenv('STRIPE_TEST_SECRET_KEY=' . STRIPE_TEST_SECRET_KEY);
		$_ENV['STRIPE_TEST_SECRET_KEY'] = STRIPE_TEST_SECRET_KEY;
	}
} else {
	if (defined('STRIPE_SECRET_KEY') && !getenv('STRIPE_SECRET_KEY')) {
		putenv('STRIPE_SECRET_KEY=' . STRIPE_SECRET_KEY);
		$_ENV['STRIPE_SECRET_KEY'] = STRIPE_SECRET_KEY;
	}
}

/*
	Add this code to your active theme's functions.php or a custom plugin
	to change the PayPal button image on the PMPro checkout page.
*/
function my_pmpro_paypal_button_image($url)
{
	return "https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-large.png";
}
//add_filter('pmpro_paypal_button_image', 'my_pmpro_paypal_button_image');

/**
 * Prevent users from purchasing a new subscription when an active/trialing/past_due subscription exists.
 */
function bass_nation_user_has_open_subscription( $user_id ) {
	$statuses_to_block = array( 'active', 'trialing', 'past_due' );
	$subscriptions = array();

	if ( empty( $user_id ) ) {
		return false;
	}

	if ( function_exists( 'pmpro_getSubscriptions' ) ) {
		$subscriptions = pmpro_getSubscriptions( array( 'user_id' => $user_id ) );
	} elseif ( class_exists( 'PMPro_Subscription' ) ) {
		if ( method_exists( 'PMPro_Subscription', 'get_subscriptions_for_user' ) ) {
			$subscriptions = PMPro_Subscription::get_subscriptions_for_user( $user_id );
		} elseif ( method_exists( 'PMPro_Subscription', 'get_subscriptions' ) ) {
			$subscriptions = PMPro_Subscription::get_subscriptions( array( 'user_id' => $user_id ) );
		}
	}

	if ( empty( $subscriptions ) ) {
		return false;
	}

	foreach ( $subscriptions as $subscription ) {
		if ( ! is_object( $subscription ) ) {
			continue;
		}

		$status = '';
		if ( property_exists( $subscription, 'status' ) ) {
			$status = $subscription->status;
		} elseif ( method_exists( $subscription, 'get_status' ) ) {
			$status = $subscription->get_status();
		}

		if ( $status && in_array( $status, $statuses_to_block, true ) ) {
			return true;
		}
	}

	return false;
}

function bass_nation_block_duplicate_subscriptions( $continue ) {
	if ( ! $continue || ! is_user_logged_in() ) {
		return $continue;
	}

	$user_id = get_current_user_id();
	if ( bass_nation_user_has_open_subscription( $user_id ) ) {
		pmpro_setMessage(
			'You already have an active, trialing, or past due subscription. Please resolve any payment issues or update your existing subscription before starting a new one.',
			'pmpro_error'
		);
		return false;
	}

	return $continue;
}
add_filter( 'pmpro_registration_checks', 'bass_nation_block_duplicate_subscriptions' );


add_filter('pmpro_send_email', function($send, $email){
	if (!empty($email->template) && in_array($email->template, ['cancelled','cancelled_admin'])) {
		return false; // suppress member + admin cancel emails during migration
	}
	return $send;
}, 10, 2);

add_action('pmpro_subscription_payment_completed','db_set_default_pm_from_latest_invoice_on_renewal',10,2);
function db_set_default_pm_from_latest_invoice_on_renewal($morder, $user) {
	set_stripe_default_payment_method($user->ID, $morder);
}
add_action('pmpro_after_checkout', 'set_stripe_default_payment_method', 20, 2);
function set_stripe_default_payment_method($user_id, $morder = null) {
	// Must have an order and gateway = stripe
	if (empty($morder) || empty($morder->Gateway->gateway) || strtolower($morder->Gateway->gateway) !== 'stripe') {
		return;
	}

	error_log('subscription_transaction_id:' . print_r($morder->subscription_transaction_id,true));

	// Load Stripe library via PMPro if not already loaded
	if (!class_exists('\Stripe\Stripe')) {
		if (class_exists('PMProGateway_stripe') && method_exists('PMProGateway_stripe', 'loadStripeLibrary')) {
			PMProGateway_stripe::loadStripeLibrary();
		} else {
			// Fallback include path if needed:
			if (defined('PMPRO_DIR') && file_exists(PMPRO_DIR . '/includes/lib/Stripe/init.php')) {
				require_once PMPRO_DIR . '/includes/lib/Stripe/init.php';
			}
		}
	}
	if (!class_exists('\Stripe\Stripe')) {
		error_log('[DB default PM] Stripe library not loaded');
		return;
	}

	// Get correct secret key from PMPro options
	$env = pmpro_getOption('gateway_environment'); // 'live' or 'sandbox'
	$secret = ($env === 'sandbox')
		? getenv('STRIPE_TEST_SECRET_KEY')
		: getenv('STRIPE_SECRET_KEY');

	if (empty($secret) || !is_string($secret)) {
		error_log('[DB default PM] Missing Stripe secret key for env=' . $env);
		return;
	}

	Stripe::setApiKey($secret);

	// Determine the Stripe customer id
	$stripe_cus = get_user_meta($user_id, 'pmpro_stripe_customerid', true);
	// If we have a subscription id from the order, we can read the customer off it
	$sub_id = !empty($morder->subscription_transaction_id) ? trim($morder->subscription_transaction_id) : '';

	try {
		if (!$stripe_cus && $sub_id) {
			$sub = Subscription::retrieve($sub_id);
			$stripe_cus = $sub && !empty($sub->customer) ? $sub->customer : $stripe_cus;
		}

		if (empty($stripe_cus)) {
			error_log('[DB default PM] No Stripe customer id for user ' . $user_id);
			return;
		}

		// Prefer the PM that actually succeeded on the latest paid invoice
		$invoices = Invoice::all([
			'customer' => $stripe_cus,
			'limit'    => 1,
			'status'   => 'paid',
			'expand'   => ['data.payment_intent.payment_method'],
		]);

		$pm_id = null;
		if (!empty($invoices->data)) {
			$inv = $invoices->data[0];
			if (!empty($inv->payment_intent) && !empty($inv->payment_intent->payment_method)) {
				$pm_id = $inv->payment_intent->payment_method->id;
			}
		}

		// Fallback: first attached card on the customer
		if (!$pm_id) {
			$pms = PaymentMethod::all([
				'customer' => $stripe_cus,
				'type'     => 'card',
				'limit'    => 1,
			]);
			if (!empty($pms->data)) {
				$pm_id = $pms->data[0]->id;
			}
		}

		if (!$pm_id) {
			error_log('[DB default PM] No payment method found for customer ' . $stripe_cus);
			return;
		}

		// Set as customer default for future invoices
		Customer::update($stripe_cus, [
			'invoice_settings' => ['default_payment_method' => $pm_id],
		]);

		// Also set on subscription if we have one
		if ($sub_id) {
			Subscription::update($sub_id, [
				'default_payment_method' => $pm_id,
			]);
		}

		error_log('[DB default PM] Set default PM ' . $pm_id . ' for customer ' . $stripe_cus . ' (user ' . $user_id . ')');

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