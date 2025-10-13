<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( isset( $_GET["settings-updated"] ) && sanitize_text_field( $_GET["settings-updated"] ) == "true" ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash ?>
    <div class="updated">
        <p><strong><?php esc_html_e( 'Settings saved.', 'gd-bbpress-attachments' ); ?></strong></p>
    </div>
<?php } ?>

<form action="" method="post">
	<?php wp_nonce_field( "gd-bbpress-attachments" ); ?>
    <div class="d4p-settings">
        <fieldset>
            <h3><?php esc_html_e( 'Error logging', 'gd-bbpress-attachments' ); ?></h3>
            <p><?php esc_html_e( 'Each failed upload will be logged in postmeta table. Administrators and topic/reply authors can see the log.', 'gd-bbpress-attachments' ); ?></p>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="log_upload_errors"><?php esc_html_e( 'Activated', 'gd-bbpress-attachments' ); ?></label></th>
                    <td>
                        <input type="checkbox" <?php if ( $options["log_upload_errors"] == 1 ) {
							echo " checked";
						} ?> id="log_upload_errors" name="log_upload_errors"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="errors_visible_to_admins"><?php esc_html_e( 'Visible to administrators', 'gd-bbpress-attachments' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" <?php if ( $options["errors_visible_to_admins"] == 1 ) {
							echo " checked";
						} ?> id="errors_visible_to_admins" name="errors_visible_to_admins"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="errors_visible_to_moderators"><?php esc_html_e( 'Visible to moderators', 'gd-bbpress-attachments' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" <?php if ( $options["errors_visible_to_moderators"] == 1 ) {
							echo " checked";
						} ?> id="errors_visible_to_moderators" name="errors_visible_to_moderators"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="errors_visible_to_author"><?php esc_html_e( 'Visible to author', 'gd-bbpress-attachments' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" <?php if ( $options["errors_visible_to_author"] == 1 ) {
							echo " checked";
						} ?> id="errors_visible_to_author" name="errors_visible_to_author"/>
                    </td>
                </tr>
                </tbody>
            </table>
        </fieldset>

        <fieldset>
            <h3><?php esc_html_e( 'Deleting attachments', 'gd-bbpress-attachments' ); ?></h3>
            <p><?php esc_html_e( 'Once uploaded and attached, attachments can be deleted. Only administrators and authors can do this.', 'gd-bbpress-attachments' ); ?></p>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label for="delete_visible_to_admins"><?php esc_html_e( 'Administrators', 'gd-bbpress-attachments' ); ?></label></th>
                    <td>
                        <select id="delete_visible_to_admins" name="delete_visible_to_admins" class="widefat">
                            <option value="no"<?php if ( $options["delete_visible_to_admins"] == "no" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Don\'t allow to delete', 'gd-bbpress-attachments' ); ?></option>
                            <option value="delete"<?php if ( $options["delete_visible_to_admins"] == "delete" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Delete from Media Library', 'gd-bbpress-attachments' ); ?></option>
                            <option value="detach"<?php if ( $options["delete_visible_to_admins"] == "detach" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Only detach from topic/reply', 'gd-bbpress-attachments' ); ?></option>
                            <option value="both"<?php if ( $options["delete_visible_to_admins"] == "both" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Allow both delete and detach', 'gd-bbpress-attachments' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="delete_visible_to_moderators"><?php esc_html_e( 'Moderators', 'gd-bbpress-attachments' ); ?></label></th>
                    <td>
                        <select id="delete_visible_to_moderators" name="delete_visible_to_moderators" class="widefat">
                            <option value="no"<?php if ( $options["delete_visible_to_moderators"] == "no" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Don\'t allow to delete', 'gd-bbpress-attachments' ); ?></option>
                            <option value="delete"<?php if ( $options["delete_visible_to_moderators"] == "delete" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Delete from Media Library', 'gd-bbpress-attachments' ); ?></option>
                            <option value="detach"<?php if ( $options["delete_visible_to_moderators"] == "detach" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Only detach from topic/reply', 'gd-bbpress-attachments' ); ?></option>
                            <option value="both"<?php if ( $options["delete_visible_to_moderators"] == "both" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Allow both delete and detach', 'gd-bbpress-attachments' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="delete_visible_to_author"><?php esc_html_e( 'Author', 'gd-bbpress-attachments' ); ?></label></th>
                    <td>
                        <select id="delete_visible_to_author" name="delete_visible_to_author" class="widefat">
                            <option value="no"<?php if ( $options["delete_visible_to_author"] == "no" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Don\'t allow to delete', 'gd-bbpress-attachments' ); ?></option>
                            <option value="delete"<?php if ( $options["delete_visible_to_author"] == "delete" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Delete from Media Library', 'gd-bbpress-attachments' ); ?></option>
                            <option value="detach"<?php if ( $options["delete_visible_to_author"] == "detach" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Only detach from topic/reply', 'gd-bbpress-attachments' ); ?></option>
                            <option value="both"<?php if ( $options["delete_visible_to_author"] == "both" ) {
								echo ' selected="selected"';
							} ?>><?php esc_html_e( 'Allow both delete and detach', 'gd-bbpress-attachments' ); ?></option>
                        </select>
                    </td>
                </tr>
                </tbody>
            </table>
        </fieldset>

        <p class="submit">
            <input type="submit" value="<?php esc_html_e( 'Save Changes', 'gd-bbpress-attachments' ); ?>" class="button-primary gdbb-tools-submit" id="gdbb-att-advanced-submit" name="gdbb-att-advanced-submit"/>
        </p>
    </div>
    <div class="d4p-settings-second">
		<?php include( GDBBPRESSATTACHMENTS_PATH . 'forms/more/toolbox.php' ); ?>
    </div>

    <div class="d4p-clear"></div>
</form>
