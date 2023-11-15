<?php
function autoblank($text) {
	$myurl = 'http://your-domain.com';
	$external = str_replace('href=', 'target="_blank" href=', $text);
	$external = str_replace('target="_blank" href="'.$myurl, 'href="'.$myurl, $external);
	$external = str_replace('target="_blank" href="#', 'href="#', $external);
	$external = str_replace('target = "_blank">', '>', $external);
	return $external;
}
add_filter('the_content', 'autoblank');
add_filter('bbp_get_topic_content', 'autoblank',255);
add_filter('bbp_get_reply_content', 'autoblank',255);


function subscribe_all() {

	$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

	if (isset($_POST['subscribeall'])) {

		global $current_user;
		$user = $current_user->ID;

		$forumSubs = array();

		global $wpdb;

		$forumResults = $wpdb->get_results("SELECT meta_value FROM a02_usermeta WHERE user_id =" . $user . " AND meta_key = 'a02__bbp_forum_subscriptions'");

		if ($forumResults != null) {
			foreach ($forumResults[0] as $forumResult) {
				$forumSubs = explode(',', $forumResult);
			}
		}

		while (bbp_forums()) : bbp_the_forum();

			$forumID = bbp_get_forum_id();
			$value = false;

			$forumID = strval($forumID);

			if ($forumResults != null) {
				foreach ($forumSubs as $forumSub) {
					if ($forumSub == $forumID) {
						$value = true;
						break;
					}
				}
			}

			if ($value == false) {

				bbp_add_user_forum_subscription($user, $forumID);
			}

		endwhile;

	}

	echo '<form class="subscribe_all" method="post" action="'. $actual_link . '?subscribeall=subscribed">
        <input type="submit" name="subscribeall" value="Subscribe To All">
    </form>';

}

function add_media_button( $args ) {
	$args['media_buttons'] = true;

	return $args;
}
add_filter('bbp_after_get_the_content_parse_args', 'add_media_button');

function give_permissions( $allcaps, $cap, $args ) {
	$allcaps['upload_files'] = true;
	return $allcaps;
}
add_filter( 'user_has_cap', 'give_permissions', 0, 3 );


