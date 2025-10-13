<?php
// Detect if this visit is from your win-back email
function db_is_winback_visit(): bool {
	if (!empty($_GET['pmpro_discount_code']) && strtoupper($_GET['pmpro_discount_code']) === 'BNATION25') return true;
	if (!empty($_COOKIE['db_campaign']) && $_COOKIE['db_campaign'] === 'winback') return true;
	// Optional: also honor your UTM campaign
	if (!empty($_GET['utm_campaign']) && stripos($_GET['utm_campaign'], 'winback') !== false) return true;
	return false;
}

// Build a checkout URL for a PMPro level, carrying discount/UTMs through
function db_checkout_url_with_passthrough(int $level_id, array $extra = []): string {
	// Base checkout URL (adjust if your slug differs)
	$base = site_url('/membership-account/membership-checkout/');
	// Gather incoming params you want to preserve
	$keep = [];
	foreach (['pmpro_discount_code', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content'] as $k) {
		if (isset($_GET[$k]) && $_GET[$k] !== '') $keep[$k] = sanitize_text_field($_GET[$k]);
	}
	// If we’re in winback but code is missing (e.g., cookie-only), force the code
	if (db_is_winback_visit() && empty($keep['pmpro_discount_code'])) {
		$keep['pmpro_discount_code'] = 'BNATION25';
	}
	// Always include the level
	$args = array_merge(['pmpro_level' => $level_id], $keep, $extra);
	return esc_url(add_query_arg($args, $base));
}
