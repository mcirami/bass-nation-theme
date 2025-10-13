<?php
// === Config ===
if (!defined('DB_MC_LIST_ID'))  define('DB_MC_LIST_ID', DB_MC_LIST_ID);   // your audience ID
if (!defined('DB_MC_API_KEY'))  define('DB_MC_API_KEY', MAILCHIMP_API_KEY); // or store in wp-config.php

// Derive datacenter (e.g. "us14") from API key suffix "xxxx-us14"
function db_mc_dc() {
	if (!DB_MC_API_KEY) return 'us1';
	$parts = explode('-', DB_MC_API_KEY);
	return $parts[1] ?? 'us1';
}

// Subscriber hash = MD5 of lowercase, trimmed email (Mailchimp requirement)
function db_mc_hash($email) {
	return md5(strtolower(trim($email)));
}

// Minimal Mailchimp request wrapper
function db_mc_request($method, $path, $body = null) {
	$url = sprintf('https://%s.api.mailchimp.com/3.0/%s', db_mc_dc(), ltrim($path, '/'));
	$args = [
		'method'  => strtoupper($method),
		'headers' => [
			'Authorization' => 'Basic ' . base64_encode('anystring:' . DB_MC_API_KEY),
			'Content-Type'  => 'application/json',
		],
		'timeout' => 15,
	];
	if (!is_null($body)) $args['body'] = json_encode($body);
	$res = wp_remote_request($url, $args);

	if (is_wp_error($res)) {
		error_log('[MC] HTTP error: ' . $res->get_error_message() . " ($method $path)");
		return [null, 0, $res->get_error_message()];
	}
	$code = wp_remote_retrieve_response_code($res);
	$json = json_decode(wp_remote_retrieve_body($res), true);
	return [$json, $code, null];
}

// Ensure the contact exists (upsert) and is subscribed
function db_mc_upsert_member($email, $merge_fields = []) {
	$hash = db_mc_hash($email);
	$body = array_merge([
		'email_address' => $email,
		'status_if_new' => 'subscribed',
		'status'        => 'subscribed',
	], $merge_fields ? ['merge_fields' => $merge_fields] : []);
	list($json, $code, $err) = db_mc_request('PUT', "lists/".DB_MC_LIST_ID."/members/$hash", $body);
	if ($code >= 200 && $code < 300) return true;
	error_log("[MC] upsert failed ($code): " . json_encode($json ?: $err));
	return false;
}

// Add/remove tags in one call
function db_mc_update_tags($email, array $add = [], array $remove = []) {
	$hash = db_mc_hash($email);
	$tags = [];
	foreach ($add as $t)    $tags[] = ['name' => $t, 'status' => 'active'];
	foreach ($remove as $t) $tags[] = ['name' => $t, 'status' => 'inactive'];

	list($_, $code, $err) = db_mc_request('POST', "lists/".DB_MC_LIST_ID."/members/$hash/tags", ['tags' => $tags]);

	// This endpoint usually returns 204 on success.
	if ($code === 204) return true;

	error_log("[MC] tag update failed ($code): ".print_r($err, true));
	return false;
}

// Add "membership" tag and remove "old members" after successful checkout
add_action('pmpro_after_checkout', function($user_id, $morder) {
	if (!$user_id) return;
	$user = get_userdata($user_id);
	if (!$user) return;

	// Ensure the contact exists, then tag.
	db_mc_upsert_member($user->user_email);
	db_mc_update_tags($user->user_email, ['purchased'], ['old members']);
}, 10, 2);

// Also handle changes to membership level (upgrades/downgrades/reactivations/cancellations)
add_action('pmpro_after_change_membership_level', function($level_id, $user_id /*, $cancel_level = 0 */) {
	$user = get_userdata($user_id);
	if (!$user) return;

	if ((int)$level_id > 0) {
		// Active membership now
		db_mc_upsert_member($user->user_email);
		db_mc_update_tags($user->user_email, ['purchased'], ['old members']);
	} else {
		// No active level anymore (optional: re-tag as old member)
		db_mc_update_tags($user->user_email, ['old members'], ['purchased']);
	}
}, 10, 2);

