<?php
/**
 * Template Name: Two Column Layout
 *
 * The template for displaying Two Column Layout page like Contact us.
 *
 *
 * @package boiler
 */


get_header();

$referer = isset($_GET['referer']) ? $_GET['referer'] : null;

?>
 	<section class="two_column_template full_width page_content <?php echo $pagename; ?> <?php echo is_user_logged_in() ? "member" : ""; ?>">
		<div class="container">
			<header class="sub_header full_width">
                <?php if ($referer) : ?>
                    <h2>Welcome To Bass Nation!</h2>
                <?php else: ?>
 				    <h2><?php echo the_title(); ?></h2>
                <?php endif; ?>
			</header>
		 </div><!-- .container -->

		<section class="two_column_section full_width">
			<div class="container">
                <?php if ($referer) : ?>
                    <div class="confirmation_text">
                        <h3 class="lh-lg">
                            Thanks for joining our email list and taking the first step towards becoming a better bass player! </br> We’re excited to have you in the Bass Nation community.
                        </h3>
                       <div class="list_wrap">
                            <p>
                                Ready to Level Up Your Skills?
                            </p>
                            <ul class="check_list mb-5">
                                <li>
                                    <p>
                                        <strong>Step-by-Step Lessons:</strong> Master the essentials or sharpen advanced techniques with our comprehensive video lessons.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Expert Feedback:</strong> Get personalized tips from pro bassists who’ve been exactly where you are now.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Community Support:</strong> Connect with fellow bass players from around the world, share progress, and stay motivated.
                                    </p>
                                </li>
                                <li>
                                    <p>
                                        <strong>Exclusive Resources:</strong> Discover specialized practice routines, downloadable tabs, and insider guides you won’t find anywhere else.
                                    </p>
                                </li>
                            </ul>
                       </div>
                        <p>
                            Imagine yourself grooving to your favorite tunes with total confidence.
                        </p>
                        <p class="lh-lg">
                            Whether you’re just picking up the bass for the first time or looking to refine advanced techniques, 
                            <strong>joining Bass Nation</strong> gives you all the tools you need to groove with confidence. 
                            Take your playing to the next level—don’t settle for slow progress!
                        </p>
                        <h3 class="text-center my-5 text-uppercase lh-lg">
                            Register below and start learning today. </br> We can’t wait to see you inside the members area!
                        </h3>
                    </div>
                <?php endif; ?>
				<div class="columns_wrap">
                <?php if (str_contains(strtolower($pagename), 'register')) : ?>
                        <div class="column">
                            <div class="numbered_list">
                                <div class="list_row">
                                    <div class="outer_circle">
                                        <span>1</span>
                                    </div>
                                    <p>Register for an account.</p>
                                </div>
                                <div class="list_row">
                                    <div class="outer_circle">
                                        <span>2</span>
                                    </div>
                                    <p>Verify your email address.</p>
                                </div>
                                <div class="list_row">
                                    <div class="outer_circle">
                                        <span>3</span>
                                    </div>
                                    <p>Choose a membership level!</p>
                                </div>
                            </div>
                            <!-- <h3>You will be on your way to skyrocketing your bass playing for less than $0.40/day!</h3> -->
                        </div>
                    <?php endif; ?>
					<div class="column">
                        <?php if($pagename == "login") : ?>
                            <?php echo the_content(); ?>
                        <?php elseif (str_contains(strtolower($pagename), 'register')) : ?>
                            <?php echo do_shortcode('[register role="author"]');
                            ?>
                        <?php else: ?>   
                            <h3><?php the_field('heading_text'); ?></h3>
                            <p><?php the_field('description'); ?></p>
                            <?php the_field('form_shortcode'); ?>
                        <?php endif; ?>
					</div>
					<div class="column">
						<div class="content_wrap">
                            <?php if($pagename == "login") : ?>
                                <h2>Don't Have An Account Yet?</h2>
                                <p>We've crafted a comprehensive platform to help you become the bassist you've always wanted to be</p>
                                <h3>Join Now! <span>FREE</span> For 3 Days!</h3>
                                <a class="button yellow" href="/register">
                                    Join Now!
                                    <span>
                                        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
                                    </span>
                                </a>
                            <?php elseif (str_contains(strtolower($pagename), 'register')) : ?>
                                <div class="video_wrapper full_width">
                                    <iframe src="https://player.vimeo.com/video/1006352212?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" frameborder="0"></iframe>
                                </div>
                            <?php else: ?>
                                <h2>Hey! Don't Let <span>Your Bass Journey</span> End Here!</h2>
                                <p>We've crafted a comprehensive platform to help you become the bassist you've always wanted to be</p>
                                <h3>Not convinced yet? Join my e-mail list for freee!</h3>
                                <a class="button yellow fancybox" data-fancybox href="#" data-src="#email_join">
                                    Join Now!
                                    <span>
                                        <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
                                    </span>
                                </a>
                            <?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</section>
    </section>

<?php get_footer(); ?>