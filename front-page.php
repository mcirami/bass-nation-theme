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
							<p>Daric Bennett's bass lessons are simply life-changing! As a beginner, I was worried about keeping up, but his teaching style is so clear and motivating that I quickly gained confidence. Now I can play along with my favorite songs, and it feels incredible! Daric truly makes learning bass enjoyable.</p>
							<div class="col_footer">
								<h5>JakeM</h5>
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
							<p>I've been playing bass for years, but Daric Bennett's lessons took my skills to a whole new level. His detailed breakdowns and techniques are second to none. I appreciate how he makes complex concepts easy to understand, and I’m noticing huge improvements in my groove and timing. Totally worth it!</p>
							<div class="col_footer">
								<h5>GrooveQueen44</h5>
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
							<p>A game-changer! The structure is perfect for both beginners and seasoned players. His energy is contagious, and his ability to explain the 'why' behind each technique keeps me coming back for more. It’s like having a personal coach at my fingertips. I highly recommend it to anyone serious about bass!</p>
							<div class="col_footer">
								<h5>KevyKevT</h5>
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
							<p>There are so many options for bass lessons these days, but Daric Bennett exceeded all my expectations! His step-by-step approach makes learning smooth, and I love how he focuses on both theory and practical application. Whether you're new or experienced, these lessons will make you a better player.</p>
							<div class="col_footer">
								<h5>Jordan4Funk</h5>
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
									<p>One-to-one Feedback Classes</p>
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
								What level of experience do I need to start bass lessons with Daric Bennett?
							</button>
						</h2>
						<div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
							Daric’s lessons cater to all levels, from complete beginners to advanced players. Whether you’re just picking up the bass or looking to refine your skills, you’ll find lessons tailored to your needs.
							</div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
								What equipment do I need to take online bass lessons?
							</button>
						</h2>
						<div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
								All you need is a bass guitar, an internet connection, and access to the lesson platform on daricbennett.com. A good pair of headphones and a bass amplifier can also enhance your learning experience, but they aren’t required to get started.
							</div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
								How are the courses structured?
							</button>
						</h2>
						<div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
								The courses are designed to build progressively, starting with the fundamentals and advancing to more complex techniques. Each lesson includes video demonstrations, practice exercises, and explanations to ensure you fully grasp the material at your own pace.
							</div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="true" aria-controls="collapseFour">
								Can I learn at my own pace, or do I have to follow a set schedule?
							</button>
						</h2>
						<div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
								The lessons are fully self-paced, allowing you to learn whenever and wherever it suits you. You can revisit lessons as often as needed to master specific techniques and concepts.
							</div>
						</div>
					</div>
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="true" aria-controls="collapseFive">
								Will the lessons cover both theory and practical skills?
							</button>
						</h2>
						<div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#faq_accordion">
							<div class="accordion-body">
								Yes! Daric’s lessons balance theory and practical application. You’ll not only learn how to play but also understand the music theory behind the techniques, giving you a deeper understanding of the bass guitar and how to create music.
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>

	
<?php get_footer(); ?>