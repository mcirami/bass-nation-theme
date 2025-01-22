<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Bass_Nation
 */
$actual_link = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

?>
<!doctype html>
<html <?php language_attributes(); ?> lang="en">
<head>
	<title><?php wp_title( '|', true, 'right' ); ?></title>

	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-115393894-1"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'UA-115393894-1');
	</script>

	<!-- Facebook Pixel Code -->
	<script>
		!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
			n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
			n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
			t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
			document,'script','https://connect.facebook.net/en_US/fbevents.js');
		fbq('init', '1092024584249788'); // Insert your pixel ID here.
		fbq('track', 'PageView');
	</script>
	<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=1092024584249788&ev=PageView&noscript=1" alt=""/></noscript>
	<!-- DO NOT MODIFY -->
	<!-- End Facebook Pixel Code -->

	<?php

	$attachment_id = get_field('og_image');
	$size = "video-thumb";
	$ogImage = wp_get_attachment_image_src( $attachment_id, $size );

	if(!empty($ogImage)) : ?>
		<meta property="og:image" content="<?php echo $ogImage[0];?>" />
		<meta property="og:image:secure_url" content="<?php echo $ogImage[0];?>" />
		<meta property="og:image:type" content="image/jpeg" />

	<?php else : ?>

		<meta property="og:image" content="<?php echo esc_url( get_template_directory_uri() ); ?>/images/og-image-bass-nation.png" />
		<?php   if(is_single()) :

			$str = get_field('free_lesson_link');

			?>

			<?php if (str_contains($str, "youtube")) :

			$str = explode("embed/", $str);
			$embedCode = preg_replace('/\s+/', '',$str[1]);
			?>
			<meta property="og:image" content="https://img.youtube.com/vi/<?php echo $embedCode; ?>/mqdefault.jpg" />
			<meta property="og:image:secure_url" content="https://img.youtube.com/vi/<?php echo $embedCode; ?>/mqdefault.jpg" />
			<meta property="og:image:type" content="image/jpeg" />
			<meta property="og:image:width" content="1200" />
			<meta property="og:image:height" content="630" />

		<?php endif; //if youtube?>

		<?php endif; //is_single?>

	<?php endif; //!empty($ogImage)?>

	<?php if (get_field('lesson_description')) : ?>
		<meta property="og:description" content="<?php the_field('lesson_description'); ?>" />
	<?php elseif (get_field('description')) : ?>
		<meta property="og:description" content="<?php the_field('description'); ?>" />
	<?php endif; ?>

	<meta property="og:site_name" content="Daric Bennett's Bass Nation" />
	<meta property="og:title" content="<?php echo the_title(); ?>" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="<?php echo $actual_link; ?>" />
	<meta property="fb:app_id" content="476725656008860" />

	<?php wp_head(); ?>
</head>

<?php
$current_user = wp_get_current_user();
$username = $current_user->user_login;
?>

<body <?php body_class(); ?>>
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v11.0&appId=980121096073585&autoLogAppEvents=1" nonce="d1vPKtS3"></script>
<script>
	window.fbAsyncInit = function() {
		FB.init({
			appId      : '476725656008860',
			xfbml      : true,
			version    : 'v2.9'
		});
		FB.AppEvents.logPageView();
	};

	(function(d, s, id){
		let js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement(s); js.id = id;
		js.src = "//connect.facebook.net/en_US/sdk.js";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));
</script>
<?php wp_body_open(); ?>

