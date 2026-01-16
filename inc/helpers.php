<?php

/* add image thumbnail sizes*/
if ( function_exists( 'add_image_size' ) ) {
	add_image_size('avatar-size', 300, 300, true);
	add_image_size( 'video-thumb', 640, 360, true );
}

function blockusers_init() {
	if ( is_admin() && ! current_user_can( 'administrator' ) &&
	     ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		wp_redirect( home_url() );
		exit;
	}
}

add_action( 'init', 'blockusers_init' );

/**
 * Determine if the Simple Favorites plugin is available and fully initialized.
 *
 * The plugin loads its translations at `init`, so calling its helpers before
 * then triggers `_load_textdomain_just_in_time()` notices. This guard lets us
 * defer any interaction until WordPress (and the plugin) have finished
 * bootstrapping.
 *
 * @return bool
 */
function bass_nation_favorites_ready(): bool {
	return function_exists( 'get_user_favorites' ) && did_action( 'init' );
}


/**
 * Prevent the Favorites plugin from loading translations before WordPress is ready.
 *
 * WordPress 6.7+ triggers a notice when `_load_textdomain_just_in_time()` attempts to load
 * a textdomain before the `init` hook. Some Favorites integrations execute early and rely on
 * just-in-time loading, so we intercept those requests and defer them until `init` fires.
 *
 * @param bool   $override Whether to override the textdomain loading logic.
 * @param string $domain   Textdomain being loaded.
 * @param string $mofile   Path to the MO file WordPress is trying to load.
 *
 * @return bool True when the load should be short-circuited, original override otherwise.
 */
function bass_nation_delay_favorites_textdomain( $override, $domain, $mofile ) {
	if ( 'favorites' !== $domain || did_action( 'init' ) || doing_action( 'init' ) ) {
		return $override;
	}

	static $queued = array();

	if ( isset( $queued[ $mofile ] ) ) {
		return true;
	}

	$queued[ $mofile ] = true;

	add_action(
		'init',
		static function () use ( $domain, $mofile ) {
			// The file path passed by `_load_textdomain_just_in_time()` may not exist when a
			// translation hasn't been generated yet. Only attempt to load it when present.
			if ( file_exists( $mofile ) ) {
				load_textdomain( $domain, $mofile );
			} else {
				load_plugin_textdomain( $domain );
			}
		}
	);

	return true;
}
add_filter( 'pre_load_textdomain', 'bass_nation_delay_favorites_textdomain', 10, 3 );

add_filter( 'the_content', 'make_clickable');


/**
 * Use * for origin
 */

add_action( 'rest_api_init', function() {

	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
	add_filter( 'rest_pre_serve_request', function( $value ) {
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
		header('content-type: application/json; charset=utf-8');
		header('Access-Control-Allow-Headers: Authorization, Access-Control-Allow-Headers, Origin, Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers, X-WP-Nonce, cache-control, postman-token');
		return $value;

	});
}, 15 );


function filter_comment_form_submit_button( $submit_button, $args ) {
	// make filter magic happen here...
	$submit_before = '<div class="comment_submit">';
	$submit_after = '<span class="loading_gif"></span></div>';
	return $submit_before . $submit_button . $submit_after;
};
// add the filter
add_filter( 'comment_form_submit_button', 'filter_comment_form_submit_button', 10, 2 );

function my_comment_form_edits($edit_fields) {

	if (get_post_type() == "videos") {
		$title_reply = 'REPLY TO THIS THREAD';
		$label_submit = 'Post Reply';
		$title_reply_after = '<span>(you may embed a YouTube or Vimeo video link in your reply)</span>';
	} else if (get_post_type() == "live-streams"){
		$title_reply = 'CHAT LIVE';
		$label_submit = 'Submit';
		$title_reply_after = '';
	} else {
		$title_reply = 'Questions? Comments...get in touch!';
		$label_submit = 'Post Comment';
		$title_reply_after =  '<br><span>(you may embed a YouTube or Vimeo video link in your reply)</span>';
	}

	$edit_fields = array(
		'title_reply' => $title_reply,
		'title_reply_after' => $title_reply_after,
		'label_submit' => $label_submit
	);

	return $edit_fields;
}
add_filter('comment_form_defaults', 'my_comment_form_edits', 10, 2);


