<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'attachments'; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

$tabs = array(
	'attachments' => '<span class="dashicons dashicons-admin-settings" title="' . esc_attr__( 'Settings', 'gd-bbpress-attachments' ) . '"></span><span class="tab-title"> ' . esc_html__( 'Settings', 'gd-bbpress-attachments' ) . '</span>',
	'images'      => '<span class="dashicons dashicons-images-alt" title="' . esc_attr__( 'Images', 'gd-bbpress-attachments' ) . '"></span><span class="tab-title"> ' . esc_html__( 'Images', 'gd-bbpress-attachments' ) . '</span>',
	'advanced'    => '<span class="dashicons dashicons-archive" title="' . esc_attr__( 'Advanced', 'gd-bbpress-attachments' ) . '"></span><span class="tab-title"> ' . esc_html__( 'Advanced', 'gd-bbpress-attachments' ) . '</span>',
	'tools'       => '<span class="dashicons dashicons-admin-tools" title="' . esc_attr__( 'Tools', 'gd-bbpress-attachments' ) . '"></span><span class="tab-title"> ' . esc_html__( 'Tools', 'gd-bbpress-attachments' ) . '</span>',
	'd4p'         => '<span class="dashicons dashicons-flag" title="' . esc_attr__( 'Dev4Press', 'gd-bbpress-attachments' ) . '"></span><span class="tab-title"> ' . esc_html__( 'Dev4Press', 'gd-bbpress-attachments' ) . '</span>',
	'about'       => '<span class="dashicons dashicons-info" title="' . esc_attr__( 'About', 'gd-bbpress-attachments' ) . '"></span><span class="tab-title"> ' . esc_html__( 'About', 'gd-bbpress-attachments' ) . '</span>',
);

if ( ! isset( $tabs[ $current ] ) ) {
	$current = 'attachments';
}

?>
<div class="wrap">
    <h2>GD bbPress Attachments</h2>
    <div id="icon-upload" class="icon32"><br></div>
    <h2 class="nav-tab-wrapper d4p-tabber-ctrl">
		<?php

		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab == $current ) ? ' nav-tab-active' : '';

			if ( $tab == 'toolbox' ) {
				$class .= ' d4p-tab-toolbox';
			}

			echo '<a class="nav-tab' . esc_attr( $class ) . '" href="edit.php?post_type=forum&page=gdbbpress_attachments&tab=' . esc_attr( $tab ) . '">' . $name . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		?>
    </h2>
    <div id="d4p-panel" class="d4p-panel-<?php echo esc_attr( $current ); ?>">
		<?php include( GDBBPRESSATTACHMENTS_PATH . "forms/tabs/" . esc_attr( $current ) . ".php" ); ?>
    </div>
</div>
