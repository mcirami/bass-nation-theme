<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package Bass_Nation
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function bass_nation_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}

	if (isset($_COOKIE["db_dark_mode"]) && $_COOKIE["db_dark_mode"] == "dark") {
		$classes[] = 'db-dark-mode-active';
	}

	return $classes;
}
add_filter( 'body_class', 'bass_nation_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function bass_nation_pingback_header() {
	if ( is_singular() && pings_open() ) {
		printf( '<link rel="pingback" href="%s">', esc_url( get_bloginfo( 'pingback_url' ) ) );
	}
}
add_action( 'wp_head', 'bass_nation_pingback_header' );

// Send all "Lost your password?" links to /password instead of the default.
add_filter('lostpassword_url', function ($lostpassword_url, $redirect) {
	return home_url('/reset-password/');
}, 10, 2);

add_action('template_redirect', function () {
	// Adjust this if your login page has a different slug.
	if (is_page('login') && isset($_GET['action'])) {
		$action = $_GET['action'];

		// Only redirect the *request* screens.
		// Do NOT redirect the actual reset form from the email (action=rp with key/login).
		$is_request_screen = in_array($action, ['lostpassword', 'reset_pass', 'retrievepassword'], true);
		$has_reset_key     = isset($_GET['key']) || isset($_GET['login']) || $action === 'rp';

		if ($is_request_screen && !$has_reset_key) {
			wp_safe_redirect(home_url('/reset-password/'));
			exit;
		}
	}
});