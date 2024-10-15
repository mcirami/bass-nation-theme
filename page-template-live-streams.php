<?php
/**
 * Template Name: Live Streams
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
			<h2>Past Live Streams</h2>
		</div><!-- .container -->
	</header>

	<div class="full_width live_stream">
		<div class="container">

            <?php if (pmpro_hasMembershipLevel()) : ?>

                    <section class="full_width gallery">

	                    <?php the_content(); ?>

                    </section>

            <?php else :

                get_template_part( 'template-parts/content', 'not-member' );  ?>

            <?php endif; ?>

        </div>
	</div>
</div>

<?php get_footer(); ?>