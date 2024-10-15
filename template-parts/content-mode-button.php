<div id="db_mode_button">
	<div class="toggle-radio">
		<input type="radio" name="rdo" id="dark" 
		<?php if ($_COOKIE["db_dark_mode"] == "dark") {
			echo "checked";
		} ?>>
		<input type="radio" name="rdo" id="light" 
		<?php if ($_COOKIE["db_dark_mode"] == "light" || !isset($_COOKIE["db_dark_mode"])) {
			echo "checked";
		} ?>>
		<div class="switch">
			<label for="dark"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/moon.svg" alt="Bass Nation Logo"/></label>
			<label for="light"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/sun.svg" alt="Bass Nation Logo"/></label>
			<span></span>
		</div>
	</div>
</div>	