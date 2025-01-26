<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */
$posts = get_field('lesson_links');
get_header(); ?>

	<div class="lessons_page courses full_width page_content <?php if (is_user_logged_in()){ echo "member";} ?>">

		<header class="sub_header full_width">
			<div class="container">
				<h2><?php the_title(); ?></h2>
			</div><!-- .container -->
		</header>

		<?php if (pmpro_hasMembershipLevel()) : ?>

			<div id="video_player" class="full_width">
				<div id="video_iframe_wrap"></div>
				<div id="video_content_wrap"></div>
			</div>

			<div class="video_list full_width">
				<?php $description = get_field('course_description');  
				if ($description) : ?>
					<div class="banner_wrap full_width">
						<div class="container">
							<?php echo $description; ?>
						</div>
					</div>
				<?php endif;?>
				<div class="container">
					<div class="videos_wrap">
						<div class="filter_controls full_width" style="display:none;">

							<div class="filters filters-group">
								<h3>Filter Lessons By<span>:</span></h3>
								<p>(select as many as you like)</p>
								<ul id="lesson_grid" class="filter_list full_width filter-options">
									<li data-group="all" class="active all">All</li>
									<li data-group="<?php echo ""; ?>"><?php echo ""; ?></li>
								</ul>
							</div>
							<div class="search_box">
								<input id="search_input" type="text" name="search" placeholder="Search Lesson By Keyword" data-search>
							</div>
						</div><!-- filter_controls -->
						<div id="filter_images" class="shuffle-container full_width">

							<?php /* while ( have_posts() ) : the_post(); */
								foreach($posts as $post) : 
									setup_postdata($post);
									get_template_part('template-parts/content', 'member-lesson'); 
									//get_template_part( 'template-parts/content-courses', get_post_format() );

								endforeach;
								//boiler_content_nav( 'nav-below' );

							/* endwhile;  */// End of the loop.

							wp_reset_query();
							?>

						</div><!-- video_list -->
					</div>
				</div>
			</div>
		<?php else :

			get_template_part( 'template-parts/content', 'not-member' );  ?>

		<?php endif; ?>
			</div>
	</div><!-- lessons_page -->

<?php get_footer();