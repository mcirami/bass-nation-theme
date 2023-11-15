<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Bass_Nation
 */
$current_user = wp_get_current_user();
$username = $current_user->user_login;
?>

<footer id="global_footer" class="site_footer">
	<div class="container">
		<div class="content_wrap">
			<div class="logo">
				<a href="/">
					<img alt="Bass Nation Logo" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo.png"/>
				</a>
			</div>
			<div class="columns_wrap">
				<div class="column">
					<h3><?php the_field('first_column_heading', 'options'); ?></h3>
					<div class="menu">
						<nav role="navigation">
							<?php if (is_user_logged_in()) : ?>

								<?php if (have_rows('member_links_first_column', 'options')) : ?>
									<ul>

										<?php while (have_rows('member_links_first_column', 'options')) : the_row();

											if(get_sub_field('link_type', 'options') == "Hard Coded Link") : ?>

												<li><a href="<?php echo do_shortcode('[bbpm-messages-link]'); ?>"><?php the_sub_field('link_text','options'); ?></a></li>

											<?php else : ?>

												<li><a href="<?php the_sub_field('link', 'options'); ?>"><?php the_sub_field('link_text','options'); ?></a></li>

											<?php endif; ?>

										<?php endwhile; ?>

									</ul>
								<?php endif; ?>

							<?php else : ?>

								<?php if (have_rows('first_column_links', 'options')) : ?>
									<ul>
										<?php while (have_rows('first_column_links', 'options')) : the_row(); ?>
											<li>
												<a href="<?php the_sub_field('link', 'options'); ?>"><?php the_sub_field('link_text', 'options'); ?></a>
											</li>
										<?php endwhile; ?>
									</ul>
								<?php endif; ?>

							<?php endif; ?>
						</nav>
					</div>
				</div>
				<div class="column">

					<h3><?php the_field('second_column_heading', 'options'); ?></h3>

					<?php if (is_user_logged_in()) : ?>

						<?php if (have_rows('member_links_second_column', 'options')) : ?>
							<ul>

								<?php while (have_rows('member_links_second_column', 'options')) : the_row(); ?>

									<li><a href="<?php the_sub_field('link', 'options'); ?>"><?php the_sub_field('link_text','options'); ?></a></li>

								<?php endwhile; ?>

							</ul>
						<?php endif; ?>

					<?php else : ?>

						<ul>
							<?php if (have_rows('second_column_links', 'options')) : ?>

								<?php while (have_rows('second_column_links', 'options')) : the_row(); ?>

									<?php if (get_sub_field('popup', 'options')) : ?>

										<li><a class="fancybox" href="#email_join"><?php the_sub_field('link_text','options'); ?></a></li>
										<!--<li><a class="feather" data-featherlight="#email_join" href="#"><?php the_sub_field('link_text','options'); ?></a></li>-->


									<?php else: ?>

										<li><a href="<?php the_sub_field('link', 'options'); ?>"><?php the_sub_field('link_text','options'); ?></a></li>

									<?php endif; ?>

								<?php endwhile; ?>
							<?php endif; ?>

						</ul>

					<?php endif; ?>

				</div>
				<div class="column">
					<h3><?php the_field('third_column_heading', 'options'); ?></h3>
					<!--<p class="white">E:<a href="mailto:daric@daricbennett.com">Daric@DaricBennett.com</a></p>-->
					<div class="icon_wrap">

						<?php if (have_rows('third_column_links', 'options')) : ?>

							<?php while (have_rows('third_column_links', 'options')) : the_row(); ?>

								<div class="row">

									<?php $socialIcon = get_sub_field('social_icon','options');
									$socialText = strtolower(get_sub_field('social_text', 'options'));
									?>

									<a class="<?php if ( $socialText == "facebook"){echo "facebook";} elseif ($socialText == "instagram"){ echo "instagram";} elseif ($socialText == "youtube") {echo "youtube";}?>" target="_blank" href="<?php the_sub_field('social_link','options'); ?>">
										<?php if (!empty($socialIcon)) : ?>
											<img src="<?php echo $socialIcon['url']; ?>" alt="Bass Nation Social Media"/>
										<?php endif; ?>
										<h3><?php the_sub_field('social_text','options'); ?></h3>
									</a>

								</div>

							<?php endwhile; ?>

						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php if (have_rows('bottom_copy', 'options')) : ?>
			<div class="copy">
				<ul>
					<?php while (have_rows('bottom_copy', 'options')) : the_row(); ?>

						<?php $link = get_sub_field('add_link', 'options');
						if($link) :
							?>
							<li><a href="<?php the_sub_field('link', 'options'); ?>"><?php the_sub_field('text', 'options'); ?></a></li>
						<?php else : ?>
							<li><p><?php the_sub_field('text', 'options'); ?></p></li>
						<?php endif; ?>

					<?php endwhile; ?>

				</ul>
				<ul>
					<li><a href="mailto:<?php the_field('email', 'options'); ?>"><?php the_field('email', 'options'); ?></a></li>
				</ul>
			</div>
		<?php endif; ?>
	</div><!-- .container -->
