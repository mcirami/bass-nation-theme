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
		<div class="content_wrap top_row">
			<div class="logo">
				<a href="/">
					<img alt="Bass Nation Logo" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/logo.png"/>
				</a>
			</div>
			<div class="column">
				<?php $firstColumn = get_field('first_column_group', 'option');  ?>
				<h3><?php echo $firstColumn['first_column_heading']; ?></h3>
				<div class="menu">
					<nav role="navigation">
						<?php if (is_user_logged_in()) : ?>

							<?php if (have_rows('member_links_first_column', 'option')) : ?>
								<ul>

									<?php while (have_rows('member_links_first_column', 'option')) : the_row();

										if(get_sub_field('link_type', 'option') == "Hard Coded Link") : ?>

											<li><a href="<?php echo do_shortcode('[bbpm-messages-link]'); ?>"><?php the_sub_field('link_text','option'); ?></a></li>

										<?php else : ?>

											<li><a href="<?php the_sub_field('link', 'option'); ?>"><?php the_sub_field('link_text','option'); ?></a></li>

										<?php endif; ?>

									<?php endwhile; ?>

								</ul>
							<?php endif; ?>

						<?php else : ?>

							<?php if (have_rows('first_column_group', 'option')) : ?>
				
									<?php while (have_rows('first_column_group', 'option')) : the_row(); 
									
										$columnLinks = get_sub_field('first_column_links', 'option');
										if($columnLinks) : ?>
											<ul>
												<?php foreach($columnLinks as $columnLink) : ?>
													<li>
														<a href="<?php echo $columnLink['link']; ?>"><?php echo $columnLink['link_text']; ?></a>
													</li>
												<?php endforeach; ?>
											</ul>
										<?php endif; ?>

									<?php endwhile; ?>
				
							<?php endif; ?>
							
						<?php endif; ?>
					</nav>
				</div>
			</div>
			<div class="column">

				<h3><?php the_field('second_column_heading', 'option'); ?></h3>
					<ul>
						<?php if (have_rows('second_column_group', 'option')) : ?>

							<?php while (have_rows('second_column_group', 'option')) : the_row(); 
								$columnLinks = get_sub_field('second_column_links', 'option');
								if($columnLinks) :
							?>
								<ul>
									<?php foreach ($columnLinks as $link) : ?>
										<li><a href="<?php echo $link['second_column_link']; ?>"><?php echo $link['second_column_link_text']; ?></a></li>
									<?php endforeach; ?>
								</ul>
								<?php endif; ?>
							<?php endwhile; ?>
						<?php endif; ?>
					</ul>
			</div>

			<div class="column">
				<h3><?php the_field('third_column_heading', 'option'); ?></h3>

				<?php if (have_rows('third_column_group', 'option')) : ?>
					<ul>
					
						<?php while (have_rows('third_column_group', 'option')) : the_row(); 
							$columnLinks = get_sub_field('third_column_links', 'option');
							if ($columnLinks) :
						?>
								<ul>
									<?php foreach ($columnLinks as $link) : ?>
										<li><a href="<?php echo $link['third_column_link']; ?>"><?php echo $link['third_column_link_text']; ?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						<?php endwhile; ?>

					</ul>

				<?php endif; ?>
	
			</div>
			<div class="column">
				<div class="button_wrap">
					<h3>Join my email list</h4>
					<a class="button yellow fancybox" data-fancybox href="#" data-src="#email_join">
						Join Now!
						<span>
							<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
						</span>
					</a>
					<small>Receive new bass lesson videos weekly!</small>
				</div>
			</div>
		</div>

		<div class="bottom_row">
			<?php $bottomFooter = get_field('footer_bottom', 'option'); ?>
			<div class="copy">
				<p><?php echo $bottomFooter['copy_text']; ?></p></li>
			</div>
			<div class="social_links">
			<?php 
			
			$socialIcons = $bottomFooter['social_icons'];
			if($socialIcons) :

				foreach($socialIcons as $icon) : ?>

						<a class="<?php if ( $icon['social_link'] == "facebook"){echo "facebook";} elseif ($icon['social_link'] == "instagram"){ echo "instagram";} elseif ($icon['social_link'] == "youtube") {echo "youtube";}?>" target="_blank" href="<?php echo $icon['social_link']; ?>">
							<?php if (!empty($icon['social_icon'])) : ?>
								<img src="<?php echo $icon['social_icon']['url']; ?>" alt="Bass Nation Social Media"/>
							<?php endif; ?>
						</a>
				<?php endforeach; ?>
			<?php endif; ?>
			</div>
		</div>
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
					<a href="<?php echo get_site_url() . '/register'?>">Click here to redirect manually</a>
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
				const name = cname + "=";
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
