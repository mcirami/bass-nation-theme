<?php
function create_live_stream_post_type() {
	register_post_type( 'live-streams',
		array(
			'labels' => array(
				'name' => __( 'Live Streams' ),
				'singular_name' => __( 'Live Stream' ),
				'add_new' => ('Add New Stream'),
				'add_new_item' => ('Add New Stream'),
				'edit_item' => ('Edit Stream'),
				'new_item' => ('New Stream'),
				'view_item' => ('View Streams'),
				'search_items' => ('Search Streams'),
				'not_found' => ('No Stream found'),
				'not_found_in_trash' => ('No Stream found in Trash'),
				'parent_item_colon' => ('Parent Stream:'),
				'menu_name' => ('Live Streams'),
			),
			'public' => true,
			'has_archive' => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'menu_icon' => get_template_directory_uri() . '/images/live-stream-icon.png',
			'supports' => array( 'title', 'editor', 'thumbnail', 'comments', 'author' ),
			'rewrite' => array( 'slug' => 'live-stream' ),
			'show_in_rest' => true
		)
	);
}
add_action( 'init', 'create_live_stream_post_type' );
?>