</footer>
	<?php
	if(isset($_GET['act'])) : ?>

		<div id="verifying_popup">
			<div class="message_box">
				<div class="heading">
					<h2>Verifying Your Account</h2>
				</div>
				<div class="content_wrap">
					<p>Hang Tight. You will be redirected shortly.</p>
					<div class="loading_gif">
						<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/spinner.svg" alt="">
					</div>
					<p>
						If you are not redirected soon...
					</p>
					<a href="<?php get_site_url() . '/membership-account/membership-levels/'?>">Click here to be redirect manually</a>
				</div>
			</div>
		</div>

	<?php endif; ?>
</div><!-- .wrapper -->
</div><!-- #page -->
<?php if (!pmpro_hasMembershipLevel() && !is_page(27) && !is_page(30) && !is_page(19)) : ?>
	<script>
		jQuery(document).ready(function($){
			const popup = getCookie("popup");
			const subscribed = getCookie("subscribed");
			const subscribedForm = getCookie("subscribed_form");
			const lpSubscribed = getCookie("lp-subscribed");
			const subscribedMember = getCookie("subscribed-member");
			const emailJoin = $('#email_join');

			setTimeout(function() {

				if (!emailJoin.hasClass('active') && popup === "" && subscribed === "" && subscribedForm === "" && lpSubscribed === "" && subscribedMember === "") {

					$.fancybox({
						arrows: false,
						autoSize: false,
						width: '750',
						height: '410',
						closeBtn: true,
						scrolling: 'hidden',
						scrollOutside: false,
						href: '#email_join',
						beforeShow  :function(){
							$("body").css({'overflow-y':'hidden'});
						},
						afterClose :function(){
							$("body").css({'overflow-y':'visible'});
						},
						helpers: {
							overlay: {
								locked: true
							}
						}
					});

					emailJoin.addClass('active');
					createCookie("popup", "popped", 1);

				} }, 15000);


			function getCookie(cname) {
				const = cname + "=";
				const ca = document.cookie.split(';');
				for(var i = 0; i <ca.length; i++) {
					var c = ca[i];
					while (c.charAt(0)===' ') {
						c = c.substring(1);
					}
					if (c.indexOf(name) === 0) {
						return c.substring(name.length,c.length);
					}
				}
				return "";
			}

			function createCookie(name, value, days) {
				let expires;
				if (days) {
					const date = new Date();
					date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
					expires = "; expires=" + date.toGMTString();
				}
				else {
					expires = "";
				}
				document.cookie = name + "=" + value + expires + "; path=/";
			}
		});
	</script>

<?php endif; ?>
<?php wp_footer(); ?>

</body>
</html>
