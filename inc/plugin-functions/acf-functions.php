<?php
/**
 * ACF Option Pages
 */

if( function_exists('acf_add_options_page') ) {
	acf_add_options_page('Header');
	acf_add_options_page('Footer');
	acf_add_options_page('Popup');
}

function my_save_post( $post_id )
{

	// bail early if not a models post
	if (get_post_type($post_id) !== 'videos') {
		return;
	}

	// bail early if editing in admin
	if (is_admin()) {
		return;
	}

	$url = site_url();

	if (strpos($url,'test') !== false || strpos($url,'staging') !== false ) {
		$mailTo = "matteo@mscwebservices.net";
	} else {
		$mailTo = 'admin@daricbennett.com, daric@daricbennett.com';
	}

	$link = get_permalink($post_id);

	$to = $mailTo;
	$headers = array('Content-Type: text/html; charset=UTF-8');
	$subject = 'A new Q & A Video was submitted';
	$body = 'A new video was submitted to the Video Q & A section! <br><br>To view it click here:<br>' . $link;

	wp_mail( $to, $subject, $body, $headers );

	//httpPost('https://', ' ');
}
add_action('acf/save_post', 'my_save_post', 1);
/* 
function httpPost($url, $params) {

	$fields_string = array();

	foreach($params as $key => $value) {
		$fields_string .= $key . '=' . urlencode($value) . '&';
	}

	//rtrim($fields_string, '&');

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch,CURLOPT_URL, $url);
	//curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	//curl_setopt($ch,CURLOPT_HEADER, false);
	curl_setopt($ch,CURLOPT_POST, count($fields_string));
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

	//execute post
	$result = curl_exec($ch);

	//close connection
	curl_close($ch);
} */

function my_acf_load_field( $field ) {

	global $post;
	$postTitle = $post->post_name;
	$url = get_site_url();

	$field['value'] = $url . "/lessons/#" . $postTitle;
	return $field;

}
add_filter('acf/load_field/name=lesson_page_url', 'my_acf_load_field');

?>