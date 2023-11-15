<?php
function hash_shortcode() {
	if ( isset( $_COOKIE['clickHash'] ) ) {
		$hash = $_COOKIE['clickHash'];
	}

	$hostUrl = get_site_url();
	$hostUrl = get_site_url();

	$url = $hostUrl . "/lessons/" . $hash;

	return $url;
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


/*
*register form by redpishi.com
*[register role="subscriber"] role: shop_manager | customer | subscriber | contributor | author | editor | administrator
*/
function red_registration_form($atts) {
	$atts = shortcode_atts( array(
		'role' => 'subscriber',
	), $atts, 'register' );

	$role_number = $atts["role"];
	if ($role_number == "shop_manager" ) { $reg_form_role = (int) filter_var(AUTH_KEY, FILTER_SANITIZE_NUMBER_INT); }  elseif ($role_number == "customer" ) { $reg_form_role = (int) filter_var(SECURE_AUTH_KEY, FILTER_SANITIZE_NUMBER_INT); } elseif ($role_number == "contributor" ) { $reg_form_role = (int) filter_var(NONCE_KEY, FILTER_SANITIZE_NUMBER_INT); } elseif ($role_number == "author" ) { $reg_form_role = (int) filter_var(AUTH_SALT, FILTER_SANITIZE_NUMBER_INT); } elseif ($role_number == "editor" ) { $reg_form_role = (int) filter_var(SECURE_AUTH_SALT, FILTER_SANITIZE_NUMBER_INT); }   elseif ($role_number == "administrator" ) { $reg_form_role = (int) filter_var(LOGGED_IN_SALT, FILTER_SANITIZE_NUMBER_INT); } else { $reg_form_role = 1001; }

	if(!is_user_logged_in()) {
		$registration_enabled = get_option('users_can_register');
		if($registration_enabled) {
			$output = red_registration_fields($reg_form_role);
		} else {
			$output = __('<p>User registration is not enabled</p>');
		}
		return $output;
	}  $output = __('<p>You already have an account on this site, so there is no need to register again.</p>');
	return $output;
}
add_shortcode('register', 'red_registration_form');

function red_registration_fields($reg_form_role) {	?>
	<?php
	ob_start();
	?>
	<form id="red_registration_form" class="red_form" action="" method="POST">
		<?php red_register_messages();	 ?>
		<p>
			<label for="red_user_login"><?php _e('Username'); ?></label>
			<input name="red_user_login" id="red_user_login" class="red_input" placeholder="Username" type="text" required/>
		</p>
		<p>
			<label for="red_user_email"><?php _e('Email'); ?></label>
			<input name="red_user_email" id="red_user_email" class="red_input" placeholder="Email" type="email" required/>
		</p>
		<p>
			<label for="password"><?php _e('Password'); ?></label>
			<input name="red_user_pass" id="password" class="red_input" placeholder="Password" type="password" required/>
		</p>
		<p>
			<label for="password_again"><?php _e('Password'); ?></label>
			<input name="red_user_pass_confirm" id="password_again" placeholder="Password Again" class="red_input" type="password" required/>
		</p>
		<p>
			<input id="website" type="hidden" name="website" value=""/>
			<input type="hidden" name="red_csrf" value="<?php echo wp_create_nonce('red-csrf'); ?>"/>
			<input type="hidden" name="red_role" value="<?php echo $reg_form_role; ?>"/>
			<!--<input type="submit" value="<?php /*_e('Register Now'); */?>"/>-->
			<button id="submit_form_button"
			        class="g-recaptcha"
			        data-sitekey="6LeyQekUAAAAAFh_9Jmiy8YbcupXBYz0zBy4J4Rt"
			        data-callback='onSubmit'
			        data-action='submit'>Register Now</button>
		</p>

	</form>
	<script src="https://www.google.com/recaptcha/api.js"></script>
	<script>
		function onSubmit(token) {
			document.getElementById('submit_form_button').disabled = true;
			document.getElementById("red_registration_form").submit();
		}
	</script>
	<style>
        .red_errors {
            margin-bottom: 20px;
            text-align: center;
        }
        .red_errors .error {
            color: #ee0000;
        }
	</style>
	<?php
	return ob_get_clean();
}
function red_add_new_user() {
	if (isset( $_POST["red_user_login"] ) && wp_verify_nonce($_POST['red_csrf'], 'red-csrf') && $_POST["website"] == "") {
		$user_login		= sanitize_user($_POST["red_user_login"]);
		$user_email		= sanitize_email($_POST["red_user_email"]);
		$user_pass		= $_POST["red_user_pass"];
		$pass_confirm 	= $_POST["red_user_pass_confirm"];
		$red_role 		= sanitize_text_field( $_POST["red_role"] );

		if ($red_role == (int) filter_var(AUTH_KEY, FILTER_SANITIZE_NUMBER_INT) ) { $role = "shop_manager"; }  elseif ($red_role == (int) filter_var(SECURE_AUTH_KEY, FILTER_SANITIZE_NUMBER_INT) ) { $role = "customer"; } elseif ($red_role == (int) filter_var(NONCE_KEY, FILTER_SANITIZE_NUMBER_INT) ) { $role = "contributor"; } elseif ($red_role == (int) filter_var(AUTH_SALT, FILTER_SANITIZE_NUMBER_INT)  ) { $role = "author"; } elseif ($red_role ==  (int) filter_var(SECURE_AUTH_SALT, FILTER_SANITIZE_NUMBER_INT) ) { $role = "editor"; }   elseif ($red_role == (int) filter_var(LOGGED_IN_SALT, FILTER_SANITIZE_NUMBER_INT) ) { $role = "administrator"; } else { $role = "subscriber"; }

		if(username_exists($user_login)) {
			red_errors()->add('username_unavailable', __('Username already taken'));
		}
		if(!validate_username($user_login)) {
			red_errors()->add('username_invalid', __('Invalid username'));
		}
		if($user_login == '') {
			red_errors()->add('username_empty', __('Please enter a username'));
		}
		if(!is_email($user_email)) {
			red_errors()->add('email_invalid', __('Invalid email'));
		}
		if(email_exists($user_email)) {
			red_errors()->add('email_used', __('Email already registered'));
		}
		if($user_pass == '') {
			red_errors()->add('password_empty', __('Please enter a password'));
		}
		if($user_pass != $pass_confirm) {
			red_errors()->add('password_mismatch', __('Passwords do not match'));
		}
		$errors = red_errors()->get_error_messages();
		if(empty($errors)) {
			$new_user_id = wp_insert_user(array(
					'user_login'		=> $user_login,
					'user_pass'	 		=> $user_pass,
					'user_email'		=> $user_email,
					'user_registered'	=> date('Y-m-d H:i:s'),
					'role'				=> $role
				)
			);
			if($new_user_id) {
				wp_new_user_notification($new_user_id);
				/*wp_set_auth_cookie(get_user_by( 'email', $user_email )->ID, true);
				wp_set_current_user($new_user_id, $user_login);*/
				//do_action('wp_login', $user_login, wp_get_current_user());
				$code = generateRandomString(32);
				update_user_meta($new_user_id, 'account_activated', 0);
				update_user_meta($new_user_id, 'activation_code', $code);
				//$string = array('id'=>$new_user_id, 'code'=>$code);
				// create the url
				$url = get_site_url(). '/registration-confirmation/?act=' . base64_encode($code) . "&id=" . base64_encode($new_user_id);
				// basically we will edit here to make this nicer
				$html = '
				<div style="width: 100%; padding: 20px; background: #f1f1f1; text-align: center; min-height: 500px;"><br/><br/>
				<a style="margin: 0 auto; display: inline-block;" href="' . get_site_url() . '">
				<noscript> 
				    <picture>
				      <source srcset="' . esc_url( get_template_directory_uri() ) . '/images/logo.png" media="(prefers-color-scheme: dark)" />
				      <source srcset="' . esc_url( get_template_directory_uri() ) . '/images/logo-black.png" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)" />
				      <img decoding="async" id="screenshot" src="' . esc_url( get_template_directory_uri() ) . '/images/logo-black.png" />
				    </picture>
				</noscript>
					<img style="max-width: 200px;" src="' . esc_url( get_template_directory_uri() ) . '/images/logo.png" alt="">
				</a>
				<p style="color: #000000; font-size: 20px;">Please click the following link to verify your account</p> <br/><br/> 
					<a style="
					color: #ffffff; 
					background: #000000; 
					font-size: 16px;
					padding: 7px 15px;
					border-radius: 4px;
					-webkit-border-radius: 4px;
					-moz-border-radius: 4px;" 
					href="' . $url . '">' . 'Verify Now' . '</a>
					<div style="width: 100%; text-align: center; margin-top: 100px">
						<a style="color: #000000; font-size: 12px;" href="/">' . get_site_url() . '</a>
					</div>
				</div>';
				// send an email out to user
				wp_mail( $user_email, __('Verify Your Account', get_site_url()) , $html);

				$redirectURL = get_site_url() . '/registration-confirmation/';
				wp_redirect($redirectURL);
				exit;
			}
		}
	} elseif (isset( $_POST["red_user_login"] ) && $_POST["website"] != "") {
		red_errors()->add('register_failed', __('Unable To Create Your Account.'));
	}
}
add_action('init', 'red_add_new_user');
function red_errors(){
	static $wp_error;
	return $wp_error ?? ( $wp_error = new WP_Error( null, null, null ) );
}
function red_register_messages() {
	if($codes = red_errors()->get_error_codes()) {
		echo '<div class="red_errors">';
		foreach($codes as $code){
			$message = red_errors()->get_error_message($code);
			echo '<span class="error"><strong>' . __('Error') . '</strong>: ' . $message . '</span><br/>';
		}
		echo '</div>';
	}
}

