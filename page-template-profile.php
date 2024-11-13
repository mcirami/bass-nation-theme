<?php

/**
 * Template Name: Member Profile
 *
 * The template for displaying member Profile edit page.
 *
 *
 * @package boiler
 */

get_header();?>

<div class="full_width page_content member">


		<header class="sub_header full_width">
		    <div class="container">
		        <h2><?php the_title(); ?></h2>
			</div><!-- .container -->
		</header>

	<?php if (pmpro_hasMembershipLevel()) : ?>
		<section class="profile_wrap">
			<div class="container">
				<div class="form_wrap full_width">
					<div class="avatar_upload">
						<?php echo do_shortcode('[avatar_upload]');?>
					</div>
					<?php echo do_shortcode('[pmpro_member_profile_edit]');?>
				</div>

			</div>
		</section>

	<?php else :

		get_template_part( 'template-parts/content', 'not-member' );

	 endif; ?>
</div>






<?php
	get_footer();
?>