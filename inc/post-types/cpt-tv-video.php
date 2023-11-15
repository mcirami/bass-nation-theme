<?php
function create_tv_video_post_type() {
	register_post_type( 'tv-videos',
		array(
			'labels' => array(
				'name' => __( 'TV Videos' ),
				'singular_name' => __( 'TV Video' ),
				'add_new' => ('Add New Video'),
				'add_new_item' => ('Add New Video'),
				'edit_item' => ('Edit Video'),
				'new_item' => ('New Video'),
				'view_item' => ('View Videos'),
				'search_items' => ('Search TV Videos'),
				'not_found' => ('No TV Video found'),
				'not_found_in_trash' => ('No TV Video found in Trash'),
				'parent_item_colon' => ('Parent Video:'),
				'menu_name' => ('TV Videos'),
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
			'rewrite' => array( 'slug' => 'bass-nation-tv' ),
			'show_in_rest' => true
		)
	);
}
add_action( 'init', 'create_tv_video_post_type' );

?>