
<div class="not_member full_width">
	<div class="container">
		<h2> You Must Be A Member To View This Page...</h2>

		<div class="full_width content_wrap">
			<div class="column <?php if (is_user_logged_in()) : echo 'full_width'; endif;?>">
				<div class="full_width heading">
					<h3>Not A Member? <span>Join Free Today!</span></h3>
				</div>
				<div class="full_width top_section">
					<h4>Monthly Membership Includes</h4>
					<h4>Totaly Free for 3 Days,</h4>
					<p>then only <span>$9.99/mo</span> after that!</p>
					<div class="button_wrap">
						<?php $buttonUrl = is_user_logged_in() ? '/membership-account/membership-levels/' : '/register'; ?>
						<a class="button round_button" href="<?php echo $buttonUrl; ?>">Start My 3 Day Free Trial Now!</a>
					</div>
				</div>
				<div class="bottom_section full_width">
					<div class="full_width heading">
						<h3>Your 3 Day Free Trial Includes All of the Following</h3>
					</div>
					<div class="full_width content">
						<ul class="full_width">
							<li><p>Unlimited full access to over 150 Bass Lessons!</p></li>
							<li><p>6 Multi-Part Full Bass Courses Ranging from Ultra Beginner to Advanced Slap Techniques</p></li>
							<li><p>Interviews With Amazing Bass Players</p></li>
							<li><p>Weekly Broadcasted Live Streams with Daric</p></li>
							<li><p>Full Bass Forum Access to Chat with Other Bass Players Around the World!</p></li>
							<li><p>Video Q & A Section to Post Any Bass Related Questions for Video Format Replies from Daric!</p></li>
						</ul>
					</div>

				</div>
			</div>

			<?php if (!is_user_logged_in()) : ?>
				<div class="column guest">
					<h3>Already A Member? <span>Login Below</span></h3>
					<?php echo do_shortcode('[pmpro_login]'); ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
	setcookie("postedArticle", true, time() + (60 * 20)); // 60 seconds ( 1 minute) * 20 = 20 minutes
?>
<script>

	const redirectURL = window.location.href;

	createCookie("login_redirect", redirectURL, 5);

	function createCookie(name, value, minutes) {

		var expires;

		if (minutes) {
			var date = new Date();
			date.setTime(date.getTime() + (minutes * 60 * 1000));
			expires = "; expires=" + date.toGMTString();
		}
		else {
			expires = "";
		}
		document.cookie = name + "=" + value + expires + "; path=/";
	}
</script>