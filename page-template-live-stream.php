<?php
/**
 * Template Name: BN Live Stream
 *
 * The template for displaying past Live Streams.
 *
 *
 * @package boiler
 */


get_header();
?>

<div class="full_width page_content <?php if (is_user_logged_in()){ echo "member";} ?>">
	<header class="sub_header full_width">
		<div class="container">
			<h2><?php the_title(); ?></h2>
		</div><!-- .container -->
	</header>

	<div class="full_width live_stream">
		<div class="container">

            <?php if (pmpro_hasMembershipLevel()) : ?>

                <section class="columns_wrap">
	                <?php the_content(); ?>
                </section>

                <div class="full_width button_row">
	                <a class="button yellow" href="/live-streams">
		                View Past Streams
						<span>
							<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
						</span>
	                </a>
                </div>

            <?php else :

                get_template_part( 'template-parts/content', 'not-member' );  ?>

            <?php endif; ?>

        </div>
	</div>
</div>

<?php
get_footer();
?>