<?php
	
	/**
	 * Template Name: Contact Us
	 *
	 * The template for displaying Contact Us page.
	 *
	 *
	 * @package boiler
	 */
	 
 get_header();
  
 ?>
 
 	<section class="two_column_template full_width page_content <?php if (is_user_logged_in()) { echo 'member';} ?>">
		<div class="container">
			<header class="sub_header full_width">
				<h2><?php the_field('page_header'); ?></h2>
			</header>
		 </div><!-- .container -->

		<section class="two_column_section full_width">
			<div class="container">
				<div class="columns_wrap">
					<div class="column">
						<h3><?php the_field('heading_text'); ?></h3>
						<p><?php the_field('description'); ?></p>
						<?php the_field('form_shortcode'); ?>
					</div>
					<div class="column">
						<div class="content_wrap">
							<h2>Hey! Don't Let <span>Your Bass Journey</span> End Here!</h2>
							<p>We've crafted a comprehensive platform to help you become the bassist you've always wanted to be</p>
							<h3>Not convinced yet? Join my e-mail list for freee!</h3>
							<a class="button yellow fancybox" data-fancybox href="#" data-src="#email_join">
								Join Now!
								<span>
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
								</span>
							</a>
						</div>
					</div>
				</div>
			</div>
		</section>
		
 <?php get_footer(); ?>