function send_post_author_notification($comment_ID, $comment_approved, $commentdata) {

	$postID      = $commentdata['comment_post_ID'];
	$commentAuthorEmail = $commentdata['comment_author_email'];
	$postAuthorID    = get_post_field( 'post_author', $postID );
	$postAuthorEmail = get_the_author_meta( 'user_email', $postAuthorID );
	$postURL     = preg_replace( '/%..|[^a-zA-Z0-9-]/', '', get_post_permalink( $postID ));
	//$commentContent = $commentdata['comment_content'];

	if( str_contains( $postURL, 'video-q-and-a' ) && $commentAuthorEmail !== $postAuthorEmail) {

		$messageData = "
			<div style='background: #000; padding: 20px 20px 100px 20px; text-align: center;'>
				<img class=\"alignnone size-medium wp-image-114\" src=\"https://staging.daricbennett.com/wp-content/uploads/2016/09/logo-300x69.png\" alt=\"\" width=\"300\" height=\"69\" />
				<p style=\"color: #fff; text-align: left;\">You can read and reply to the comment here: </p><br><br>
				<a style=\"color: #ddb72e; display: block; text-align: left;\" href=\"$postURL\">$postURL</a>
			
			</div>
		";

		$to      = $postAuthorEmail;
		$subject = "Someone commented on your Video Q & A post";
		$message = $messageData;
		$headers = "From: admin@daricbennett.com";

		if ( wp_mail( $to, $subject, $message, $headers ) ) {
			return json_encode( array( 'status' => 'success', 'message' => 'Comment on author post notification sent' ) );
			//exit;
		} else {
			echo json_encode( error_get_last() );
			die();
		}
	}
	return 1;
}
add_action('comment_post', 'send_post_author_notification', 10, 3);


function custom_post_comment_action($location, $commentData) {
	$hash = '';
	if ( isset( $_COOKIE['clickHash'] ) ) {
		$hash = $_COOKIE['clickHash'];
	}

	$post_id = $commentData->comment_post_ID;

	if(get_post_type($post_id) == "courses" || get_post_type($post_id) == "lessons") {
		$location = $_SERVER['HTTP_REFERER'] . $hash;
	}

	return $location;
}
add_filter('comment_post_redirect', 'custom_post_comment_action', 10, 2);


function devplus_wpquery_where( $where ){
	global $current_user;

	if( is_user_logged_in() ){
		// logged in user, but are we viewing the library?
		if( isset( $_POST['action'] ) && ( $_POST['action'] == 'query-attachments' ) ){
			// here you can add some extra logic if you'd want to.
			$where .= ' AND post_author='.$current_user->data->ID;
		}
	}

	return $where;
}

add_filter( 'posts_where', 'devplus_wpquery_where' );

add_action('wp_ajax_nopriv_get_lesson_comments', 'get_lesson_comments');
add_action('wp_ajax_get_lesson_comments', 'get_lesson_comments');
function get_lesson_comments() {

	$postID = $_POST['id'];
	$comments = get_comments(array('post_id' => $postID));

	wp_list_comments(array(
		'avatar_size' => 100,
		'style'       => 'ol',
		'short_ping'  => true,
		'callback' => 'bass_nation_comment' ), $comments);
	$args = array(
		'id_form'           => 'commentform',
		'class_form'        => 'comment-form',
		'id_submit'         => 'submit',
		'class_submit'      => 'submit',
		'name_submit'       => 'submit',
		'label_submit'      => __( 'Post Comment' ),
		'format'            => 'xhtml');
	comment_form( $args, $postID );

	wp_die();

	//echo $comments . comment_form( '', $postID );
}

