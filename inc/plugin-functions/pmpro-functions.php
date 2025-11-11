<?php
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice;
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

add_action('pmpro_after_checkout', 'db_set_default_pm_from_latest_invoice', 20, 2);
add_action('pmpro_subscription_payment_completed', 'db_set_default_pm_from_latest_invoice_on_renewal', 10, 2);

function db_set_default_pm_from_latest_invoice($user_id, $morder): void {
	db__stripe_set_default_pm_for_user($user_id, $morder);
}
function db_set_default_pm_from_latest_invoice_on_renewal($morder, $user): void {
	db__stripe_set_default_pm_for_user($user->ID, $morder);
}

function db__stripe_set_default_pm_for_user($user_id, $morder = null): void {
	$cus = get_user_meta($user_id, 'pmpro_stripe_customerid', true);
	if (!$cus) return;

	if (!class_exists('\\Stripe\\Stripe')) {
		// Load Stripe via PMPro gateway if available
		if (class_exists('PMProGateway_stripe') && method_exists('PMProGateway_stripe', 'loadStripeLibrary')) {
			PMProGateway_stripe::loadStripeLibrary();
		}
	}
	if (!class_exists('\\Stripe\\Stripe')) return;

	$secret = pmpro_getOption('stripe_secretkey');
	if (pmpro_getOption('gateway_environment') === 'sandbox') {
		$secret = get_option('pmpro_stripe_secretkey_test');
	}
	if (!$secret) return;
	Stripe::setApiKey($secret);

	try {
		// Find the latest PAID invoice for this customer
		$invoices = Invoice::all([
			'customer' => $cus,
			'limit'    => 1,
			'status'   => 'paid',
			'expand'   => ['data.payment_intent.payment_method'],
		]);
		if (empty($invoices->data)) return;

		$inv = $invoices->data[0];
		$pm  = $inv->payment_intent && $inv->payment_intent->payment_method
			? $inv->payment_intent->payment_method->id
			: null;

		if ($pm) {
			// Set as customer's default payment method for future invoices
			Customer::update($cus, [
				'invoice_settings' => ['default_payment_method' => $pm],
			]);
		}
	} catch (\Exception $e) {
		error_log('[DB default PM] ' . $e->getMessage());
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