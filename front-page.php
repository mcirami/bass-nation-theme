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

	<div class="home_section">		
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
							<p><?php the_field('sub_text'); ?></p>
							<a class="button yellow" href="/register">
								Start My FREE TRIAL Now!
								<span>
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
								</span>
							</a>
						</div>
					</div>
				</div>
			</div>
		</section><!-- hero -->
		<section class="video full_width">
			<div class="container">
				<div class="small_container">
					<div class="video_wrapper full_width">
						<iframe src="<?php the_field('about_video_link'); ?>" allowfullscreen></iframe>
					</div>
					<div class="text_wrap full_width">
						<?php the_field('about_text'); ?>
						<p class="signup_text"><?php the_field('about_signup_text'); ?></p>
						<a class="button yellow fancybox" data-fancybox href="#" data-src="#email_join">
							Join E-mail List Now
							<span>
								<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
							</span>
						</a>
					</div>
				</div>
				
			</div>
		</section>
		<section class="lessons full_width">
			<div class="container">
				<div class="small_container">
					<h2><?php the_field('lessons_heading'); ?></h2>

					<?php if (have_rows('lessons_text')) : ?>

						<div class="columns">
							<?php while (have_rows('lessons_text')) : the_row(); ?>
							
									<div class="column">
										<h3><?php the_sub_field('column_text') ?></h3>
									</div>
								
							<?php endwhile; ?>
						</div>
					<?php endif; ?>
				</div>
				<div class="slider_wrap full_width">
					<div class="swiper">
						<div class="swiper-wrapper">
							<?php 
								$args = array(
									'post_type' => 'lessons',
									'order_by' => 'post_date',
									'order' => 'ASC',
									'posts_per_page' => 20,
									'category__not_in' => array(10),
								);
						
								$catTerms = get_terms('category');
								$levelTerms = get_terms('level', array(
										'orderby' => 'description'
								));

								$lessons = new WP_Query($args);

								if ( $lessons->have_posts() ) : while( $lessons->have_posts() ) : $lessons->the_post();

										$hide = get_field('hide_lesson');
				
										if (!$hide) : ?>
											<div class="swiper-slide">
												<?php get_template_part('template-parts/content', 'home-slider'); ?>
											</div>
										<?php endif; ?> 

								<?php endwhile; //query loop
							endif; 
							wp_reset_query();
							?> 
		
						</div>
						
					</div>
					<div class="swiper-button-prev"></div>
						<div class="swiper-button-next"></div>
				</div>
			</div>
		</section><!-- lessons -->

		<section class="call_to_action">
			<div class="container">
				<h3>Get Full Access To Everything!</h3>
				<a class="button yellow" href="/register">
					Start FREE Today!
					<span>
						<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
					</span>
				</a>
			</div>
		</section>

		<section class="about_me full_width">
			<div class="container">
				<h2>About Me & My Goals For You</h2>
				<div class="columns_2">
					<div class="column">
						<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/daric-about.jpg" alt="">
					</div>
					<div class="column">
						<p>My goal is to bring every bass player the best online bass guitar lessons at an affordable price so everyone can take their playing to the next level! I offer free bass lessons on the site and full bass lessons, forums, messaging and much more to Bass Nation members with <span>100% Free Trials to test it out!</span> Grab your bass and let's get started!</p>
						<div class="button_wrap">
							<h4>Join my email list</h4>
							<a class="button yellow fancybox" data-fancybox href="#" data-src="#email_join">
								Join Now!
								<span>
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
								</span>
							</a>
							<small>Receive new bass lesson videos weekly!</small>
						</div>
					</div>
				</div>
			</div>
		</section>
		<section id="testimonials_section" class="full_width">
			<div class="heading">
				<div class="small_container">
					<h2>Over 25k students trust Daric Bennett with their bass training</h2>
				</div>
			</div>
			<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/bass-players.jpg" alt="">
			<div class="content_wrap">
				<div class="small_container">
					<h4>Our students feel proud to be a part of Daric Bennett  and are based all over the world. From total beginners to professionals, we meet students where they are and take them to where they want to be. </h4>
				</div>	
				<h2>What our members say</h2>
				<p>4.9 rating based on 599 reviews</p>
				<div class="testimonials">
					<div class="column">
						<div class="reviews">
							<p>Daric is the man! Honestly one of the best bass players I've ever seen and heard. Learning to play bass guitar from him is an honor! Well worth the cheap membership fee that he charges for all the content you get. Especially since you can directly talk to him and ask questions. Keep it up Daric</p>
							<div class="col_footer">
								<h5>BassPlaya</h5>
								<div class="stars">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
								</div>
							</div>
						</div>
						<div class="reviews">
							<p>Love the site. Simple and easy to use. Daric is an awesome teacher. He obviously knows hit stuff and knows how to relay what he knows in a way I can understand. Look forward to more lessons and throwdowns! </p>
							<div class="col_footer">
								<h5>jefftoch</h5>
								<div class="stars">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
								</div>
							</div>
						</div>
					</div>
					<div class="column">
						<div class="reviews">
							<p>Being a part of Daric's Bass Nation is well worth it! The fact that Daric shares his infinite knowledge about learning to play bass guitar so cheap just shows that he enjoys teaching and is passionate about bass. I would pay double for what I've learned and most people do, if not more! I look forward to the new videos every week!</p>
							<div class="col_footer">
								<h5>matteoc5</h5>
								<div class="stars">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
								</div>
							</div>
						</div>
						<div class="reviews">
							<p>Daric is the man! Honestly one of the best bass players I've ever seen and heard. Learning to play bass guitar from him is an honor! Well worth the cheap membership fee that he charges for all the content you get. Especially since you can directly talk to him and ask questions. Keep it up Daric</p>
							<div class="col_footer">
								<h5>BassPlaya</h5>
								<div class="stars">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
								</div>
							</div>		
						</div>
						<div class="reviews">
							<p>Daric is the man! Honestly one of the best bass players I've ever seen and heard. Learning to play bass guitar from him is an honor! Well worth the cheap membership fee that he charges for all the content you get. Especially since you can directly talk to him and ask questions. Keep it up Daric</p>
							<div class="col_footer">
								<h5>BassPlaya</h5>
								<div class="stars">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
								</div>
							</div>		
						</div>
					</div>
					<div class="column">
						<div class="reviews">
							<p>Daric is the man! Honestly one of the best bass players I've ever seen and heard. Learning to play bass guitar from him is an honor! Well worth the cheap membership fee that he charges for all the content you get. Especially since you can directly talk to him and ask questions. Keep it up Daric</p>
							<div class="col_footer">
								<h5>BassPlaya</h5>
								<div class="stars">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
								</div>
							</div>
						</div>
						<div class="reviews">
							<p>Daric is the man! Honestly one of the best bass players I've ever seen and heard. Learning to play bass guitar from him is an honor! Well worth the cheap membership fee that he charges for all the content you get. Especially since you can directly talk to him and ask questions. Keep it up Daric</p>
							<div class="col_footer">
								<h5>BassPlaya</h5>
								<div class="stars">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
									<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/review-star.png" alt="">
								</div>
							</div>		
						</div>
					</div>
				</div>
			</div>
		</section>
		<section class="call_to_action_two_col full_width">
			<div class="top_section full_width">
				<div class="container">
					<h2>Try Bass Nation Membership</br>FREE for 3 Days</h2>
				</div>
			</div>
			<div class="full_width bottom_section">
				<div class="container">
					<div class="two_col">
						<div class="column">
							<h3>Sign up today and get:</h3>
							<ul>
								<li>
									<p>Specialized Courses</p>
								</li>
								<li>
									<p>Step-by-step Learning Lessons</p>
								</li>
								<li>
									<p>Weekly Mentorship with Daric himself</p>
								</li>
								<li>
									<p>Weekly Livestream Classes</p>
								</li>
								<li>
									<p>Excslusive Interviews with Other Top Bassists</p>
								</li>
								<li>
									<p>One-to-one Feeback Classes</p>
								</li>
								<li>
									<p>Vibrant & Supportive Community</p>
								</li>
							</ul>
						</div>
						<div class="column">
							<div class="top">
								<h3>Cost Comparison</h3>
								<h4>Bass Nation Starts At</h4>
								<h5>Under 90&cent; per day</h3>
							</div>
							<div class="bottom">
								<a class="button yellow" href="/register">
									Start FREE Today!
									<span>
										<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
									</span>
								</a>
								<small>Free for 3 days, cancel anytime</small>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<section class="faq full_width">
			<div class="small_container">
				<h2>Frequently Asked Questions</h2>
				<div class="accordion" id="faq_accordion">
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
								What do the courses entail?
							</button>
						</h2>
						<div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
								<strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
							</div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
								What do the courses entail?
							</button>
						</h2>
						<div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
								<strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
							</div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
								What do the courses entail?
							</button>
						</h2>
						<div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
								<strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
							</div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
								What do the courses entail?
							</button>
						</h2>
						<div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
								<strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
							</div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="true" aria-controls="collapseFive">
								What do the courses entail?
							</button>
						</h2>
						<div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
								<strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the appropriate classes that we use to style each element. These classes control the overall appearance, as well as the showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables. It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does limit overflow.
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>

	
<?php get_footer(); ?>