add_action('wp_ajax_nopriv_get_comment_form', 'get_comment_form');
add_action('wp_ajax_get_comment_form', 'get_comment_form');
function get_comment_form() {

	$postID = $_POST['id'];
	$args = array(
		'id_form'           => 'commentform',
		'class_form'      => 'comment-form',
		'id_submit'         => 'submit',
		'class_submit'      => 'submit',
		'name_submit'       => 'submit',
		'title_reply'       => __( 'Leave a Reply' ),
		'label_submit'      => __( 'Post Comment' ),
		'format'            => 'xhtml');
	comment_form($args , $postID);

	wp_die();
}

// Remove comment-reply.min.js from footer
function crunchify_clean_header_hook(){
	wp_deregister_script( 'comment-reply' );
}
add_action('init','crunchify_clean_header_hook');


if( !function_exists('sc_after_login_redirection') ):
	function sc_after_login_redirection($redirect_to, $request, $user) {
		global $wpdb;
		$result = $wpdb->get_results( "SELECT status FROM a02_pmpro_subscriptions WHERE user_id = $user->ID " );
		if ( $wpdb->last_error ) {
			//echo 'wpdb error: ' . $wpdb->last_error;
			echo 'Sorry there has been an error, please try again';
		  }
		if ( isset( $user->roles ) && is_array( $user->roles ) ) {
			if ( ($result[0]->status == "cancelled" || empty($result)) && !in_array('administrator',  $user->roles  )) :
				$redirect_to  =  site_url() . '/membership-account/membership-levels';
			elseif ( isset($_COOKIE['login_redirect']) && !str_contains($_COOKIE['login_redirect'], 'membership-levels') ):
				$redirect_to  = $_COOKIE['login_redirect'];
				unset( $_COOKIE['login_redirect'] );
				else :
					$redirect_to  = site_url() . '/member-home/';
			endif;

			if (session_status() === PHP_SESSION_NONE) {
				session_write_close();
			}
		}

		return $redirect_to;
	}
	add_filter('login_redirect', 'sc_after_login_redirection', 10, 3);
endif;



function remove_admin_bar() {
	if (!current_user_can('administrator') && !is_admin()) {
		show_admin_bar(false);
	}
}
add_action('after_setup_theme', 'remove_admin_bar');


add_filter( 'avatar_defaults', 'wpb_new_gravatar' );
function wpb_new_gravatar ($avatar_defaults) {
	$myavatar                     = 'https://staging.daricbennett.com/wp-content/uploads/2016/11/nophoto_1.jpg';
	$avatar_defaults[ $myavatar ] = "Default Gravatar";

	return $avatar_defaults;
}

