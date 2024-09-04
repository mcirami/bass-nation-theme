<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Bass_Nation
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="page_content full_width <?php if (is_user_logged_in()){ echo "member";} ?>">
			<header>
				<?php if (is_page('member-profile')): ?>
					<h1 class="entry-titlem sub_header">Bass Nation Directory Profile</h1>
				<?php else : ?>
					<h1 class="entry-titlem sub_header"><?php the_title(); ?></h1>
				<?php endif; ?>
			</header>
		<?php if (pmpro_hasMembershipLevel() ||
		          is_page('membership-checkout') ||
		          is_page('privacy') ||
		          is_page('terms-of-use') ||
		          is_page('membership-levels') ||
		          is_page('login') ||
		          is_page('logout')||
		          is_page('reset-password') ||
		          is_page('register') ||
		          is_page('registration-confirmation') ||
		          is_page('verify-account') ) :
			?>
			<div class="container">

				<?php
				if(is_page('login')) : ?>
					<p class="join_text">Not a member yet? <a href="/register">Join Free Now!</a></p>
				<?php endif;
					$slug = get_post_field( 'post_name', get_post() );
				?>

				<div class="full_width <?php echo $slug;?>">
					<?php the_content(); ?>
				</div>
			</div>

		<?php else :

			get_template_part( 'template-parts/content', 'not-member' );

		endif; ?>
	</div>
	<?php if ( get_edit_post_link() ) : ?>
		<footer class="entry-footer">
			<?php
			edit_post_link(
				sprintf(
					wp_kses(
						/* translators: %s: Name of current post. Only visible to screen readers */
						__( 'Edit <span class="screen-reader-text">%s</span>', 'bass-nation' ),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					),
					wp_kses_post( get_the_title() )
				),
				'<span class="edit-link">',
				'</span>'
			);
			?>
		</footer><!-- .entry-footer -->
	<?php endif; ?>
</article><!-- #post-<?php the_ID(); ?> -->
