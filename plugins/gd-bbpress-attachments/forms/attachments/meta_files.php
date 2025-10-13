<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_ID   = $post_ID ?? 0;
$user_ID   = $user_ID ?? 0;
$author_id = $author_id ?? 0;

$attachments = d4p_get_post_attachments( $post_ID );

if ( empty( $attachments ) ) {
	esc_html_e( 'No attachments here.', 'gd-bbpress-attachments' );
} else {
	echo '<ul style="list-style: decimal outside; margin-left: 1.5em;">';
	foreach ( $attachments as $attachment ) {
		$file     = get_attached_file( $attachment->ID );
		$filename = pathinfo( $file, PATHINFO_BASENAME );
		echo '<li>' . esc_html( $filename );
		echo ' - <a href="' . admin_url( 'media.php?action=edit&attachment_id=' . absint( $attachment->ID ) ) . '">' . __( 'edit', 'gd-bbpress-attachments' ) . '</a>';  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</li>';
	}
	echo '</ul>';
}

if ( ( d4p_bba_o( "errors_visible_to_author" ) == 1 && $author_id == $user_ID ) || ( d4p_bba_o( "errors_visible_to_admins" ) == 1 && d4p_is_user_admin() ) || ( d4p_bba_o( "errors_visible_to_moderators" ) == 1 && d4p_is_user_moderator() ) ) {
	$errors = get_post_meta( $post_ID, "_bbp_attachment_upload_error" );
	if ( ! empty( $errors ) ) {
		echo '<h4>' . esc_html__( 'Upload Errors', 'gd-bbpress-attachments' ) . ':</h4>';
		echo '<ul style="list-style: decimal outside; margin-left: 1.5em;">';
		foreach ( $errors as $error ) {
			echo '<li><strong>' . esc_html( $error["file"] ) . '</strong>:<br/>' . esc_html__( $error["message"], "gd-bbpress-attachments" ) . '</li>';  //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}
		echo '</ul>';
	}
}