<div id="page" class="site">
	<header id="global_header" class="<?php echo is_user_logged_in() ? "" : "external"; ?>">

		<?php if (is_user_logged_in()) : ?>
			<div class="header_top member">
				<div class="container">
					<a href="/schedule-a-meeting">Schedule A Meeting</a>
					<a href="/contact-us">Contact Us</a>
				</div>
			</div>
		<?php endif; ?>

		<div class="header_bottom <?php echo is_user_logged_in() ? "member" : ""; ?>">
			<?php wp_reset_query(); ?>
			<div class="container">
				<div class="content_wrap">
					<?php if (is_user_logged_in()): ?>
						<div class="logo_wrap">
							<a href="/member-home">
								<h1 class="logo">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo.png" alt="Bass Nation Logo"/>
								</h1>
							</a>
						</div>
					<?php else : ?>
						<a href="/"><h1 class="logo"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo.png" alt="Bass Nation Logo"/></h1></a>
					<?php endif; ?>

					<a class="mobile_menu_icon" href="#">
						<span></span>
						<span></span>
						<span></span>
					</a>
					<div class="menu <?php if(!is_user_logged_in()) { echo "guest"; } ?>">

						<nav role="navigation">

							<?php if (is_user_logged_in()): ?>

								<?php 
									// remember to assign a menu in the admin to remove the container div
									wp_nav_menu( array( 'theme_location' => 'members', 'container' => false, 'menu_class' => 'member_menu' ) ); ?>

							<?php else : ?>

								<?php 
									// remember to assign a menu in the admin to remove the container div
									wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => 'header_menu' ) ); ?>
									<div class="right_nav">
										<ul>
											<li>
												<a href="/login">
													Login
												</a>
											</li>
											<li>
												<a class="button black" href="/register">
													Start My Free Trial
													<span>
														<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
													</span>
												</a>
												
											</li>
										</ul>
									</div>
							<?php endif; ?>

						</nav>
					</div>
				</div>
			</div>
		</div>
	</header>
	<div class="wrapper">
		<?php /* if (!is_user_logged_in()) : */?>
			<div style="display: none;" id="email_join">
				<a href="/">
					<div class="logo">
						<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo.png" alt="Bass Nation Logo"/>
					</div>
				</a>
				<h2><?php the_field('heading_text', 'option'); ?></h2>
				<p><?php the_field('form_text', 'option'); ?></p>
				<!-- Begin Mailchimp Signup Form -->

				<div id="mc_embed_signup">
					<form action="https://daricbennett.us14.list-manage.com/subscribe/post?u=31b2e6fbc1efe1874039014fd&amp;id=08854914fe" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
						<div id="mc_embed_signup_scroll">
							<input type="email" value="" name="EMAIL" class="required email" id="mce-EMAIL" />
							<div class="button_wrap">
								<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button yellow" />
								<span>
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
								</span>
							</div>
							<div id="mce-responses" class="clear">
								<div class="response" id="mce-error-response" style="display:none"></div>
								<div class="response" id="mce-success-response" style="display:none"></div>
							</div>    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
							<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_31b2e6fbc1efe1874039014fd_08854914fe" tabindex="-1" value=""></div>
						</div>
					</form>
				</div>
				<script type='text/javascript' src='//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js'></script>
				<script type='text/javascript'>(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[3]='PMPLEVELID';ftypes[3]='number';fnames[4]='PMPLEVEL';ftypes[4]='text';fnames[8]='TABLP';ftypes[8]='text';fnames[5]='TABDL';ftypes[5]='text';fnames[6]='PMPALLIDS';ftypes[6]='text';fnames[7]='FC6';ftypes[7]='text';fnames[9]='TABDL2';ftypes[9]='text';fnames[10]='TABDL3';ftypes[10]='text';fnames[1]='TABDL4';ftypes[1]='text';fnames[2]='TABDL5';ftypes[2]='text';fnames[11]='TABDL6';ftypes[11]='text';fnames[12]='TABDL7';ftypes[12]='text';fnames[13]='TABDL8';ftypes[13]='text';fnames[14]='TABDL9';ftypes[14]='text';fnames[15]='LIVESTRMLP';ftypes[15]='text';}(jQuery));var $mcj = jQuery.noConflict(true);</script>
			</div>
		<?php /* endif;  */?>