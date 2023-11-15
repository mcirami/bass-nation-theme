<?php
/*
	Add this code to your active theme's functions.php or a custom plugin
	to change the PayPal button image on the PMPro checkout page.
*/
function my_pmpro_paypal_button_image($url)
{
	return "https://www.paypalobjects.com/webstatic/en_US/i/buttons/checkout-logo-large.png";
}
add_filter('pmpro_paypal_button_image', 'my_pmpro_paypal_button_image');

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