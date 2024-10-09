<?php
/**
 * Template Name: Two Column Layout
 *
 * The template for displaying Two Column Layout page like Contact us.
 *
 *
 * @package boiler
 */


get_header();

?>
 	<section class="two_column_template full_width page_content">
		<div class="container">
			<header class="sub_header full_width">
				<h2><?php echo $pagename; ?></h2>
			</header>
		 </div><!-- .container -->

		<section class="two_column_section full_width">
			<div class="container">
				<div class="columns_wrap">
					<div class="column">
                        <?php if($pagename == "login") : ?>
                            <?php echo the_content(); ?>
                        <?php else: ?>   
                            <h3><?php the_field('heading_text'); ?></h3>
                            <p><?php the_field('description'); ?></p>
                            <?php the_field('form_shortcode'); ?>
                        <?php endif; ?>
					</div>
					<div class="column">
						<div class="content_wrap">
                        <?php if($pagename == "login") : ?>
                                <h2>Don't Have An Account Yet?</h2>
                                <p>We've crafted a comprehensive platform to help you become the bassist you've always wanted to be</p>
                                <h3>Join Now! <span>FREE</span> For 3 Days!</h3>
                                <a class="button yellow" href="/register">
                                    Join Now!
                                    <span>
                                        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
                                    </span>
                                </a>
                            <?php else: ?>   
                                <h2>Hey! Don't Let <span>Your Bass Journey</span> End Here!</h2>
                                <p>We've crafted a comprehensive platform to help you become the bassist you've always wanted to be</p>
                                <h3>Not convinced yet? Join my e-mail list for freee!</h3>
                                <a class="button yellow fancybox" data-fancybox href="#" data-src="#email_join">
                                    Join Now!
                                    <span>
                                        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
                                    </span>
                                </a>
                            <?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</section>
    </section>

<?php get_footer(); ?>