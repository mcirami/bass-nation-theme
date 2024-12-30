<?php
	
	/**
	 * Template Name: Passions
	 *
	 * The template for displaying Passions page.
	 *
	 *
	 * @package boiler
	 */
	 
 get_header();
  
 ?>
	 <section class="passions full_width page_content <?php if (is_user_logged_in()) { echo "member";} ?>">
	 	
	 	<header class="sub_header full_width">
			 <div class="container">
					<h2><?php the_field('page_header'); ?></h2>
			</div><!-- .container -->
		 </header>

	 	
	 	<section class="top_section full_width">
			<div class="container">
				<div class="text_wrap">
					<h3><?php the_field('heading_text'); ?></h3>
					<p><?php the_field('description' , false, false); ?></p>
				</div>
			</div>
		</section>
		<section class="photo_section gray full_width">
			<div class="container">
				<div class="full_width">
					<h2><?php the_field('gear_heading'); ?></h2>
				</div>
				<?php if (have_rows('gear')) : ?>
					<div class="columns_wrap">
						<?php while (have_rows('gear')) : the_row(); ?>
								<?php $gearImage = get_sub_field('gear_image'); ?>
								<div class="column_wrap">
									<div class="image_wrap">
										<a target="_blank" href="https://<?php the_sub_field('gear_link'); ?>">
											<img src="<?php echo $gearImage['url'] ?>" alt="<?php echo $gearImage['alt']; ?>"/>
										</a>
									</div>
									<div class="text_wrap">
										<a href="<?php the_sub_field('gear_link'); ?>"><?php the_sub_field('gear_link'); ?></a>
									</div>
								</div>
						<?php endwhile; ?>
					</div>
				<?php endif; ?>
			</div><!-- container -->
		</section>
		<section class="photo_section full_width bass_players">
			<div class="container">
				<div class="full_width">
					<h2><?php the_field('inspires_heading'); ?></h2>
				</div>
				<?php if (have_rows('bassists')) : ?>
					<div class="columns_wrap">
						<?php while (have_rows('bassists')) : the_row(); ?>
								<?php $image = get_sub_field('image'); ?>
								<div class="column_wrap">
									<div class="image_wrap">
										<a target="_blank" href="<?php  the_sub_field('link'); ?>">
											<img src="<?php echo $image['url'] ?>" alt="<?php echo $image['alt']; ?>"/>
										</a>
									</div>
									<div class="text_wrap">
										<p><?php the_sub_field('name'); ?></p>
									</div>
								</div>
						<?php endwhile; ?>
					</div>
				<?php endif; ?>
			</div><!-- container -->
		</section>
	 	
	 </section>
 
 <?php get_footer(); ?>