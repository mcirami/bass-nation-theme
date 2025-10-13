<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( isset( $_GET["settings-updated"] ) && sanitize_text_field( $_GET["settings-updated"]) === "true" ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash ?>
    <div class="updated">
        <p><strong><?php esc_html_e( 'Settings saved.', 'gd-bbpress-attachments' ); ?></strong></p>
    </div>
<?php } ?>

<form action="" method="post">
	<?php wp_nonce_field( "gd-bbpress-attachments" ); ?>
    <div class="d4p-settings">
        <fieldset>
            <h3><?php esc_html_e( 'Display of image attachments', 'gd-bbpress-attachments' ); ?></h3>
            <p><?php esc_html_e( 'Attached images can be displayed as thumbnails, and from here you can control this.', 'gd-bbpress-attachments' ); ?></p>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="image_thumbnail_active"><?php esc_html_e( 'Activated', 'gd-bbpress-attachments' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" <?php if ( $options["image_thumbnail_active"] == 1 ) {
							echo " checked";
						} ?> id="image_thumbnail_active" name="image_thumbnail_active"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="image_thumbnail_caption"><?php esc_html_e( 'With caption', 'gd-bbpress-attachments' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" <?php if ( $options["image_thumbnail_caption"] == 1 ) {
							echo " checked";
						} ?> id="image_thumbnail_caption" name="image_thumbnail_caption"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="image_thumbnail_inline"><?php esc_html_e( 'In line', 'gd-bbpress-attachments' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" <?php if ( $options["image_thumbnail_inline"] == 1 ) {
							echo " checked";
						} ?> id="image_thumbnail_inline" name="image_thumbnail_inline"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="image_thumbnail_css"><?php esc_html_e( 'CSS class', 'gd-bbpress-attachments' ); ?></label>
                    </th>
                    <td>
                        <input type="text" class="widefat" value="<?php echo esc_attr( $options["image_thumbnail_css"] ); ?>" id="image_thumbnail_css" name="image_thumbnail_css"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="image_thumbnail_rel"><?php esc_html_e( 'REL attribute', 'gd-bbpress-attachments' ); ?></label>
                    </th>
                    <td>
                        <input type="text" class="widefat" value="<?php echo esc_attr( $options["image_thumbnail_rel"] ); ?>" id="image_thumbnail_rel" name="image_thumbnail_rel"/><br/>
                        <em><?php esc_html_e( 'You can use these tags', 'gd-bbpress-attachments' ); ?>: %ID%, %TOPIC%</em>
                    </td>
                </tr>
                </tbody>
            </table>
        </fieldset>

        <fieldset>
            <h3><?php esc_html_e( 'Image thumbnails size', 'gd-bbpress-attachments' ); ?></h3>
            <p><?php esc_html_e( 'Changing thumbnails size affects only new image attachments. To use new size for old attachments, resize them using', 'gd-bbpress-attachments' ); ?>
                <a href="https://wordpress.org/plugins/regenerate-thumbnails/" target="_blank">Regenerate Thumbnails</a> <?php esc_html_e( 'plugin', 'gd-bbpress-attachments' ); ?>.
            </p>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="image_thumbnail_size_x"><?php esc_html_e( 'Thumbnail size', 'gd-bbpress-attachments' ); ?></label>
                    </th>
                    <td>x:</td>
                    <td>
                        <input step="1" min="1" type="number" class="widefat small-text" value="<?php echo esc_attr( $options["image_thumbnail_size_x"] ); ?>" id="image_thumbnail_size_x" name="image_thumbnail_size_x"/>
                        <span class="description">px</span>
                    </td>
                    <td>y:</td>
                    <td>
                        <input step="1" min="1" type="number" class="widefat small-text" value="<?php echo esc_attr( $options["image_thumbnail_size_y"] ); ?>" id="image_thumbnail_size_y" name="image_thumbnail_size_y"/>
                        <span class="description">px</span>
                    </td>
                </tr>
                </tbody>
            </table>
        </fieldset>

        <p class="submit">
            <input type="submit" value="<?php esc_html_e( 'Save Changes', 'gd-bbpress-attachments' ); ?>" class="button-primary gdbb-tools-submit" id="gdbb-att-images-submit" name="gdbb-att-images-submit"/>
        </p>
    </div>
    <div class="d4p-settings-second">
		<?php include( GDBBPRESSATTACHMENTS_PATH . 'forms/more/toolbox.php' ); ?>
    </div>

    <div class="d4p-clear"></div>
</form>