function getVideoEmbedCode($videoLink) {

	$embedCode = null;
	if ( str_contains( $videoLink, "embed" ) ) {
		$strEmbed = explode("embed/", $videoLink);
		if ( str_contains( $strEmbed[1], "?" ) ) {
			$str = explode("?", $strEmbed[1])[0];
		} else {
			$str = $strEmbed[1];
		}
		$embedCode = preg_replace('/\s+/', '', $str);
	} elseif ( str_contains( $videoLink, "v=" ) && str_contains( $videoLink, "youtube" ) ) {
		$strOne = explode("v=", $videoLink);
		if ( str_contains( $strOne[1], "?" ) ) {
			$str = explode("?", $strOne[1])[0];
		} elseif (str_contains( $strOne[1], "&") ) {
			$str = explode("&", $strOne[1])[0];
		} else {
			$str = $strOne[1];
		}
		$embedCode = preg_replace('/\s+/', '', $str);
	} elseif ( str_contains( $videoLink, "youtu.be" ) ) {
		$str = explode(".be/", $videoLink)[1];
		if ( str_contains( $str, "?" ) ) {
			$str = explode("?", $str)[0];
		}
		$embedCode = preg_replace('/\s+/', '', $str);
	} elseif ( str_contains( $videoLink, "vimeo" ) && !str_contains($videoLink, "player.vimeo") ) {
		$str       = explode( "vimeo.com/", $videoLink )[1];
		$embedCode = preg_replace( '/\s+/', '', $str);
	} elseif (str_contains( $videoLink, "player.vimeo" )) {
		$str       = explode( "player.vimeo.com/", $videoLink )[1];
		if (str_contains($str, "video")) {
			$str = explode( "video/", $str )[1];
		}
		$embedCode = preg_replace( '/\s+/', '', $str);
	}

	return $embedCode;
}
/*
add_action( 'init', 'verify_user_code' );
function verify_user_code(){
	if( isset($_GET['act']) && isset($_GET['id']) ) {
		$actCode = base64_decode($_GET['act']);
		$id = base64_decode($_GET['id']);
		$code = get_user_meta($id, 'activation_code', true);
		// verify whether the code given is the same as ours
		if($code == $actCode){
			// update the user meta
			update_user_meta($id, 'is_activated', 1);
			update_user_meta($id, 'activation_code', "");
			$user = get_user_by( 'id', $id  );
			wp_set_auth_cookie($user->ID, true);
			wp_set_current_user($user->ID, $user->user_login);
			do_action('wp_login', $user->user_login, wp_get_current_user());

			if (function_exists('pmpro_getMembershipLevelForUser')) {
				$membership_level = pmpro_getMembershipLevelForUser($user->ID);
				$has_membership = !empty($membership_level);
			} elseif (function_exists('pmpro_hasMembershipLevel')) {
				$has_membership = pmpro_hasMembershipLevel(null, $user->ID);
			} else {
				$has_membership = false;
			}
			$redirect_path = $has_membership ? '/member-home' : '/membership-account/membership-levels';
			wp_redirect( get_site_url() . $redirect_path );
			exit();
		} else {
			wp_redirect( get_site_url() );
		}
	}
}

function my_redirect()
{
	$approved = check_user_verified();

	if( ( is_page( 'membership-checkout' ) || is_page( 'membership-levels' ) ) && (! is_user_logged_in() || ! $approved ))
	{
		if (! is_user_logged_in()) {
			wp_redirect( wp_login_url() );
		} else {
			wp_redirect( get_site_url() . '/verify-account');
		}
		die;
	}
}
add_action( 'template_redirect', 'my_redirect' );*/

function check_user_verified() {
	$userID = get_current_user_id();

	$status = get_user_meta($userID, 'is_activated', 1);

	if ($status == 0) {
		return false;
	}

	return true;
}

function postToMailChimp($email, $tag) {

	$emailHash = md5($email);
	// Mailchimp API endpoint for adding/updating a list member
    $api_url = "https://us14.api.mailchimp.com/3.0/lists/08854914fe/members/$emailHash/tags";
    
	$body = array(
		'tags' => [['name' => $tag, 'status' => 'active']]
    );

	$args = array(
        'headers' => array(
            // Mailchimp API requires basic auth using any string as user and the API key as password
            'Authorization' => 'Basic ' . base64_encode( 'anystring:' . MAILCHIMP_API_KEY ),
            'Content-Type'  => 'application/json',
        ),
        'body'    => json_encode($body),
        'timeout' => 20, // seconds
    );

	 // Send POST request
	 $response = wp_remote_post($api_url, $args);

	 if (is_wp_error($response)) {
        // There was an error making the request.
		print_r('Mailchimp POST request failed: ' . $response->get_error_message());
		exit;
        error_log('Mailchimp POST request failed: ' . $response->get_error_message());
        return false;
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code == 204) {
            // Success. In Mailchimp's case, 200 usually indicates it was successful.
            return true;
        } else {
            // Request made it to the endpoint but had a non-200 response
            $body = wp_remote_retrieve_body($response);
			print_r("Mailchimp returned status code $status_code. Response body: $body");
			exit;
            error_log("Mailchimp returned status code $status_code. Response body: $body");
            return false;
        }
    }
    
}