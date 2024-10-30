<?php

/**
 * Template Name: Video Blog
 *
 * The template for displaying Videos Submitted.
 *
 *
 * @package boiler
 */
acf_form_head();
get_header();

$pageTitle = esc_html(get_the_title());

if($pageTitle == "Video Q &#038; A") {

	$args = array(
		'post_type' => 'videos',
		'order_by' => 'post_date',
		'order' => 'DESC',
		'posts_per_page' => -1,
	);

} 

if ($pageTitle == "Bass Nation TV") {

	$args = array (
	    'post_type' => 'tv-videos',
	    'order_by' => 'post_date',
	    'order' => 'DESC',
	    'posts_per_page' => -1,
	);
}
$posts = new WP_Query($args);

?>
    <section class="video_submit page_content full_width<?php if (is_user_logged_in()){ echo " member";} ?>">
        <header class="sub_header full_width">
            <div class="container">
                <h2><?php the_field('page_title'); ?></h2>
            </div><!-- .container -->
        </header>

        <?php if (pmpro_hasMembershipLevel()) : ?>

            <div class="full_width">
				<div class="banner_wrap">
					<div class="container">
						<?php if (get_field('sub_heading')) {?>
								<h3><?php the_field('sub_heading'); ?></h3>
						<?php } ?>
						<?php if (get_field('description')) {?>
								<p><?php the_field('description'); ?></p>
							<?php } ?>
						<?php if($pageTitle == "Video Q &#038; A") : ?>
							<button id="post_video_btn" class="button yellow">
								<?php the_field('button_text'); ?>
								<span>
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
								</span>
							</button>
						<?php endif; ?>
					</div>
				</div><!-- banner_wrap -->
				<div class="bottom_section">
					<div class="container">
							<?php if($pageTitle == "Video Q &#038; A") : ?>
								<div id="post_submit_form" class="full_width">

									<div class="form_wrap full_width">

										<h3>Upload A YouTube Video & Question</h3>
										<?php

										acf_form(array(
											'post_id'		=> 'new_post',
											'post_title'	=> true,
											'new_post'		=> array(
												'post_type'		=> 'videos',
												'post_status'	=> 'publish'
											),
											'return'		=> '',
											'submit_value'	=> 'Submit Post',
											'html_submit_spinner' => '<span class="acf-spinner"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"><radialGradient id="a10" cx=".66" fx=".66" cy=".3125" fy=".3125" gradientTransform="scale(1.5)"><stop offset="0" stop-color="#FF156D"></stop><stop offset=".3" stop-color="#FF156D" stop-opacity=".9"></stop><stop offset=".6" stop-color="#FF156D" stop-opacity=".6"></stop><stop offset=".8" stop-color="#FF156D" stop-opacity=".3"></stop><stop offset="1" stop-color="#FF156D" stop-opacity="0"></stop></radialGradient><circle transform-origin="center" fill="none" stroke="url(#a10)" stroke-width="15" stroke-linecap="round" stroke-dasharray="200 1000" stroke-dashoffset="0" cx="100" cy="100" r="70"><animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2" values="360;0" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite"></animateTransform></circle><circle transform-origin="center" fill="none" opacity=".2" stroke="#FF156D" stroke-width="15" stroke-linecap="round" cx="100" cy="100" r="70"></circle></svg></span>',
											'html_after_fields' => '<a class="cancel_post button black" href="#">Cancel</a>'
										));

										?>
									</div>

								</div>
							<?php endif; ?>


						<article class="content">

							<?php if ( $posts->have_posts() ) : ?>

								<?php while ( $posts->have_posts() ) : $posts->the_post(); ?>

									<?php
									/* Include the Post-Format-specific template for the content.
									* If you want to overload this in a child theme then include a file
									* called content-___.php (where ___ is the Post Format name) and that will be used instead.
									*/
										get_template_part( 'template-parts/content-video-blog', get_post_format() );
									?>

								<?php endwhile; ?>

								<?php //boiler_content_nav( 'nav-below' ); ?>

							<?php else : ?>

								<?php if($pageTitle == "Video Q &#038; A") : ?>
									<h3>No Submissions Yet</h3>
									<p>Click on the button above to post a video and question.</p>
								<?php  elseif($pageTitle == "Bass Nation TV") : ?>
									<h3>Bass Nation TV Coming Soon...</h3>
									<h3>Stay Tuned</h3>
								<?php endif; ?>

							<?php endif; ?>
						</article>
					</div><!-- container -->
				</div>

                <?php //get_sidebar(); ?>

            </div><!-- full_width -->

        <?php else :

            get_template_part( 'template-parts/content', 'not-member' );  ?>

        <?php endif; ?>

    </section>

<?php get_footer(); ?>
<form action=""></form>