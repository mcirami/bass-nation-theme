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
					 	<!-- <?php if ($current_user->user_firstname !== '') : ?>
				 			<h2> Welcome To Daric Bennett's Bass Nation, <?php echo $current_user->user_firstname; ?>!</h2>
				 		<?php else : ?>
				 			<h2>Welcome To Daric Bennett's Bass Nation!</h2>
				 		<?php endif; ?> -->
					    <!--<div class="fb-group" style="margin: 20px 0; max-width: 100%;" data-href="https://www.facebook.com/groups/3889857837810354/" data-width="500" data-show-social-context="false" data-show-metadata="true">
						    <blockquote cite="https://www.facebook.com/groups/3889857837810354/" class="fb-xfbml-parse-ignore">Bass Nation Fanatics</blockquote>
					    </div>-->
				 		<div class="content_wrap full_width">
							 <div class="full_width top_section">
								<div class="video_column">
									<div class="video_wrapper">
										<iframe src="https://player.vimeo.com/video/1006352212?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479" allowfullscreen></iframe>
									</div>
								</div>
								<div class="text_column">
									<h4>I'm so glad you're here and super excited to help you <span>skyrocket your bass playing</span> to the next level!</h4>
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
									<p>Plus, you have the opportunity to ask me anything!</p>
									<p>So explore the site and be on your way to mastering the bass faster than you ever thought possible!</p>
								</div>
							 </div>
					 		<div class="full_width bottom_section">
						 		<div class="heading full_width">
							 		<h3>Quick Links</h3>
						 		</div>
								<div class="content_wrap">
									<div class="row full_width">
										<div class="info_wrap account">
											<div class="column_title">
												<div class="icon_wrap">
													<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/icon-account.svg" alt="Profile Icon"/>
												</div>
												<h4>Account</h4>
											</div>
											<a href="<?php echo home_url();?>/your-profile">Edit Profile</a>
											<a href="<?php echo home_url();?>/my-inbox/?fepaction=messagebox&fep-filter=inbox">My Inbox</a>
										</div>
									</div>
									<div class="row full_width">
										<div class="info_wrap">
											<div class="column_title">
												<div class="icon_wrap">
													<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/icon-instruction.svg" alt="Lessons Icon"/>
												</div>
												<h4>Instruction</h4>
											</div>
											<a href="/lessons">Member Lessons</a>
											<a href="/courses">Courses</a>
										</div>
									</div>
									<div class="row full_width">
										<div class="info_wrap forums">
											<div class="column_title">
												<div class="icon_wrap forums">
													<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/icon-forums.svg" alt="Forum Icon"/>
												</div>	
												<h4>Forums</h4>
											</div>
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