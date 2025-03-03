<?php
function hash_shortcode() {
	if ( isset( $_COOKIE['clickHash'] ) ) {
		$hash = $_COOKIE['clickHash'];
	}

	if ( isset( $_COOKIE['clickSlug'] ) ) {
		$clickSlug = $_COOKIE['clickSlug'];
	}

	if (str_contains($hash, "course")) {
		$slug = "courses/" . $clickSlug ;
	} else {
		$slug = "lessons";
	}

	return get_site_url() . "/" . $slug . "/" . $hash;
}
add_shortcode( 'lessonhash', 'hash_shortcode' );

function pmpro_expiration_date_shortcode( $atts ) {
	//make sure PMPro is active
	if(!function_exists('pmpro_getMembershipLevelForUser')) {
		return 0;
	}

	//get attributes
	$a = shortcode_atts( array(
		'user' => '',
	), $atts );

	//find user
	if(!empty($a['user']) && is_numeric($a['user'])) {
		$user_id = $a['user'];
	} elseif(!empty($a['user']) && strpos($a['user'], '@') !== false) {
		$user = get_user_by('email', $a['user']);
		$user_id = $user->ID;
	} elseif(!empty($a['user'])) {
		$user = get_user_by('login', $a['user']);
		$user_id = $user->ID;
	} else {
		$user_id = false;
	}

	//no user ID? bail
	if(!isset($user_id)) {
		return 0;
	}

	//get the user's level
	$level = pmpro_getMembershipLevelForUser($user_id);

	if(!empty($level) && !empty($level->enddate))
		$content = date(get_option('date_format'), $level->enddate);
	else
		$content = "---";

	return $content;
}
add_shortcode('pmpro_expiration_date', 'pmpro_expiration_date_shortcode');

?>