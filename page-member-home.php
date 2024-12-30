<?php
	/**
	 * Template Name: Member Home Page
	 *
	 * The template for displaying Member Pages.
	 *
	 *
	 * @package boiler
	 */
	/* phpinfo();
	 exit;*/
	get_header();
	
	
 	global $current_user;
 	wp_get_current_user();
 	$username = $current_user->user_login;
 	$url = site_url();
 	
 ?>
 	
	 	<section class="page_content full_width<?php if (is_user_logged_in()){ echo " member";} ?>">


		 	
			 	<header class="sub_header full_width">
					 <div class="container">
							<h2>Welcome To Daric Bennett's Bass Nation!</h2>
					</div><!-- .container -->
				 </header>
		    <?php if (pmpro_hasMembershipLevel()) : ?>
			 	<div class="container">

				 	<div class="member_home full_width">
						<div class="full_width top_section light_gray_bg">
							<div class="video_column">
								<div class="video_wrapper">
									<iframe src="https://player.vimeo.com/video/1006352212?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" allowfullscreen></iframe>
								</div>
							</div>
							<div class="text_column">
								<h3>I'm so glad you're here and super excited to help you skyrocket your bass playing to the next level!</h3>
								<p>Now that you are a member, you have <span>FULL ACCESS</span> to:</p>
								<ul>
									<li>
										<a href="/lessons">
											COMPLETE LESSONS
										</a>
									</li>
									<li>
										<a href="/courses">IN-DEPTH COURSES</a>
									</li>
									<li>
										<a href="/forums">
											INTERACTIVE FORUMS
										</a>
									</li>
									<li>
										<a href="/live-streams">
											LIVE SESSIONS
										</a>
									</li>
								</ul>
								<div class="bottom_text">
									<p>Plus, you have the opportunity to ask me anything!</p>
									<p>So explore the site and be on your way to mastering the bass faster than you ever thought possible!</p>
								</div>
							</div>
						</div>
						<div class="full_width bottom_section">
							<div class="heading full_width">
								<h3>Quick Links</h3>
							</div>
							<div class="content_wrap">
								<div class="info_wrap account">
									<div class="icon_wrap">
										<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/account-icon.png" alt="Profile Icon"/>
									</div>
									<div class="column_text">
										<h4>Account</h4>
										<div class="links">
											<a href="<?php echo home_url();?>/your-profile">Edit Profile</a>
											<a href="<?php echo home_url();?>/my-inbox/?fepaction=messagebox&fep-filter=inbox">My Inbox</a>
										</div>
									</div>
								</div>
								<div class="info_wrap">
									<div class="icon_wrap">
										<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/instruction-icon.png" alt="Lessons Icon"/>
									</div>
									<div class="column_text">
										<h4>Instruction</h4>
										<div class="links">
											<a href="/lessons">Member Lessons</a>
											<a href="/courses">Courses</a>
										</div>
									</div>
								</div>
								<div class="info_wrap forums">
									<div class="icon_wrap forums">
										<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/forums-icon.png" alt="Forum Icon"/>
									</div>	
									<div class="column_text">
										<h4>Forums</h4>
										<div class="links">
											<a href="/forums">Forum Home</a>
											<a href="<?php echo $url; ?>/forums/forum/darics-throwdown/">Daric's Throwdown</a>
										</div>
									</div>
								</div>
							</div>
						</div>
				 	</div>
				 	<?php // endif; ?>
			    </div><!-- .container -->

				 <?php else :
				 
				 	get_template_part( 'template-parts/content', 'not-member' );
				 	
				endif; ?>

	 	</section>
	 
 <?php get_footer(); ?>