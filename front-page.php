<?php
/**
 * Template Name: Home
 *
 * The template for displaying home page.
 *
 *
 * @package bass-nation
 */

get_header(); ?>

	<section class="home_section page_content_home">		
		<?php $heroImage = get_field('hero_image'); ?>
		<section class="hero full_width" style="background:url(<?php if(!empty($heroImage)){echo $heroImage['url'];} ?>) no-repeat">
			<div class="container">
				<div class="full_width">
					<h2><?php the_field('hero_title'); ?></h2>
					<h3><?php the_field('hero_sub_title'); ?></h3>
					
				</div>
				<div class="full_width">
					<div class="content_wrap">
						<div class="text_wrap">
							<a class="button red fancybox" href="#email_join">Click To Join Email List for FREE Bass Lessons!</a>
						</div>
					</div>
				</div>
			</div>
		</section><!-- hero -->
		<section class="motto full_width">
			<div class="container">
				<div class="content full_width">
					<?php $quoteBackground = get_field('quote_section_background_image'); ?>
					<div class="content_wrap" style="background:url(<?php if(!empty($quoteBackground)){echo $quoteBackground['url']; }?>) no-repeat center center">
						<h3><?php the_field('quote_section'); ?></h3>
					</div>
					<div class="image_wrap">
						
						<?php $quoteImage = get_field('quote_section_image'); 
							
							if (!empty($quoteImage)) :
						?>	
								<img src="<?php echo $quoteImage['url']; ?>" alt="<?php echo $quoteImage['alt']; ?>" />
						
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section><!-- motto -->
		
		<?php $lessonsBackground =  get_field('lessons_background');?>
		<section class="lessons full_width" style="background:url(<?php if(!empty($lessonsBackground)){echo $lessonsBackground['url'];} ?>) no-repeat">
			<div class="container">
				<h2><?php the_field('lessons_heading'); ?></h2>
				<div class="content_wrap full_width">
					<?php $columnBackground =  get_field('lessons_column_background');?>
					
					<?php if (have_rows('lessons_text')) : ?>

						<?php while (have_rows('lessons_text')) : the_row(); ?>
							
							<div class="row full_width">
								
								<?php if (have_rows('row')) : ?>
									
									<?php while (have_rows('row')) : the_row(); ?>
										
										<div class="column" style="background:url(<?php if(!empty($columnBackground)){echo $columnBackground['url'];} ?>) no-repeat center center">
											<h3><?php the_sub_field('column_text') ?></h3>
										</div>
									
									<?php endwhile; ?>
									
								<?php endif; ?>
								
							</div>
							
						<?php endwhile; ?>
					<?php endif; ?>
				</div>
			</div>
		</section> <!-- lessons -->
		<section class="video full_width">
			<div class="container">
				<div class="column">
					<h2><?php the_field('about_heading'); ?></h2>
					<p><?php the_field('about_text'); ?></p>
					<p class="bold desktop"><?php the_field('about_form_text_desktop'); ?></p>
					<p class="bold mobile"><?php the_field('about_form_text_mobile'); ?></p>
					<a class="button red fancybox" data-fancybox href="#" data-src="#email_join">
						Click Here To Join!
					</a>
				</div>
				<div class="column">
					<div class="video_wrapper full_width">
						<iframe src="<?php the_field('about_video_link'); ?>" allowfullscreen></iframe>
					</div>
				</div>
			</div>
		</section>
	</section>

<?php get_footer(); ?>