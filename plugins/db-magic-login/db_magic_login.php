<?php

namespace db_magic_login;
use WP_Error;

/**
 * Plugin Name: DB Magic Login
 * Description: One-time, expiring magic links for auto-login + redirect (with an on-click generator).
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) exit;

class DB_Magic_Login {
	// CHANGE THIS to a long random secret (40+ chars). Keep it private.
	const SECRET = 'KdPGztvHThJyQqAHwo3t2IgR7wQR5zCXsZ8oZiYlipZ70i8';
	// Token time-to-live in seconds (e.g., 1 hour)
	const TOKEN_TTL = 30; //3600;

	public function __construct() {
		add_action('init', [$this, 'add_rewrite']);
		add_action('template_redirect', [$this, 'maybe_handle_magic_login']);
	}

	/** Register /magic-login route */
	public function add_rewrite() {
		add_rewrite_rule('^magic-login/?', 'index.php?db_magic_login=1', 'top');
		add_rewrite_tag('%db_magic_login%', '1');
	}

	/** Build a signed, one-time, short-lived token */
	public static function generate_token($user_id, $redirect) {
		$jti = wp_generate_password(32, false);
		$exp = time() + self::TOKEN_TTL;
		$payload = json_encode([
			'uid' => (int) $user_id,
			'exp' => $exp,
			'jti' => $jti,
			'r'   => $redirect, // full redirect URL (relative OK)
		], JSON_UNESCAPED_SLASHES);

		$sig = hash_hmac('sha256', $payload, self::SECRET);

		// Store jti so we can invalidate after first use (one-time)
		add_user_meta($user_id, '_db_magic_jti', ['jti' => $jti, 'exp' => $exp], false);

		// base64url(payload) . '.' . sig
		$b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
		return $b64 . '.' . $sig;
	}

	/** Verify token (signature, expiry, one-time) */
	public static function validate_token($token, $user_email = null) {
		$parts = explode('.', $token);
		if (count($parts) !== 2) return new WP_Error('bad_token', 'Malformed token');

		[$b64, $sig] = $parts;
		$payload = base64_decode(strtr($b64, '-_', '+/'));
		if (!$payload) return new WP_Error('bad_token', 'Bad encoding');

		$calc = hash_hmac('sha256', $payload, self::SECRET);
		if (!hash_equals($calc, $sig)) return new WP_Error('bad_sig', 'Signature mismatch');

		$data = json_decode($payload, true);
		if (!$data || empty($data['uid']) || empty($data['exp']) || empty($data['jti'])) {
			return new WP_Error('bad_payload', 'Invalid payload');
		}
		if (time() > (int) $data['exp']) return new WP_Error('expired', 'Token expired');

		// One-time check
		$usermeta = get_user_meta((int) $data['uid'], '_db_magic_jti');
		$found = false;
		foreach ($usermeta as $row) {
			if (!empty($row['jti']) && $row['jti'] === $data['jti']) { $found = true; break; }
		}
		if (!$found) return new WP_Error('reused', 'Already used or invalid');

		// Optional: match the email if it was passed in the link
		if ($user_email) {
			$u = get_userdata((int) $data['uid']);
			if (!$u || strcasecmp($u->user_email, $user_email) !== 0) {
				return new WP_Error('mismatch', 'Email mismatch');
			}
		}
		return $data;
	}

	/** Consume token, login, redirect */
	public function maybe_handle_magic_login() {
		if (!get_query_var('db_magic_login')) return;

		$token = isset($_GET['t']) ? sanitize_text_field($_GET['t']) : '';
		$email = isset($_GET['e']) ? sanitize_email($_GET['e']) : null;

		$valid = self::validate_token($token, $email);
		if (is_wp_error($valid)) {
			wp_die('This magic link is invalid or expired. Please request a new one.');
		}

		$user_id = (int) $valid['uid'];

		// Invalidate token (delete the matching jti row)
		$usermeta = get_user_meta($user_id, '_db_magic_jti');
		foreach ($usermeta as $row) {
			if (!empty($row['jti']) && $row['jti'] === $valid['jti']) {
				delete_user_meta($user_id, '_db_magic_jti', $row);
				break;
			}
		}

		// Login and redirect
		wp_set_current_user($user_id);
		wp_set_auth_cookie($user_id, true); // remember me = true (optional)
		do_action('wp_login', get_userdata($user_id)->user_login, get_userdata($user_id));

		$redirect = !empty($valid['r']) ? esc_url_raw($valid['r']) : home_url('/');
		wp_safe_redirect($redirect);
		exit;
	}
}
new DB_Magic_Login();

/**
 * /get-magic endpoint: user clicks email CTA with e=EMAIL and r=REDIRECT
 * If user exists -> mint token and bounce to /magic-login?t=...&e=...
 * If user doesn't exist -> send to registration with email prefilled, carrying redirect.
 */
add_action('init', function () {
	add_rewrite_rule('^get-magic/?', 'index.php?db_get_magic=1', 'top');
	add_rewrite_tag('%db_get_magic%', '1');
});

add_action('template_redirect', function () {
	if (!get_query_var('db_get_magic')) return;

	$email = isset($_GET['e']) ? sanitize_email($_GET['e']) : '';
	$redir = isset($_GET['r']) ? esc_url_raw($_GET['r']) : site_url('/');

	if (!$email) wp_die('Missing email.');

	$user = get_user_by('email', $email);

	// OPTIONAL: if you require email verification, check your flag here
	// $is_verified = (bool) get_user_meta($user->ID, 'is_activated', true);

	if ($user) {
		// User exists -> generate one-time token and bounce to magic-login
		$token = DB_Magic_Login::generate_token($user->ID, $redir);
		$url   = site_url('/magic-login?t=' . urlencode($token) . '&e=' . rawurlencode($email));
		setcookie('db_campaign', 'winback', time()+3600, '/', COOKIE_DOMAIN, is_ssl(), true);
		setcookie('db_dc', 'BNATION25', time()+3600, '/', COOKIE_DOMAIN, is_ssl(), true);
		wp_safe_redirect($url, 302); exit;
	} else {
		// No account yet -> send to your registration page (set this path if different)
		$registration_url = site_url('/register');
		$reg = add_query_arg([
			'email'    => rawurlencode($email),
			'redirect' => rawurlencode($redir),
		], $registration_url);
		wp_safe_redirect($reg, 302); exit;
	}
});
