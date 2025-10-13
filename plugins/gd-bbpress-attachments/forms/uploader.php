<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$file_size = $file_size ?? 512;

?>

<fieldset class="bbp-form">
    <legend><?php esc_html_e( 'Upload Attachments', 'gd-bbpress-attachments' ); ?></legend>
    <div class="bbp-template-notice">
        <p><?php

			$size = $file_size < 1024 ? $file_size . " KB" : floor( $file_size / 1024 ) . " MB";

	        /* translators: 1: Allowed size value. */
			printf( esc_html__( 'Maximum file size allowed is %s.', 'gd-bbpress-attachments' ), $size ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			?></p>
    </div>
    <p class="bbp-attachments-form">
        <label for="bbp_topic_tags">
			<?php esc_html_e( 'Attachments', 'gd-bbpress-attachments' ); ?>:
        </label><br/>
        <input type="file" size="40" name="d4p_attachment[]"><br/>
        <a class="d4p-attachment-addfile" href="#"><?php esc_html_e( 'Add another file', 'gd-bbpress-attachments' ); ?></a>
    </p>
</fieldset>