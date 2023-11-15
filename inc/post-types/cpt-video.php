<?php
function create_video_post_type() {
	register_post_type( 'videos',
		array(
			'labels' => array(
				'name' => __( 'Video Submit' ),
				'singular_name' => __( 'Video Submit' ),
				'add_new' => ('Add New Video'),
				'add_new_item' => ('Add New Video'),
				'edit_item' => ('Edit Video Submit'),
				'new_item' => ('New Video'),
				'view_item' => ('View Videos'),
				'search_items' => ('Search Videos'),
				'not_found' => ('No Video Submit found'),
				'not_found_in_trash' => ('No Video￼Submit found in Trash'),
				'parent_item_colon' => ('Parent Video:'),
				'menu_name' => ('Submitted Videos'),
			),
			'public' => true,
			'has_archive' => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'menu_icon' => get_template_directory_uri() . '/images/videos-icon.png',
			'supports' => array( 'title', 'editor', 'thumbnail', 'comments', 'author' ),
			'rewrite' => array( 'slug' => 'video-q-and-a' ),
			'show_in_rest' => true
		)
	);
}
add_action( 'init', 'create_video_post_type' );

?>