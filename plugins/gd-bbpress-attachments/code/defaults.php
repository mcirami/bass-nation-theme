<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDATTDefaults {
	var $default_options = array(
		'version'                      => '4.9',
		'date'                         => '2025.05.31.',
		'build'                        => 2800,
		'status'                       => 'Stable',
		'product_id'                   => 'gd-bbpress-attachments',
		'edition'                      => 'free',
		'revision'                     => 0,
		'grid_topic_counter'           => 1,
		'grid_reply_counter'           => 1,
		'delete_attachments'           => 'detach',
		'include_always'               => 1,
		'hide_from_visitors'           => 1,
		'max_file_size'                => 512,
		'max_to_upload'                => 4,
		'roles_to_upload'              => null,
		'attachment_icon'              => 1,
		'attachment_icons'             => 1,
		'image_thumbnail_active'       => 1,
		'image_thumbnail_inline'       => 0,
		'image_thumbnail_caption'      => 1,
		'image_thumbnail_rel'          => 'lightbox',
		'image_thumbnail_css'          => '',
		'image_thumbnail_size_x'       => 128,
		'image_thumbnail_size_y'       => 72,
		'log_upload_errors'            => 1,
		'errors_visible_to_admins'     => 1,
		'errors_visible_to_moderators' => 1,
		'errors_visible_to_author'     => 1,
		'delete_visible_to_admins'     => 'both',
		'delete_visible_to_moderators' => 'no',
		'delete_visible_to_author'     => 'no',
	);

	public function __construct() {
	}
}