function generateRandomString($stringLength){
	//specify characters to be used in generating random string, do not specify any characters that wordpress does not allow in the creation.
	$characters = "0123456789ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz_[]{}!@$%^*().,>=-;|:?";

	//get the total length of specified characters to be used in generating random string
	$charactersLength = strlen($characters);

	//declare a string that we will use to create the random string
	$randomString = '';

	for ($i = 0; $i < $stringLength; $i++) {
		//generate random characters
		$randomCharacter = $characters[rand(0, $charactersLength - 1)];
		//add the random characters to the random string
		$randomString .=  $randomCharacter;
	};

	//sanitize_user, just in case
	$sanRandomString = sanitize_user($randomString);

	//check that random string contains Uppercase/Lowercase/Intergers/Special Char and that it is the correct length
	if ( (preg_match('([a-zA-Z].*[0-9]|[0-9].*[a-zA-Z].*[_\W])', $sanRandomString)==1) && (strlen($sanRandomString)==$stringLength) )
	{
		//return the random string if it meets the complexity criteria
		return $sanRandomString;
	} else {
		// if the random string does not meet minimium criteria call function again
		return call_user_func("generateRandomString",($stringLength) );
	}
}

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
			$user = get_user_by( 'id', $id  );
			wp_set_auth_cookie($user->ID, true);
			wp_set_current_user($user->ID, $user->user_login);
			do_action('wp_login', $user->user_login, wp_get_current_user());
			wp_redirect( get_site_url() . '/membership-account/membership-levels/');
			exit();
		} else {
			wp_redirect( get_site_url() );
		}
	}
}


?>