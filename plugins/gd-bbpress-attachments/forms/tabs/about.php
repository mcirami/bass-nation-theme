<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = $options ?? array();

?>

<div class="d4p-information">
    <fieldset>
        <h3>GD bbPress Attachments <?php echo esc_html( $options["version"] ); ?></h3>
		<?php

		$status = ucfirst( $options["status"] );
		if ( $options["revision"] > 0 ) {
			$status .= " #" . $options["revision"];
		}

		esc_html_e( 'Release Date: ', 'gd-bbpress-attachments' );
		echo '<strong>' . esc_html( $options["date"] ) . '</strong><br/>';
		esc_html_e( 'Status: ', 'gd-bbpress-attachments' );
		echo '<strong>' . esc_html( $status ) . '</strong><br/>';
		esc_html_e( 'Build: ', 'gd-bbpress-attachments' );
		echo '<strong>' . esc_html( $options["build"] ) . '</strong>';

		?>
    </fieldset>

    <fieldset>
        <h3><?php esc_html_e( 'System Requirements', 'gd-bbpress-attachments' ); ?></h3>
		<?php

		esc_html_e( 'PHP: ', 'gd-bbpress-attachments' );
		echo '<strong>7.4 or newer</strong><br/>';
		esc_html_e( 'WordPress: ', 'gd-bbpress-attachments' );
		echo '<strong>5.9 or newer</strong><br/>';
		esc_html_e( 'bbPress: ', 'gd-bbpress-attachments' );
		echo '<strong>2.6.2 or newer</strong>';

		?>
    </fieldset>

    <fieldset>
        <h3><?php esc_html_e( 'Important Plugin Links', 'gd-bbpress-attachments' ); ?></h3>
        <a target="_blank" href="https://www.dev4press.com/plugins/gd-bbpress-attachments/">GD bbPress Attachments <?php esc_html_e( 'Home Page', 'gd-bbpress-attachments' ); ?></a><br/>
        <a target="_blank" href="https://wordpress.org/plugins/gd-bbpress-attachments/">GD bbPress Attachments <?php esc_html_e( 'on', 'gd-bbpress-attachments' ); ?> WordPress.org</a>
        <h3><?php esc_html_e( 'Plugin Support', 'gd-bbpress-attachments' ); ?></h3>
        <a target="_blank" href="https://support.dev4press.com/forums/forum/plugins-free/gd-bbpress-attachments/"><?php esc_html_e( 'Plugin Support Forum on Dev4Press', 'gd-bbpress-attachments' ); ?></a><br/>
        <h3><?php esc_html_e( 'Dev4Press Important Links', 'gd-bbpress-attachments' ); ?></h3>
        <a target="_blank" href="https://twitter.com/milangd">Dev4Press <?php esc_html_e( 'on', 'gd-bbpress-attachments' ); ?> Twitter</a><br/>
        <a target="_blank" href="https://www.facebook.com/dev4press">Dev4Press Facebook <?php esc_html_e( 'Page', 'gd-bbpress-attachments' ); ?></a><br/>
    </fieldset>
</div>
<div class="d4p-information-second">
	<?php include( GDBBPRESSATTACHMENTS_PATH . 'forms/more/toolbox.php' ); ?>
</div>
<div class="d4p-clear"></div>
<div class="d4p-copyright">
    Dev4Press &copy; 2008 - 2025
    <a target="_blank" href="https://www.dev4press.com/">www.dev4press.com</a>
</div>
