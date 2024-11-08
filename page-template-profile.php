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
				<div class="cover_img full_width">
					<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/home-bass.jpg" alt="playing bass"/>
				</div>
				<div class="avatar_wrap full_width">
					<?php echo get_avatar( get_current_user_id(), 96 );  ?>
				</div>
				<div class="form_wrap full_width">
					<?php echo do_shortcode('[pmpro_member_profile_edit]');?>
					<div class="avatar_upload">
						<?php echo do_shortcode('[basic-user-avatars]');?>
					</div>
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