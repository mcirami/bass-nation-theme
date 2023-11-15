<?php
function create_courses_post_type() {
	register_post_type( 'courses',
		array(
			'labels' => array(
				'name' => __( 'Courses' ),
				'singular_name' => __( 'Course' ),
				'add_new' => ('Add New Course'),
				'add_new_item' => ('Add New Course'),
				'edit_item' => ('Edit Course'),
				'new_item' => ('New Course'),
				'view_item' => ('View Courses'),
				'search_items' => ('Search Courses'),
				'not_found' => ('No Course found'),
				'not_found_in_trash' => ('No Course￼found in Trash'),
				'parent_item_colon' => ('Parent Course:'),
				'menu_name' => ('Courses'),
			),
			'public' => true,
			'has_archive' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'capability_type'    => 'post',
			'hierarchical'       => true,
			'menu_icon' => get_template_directory_uri() . '/images/courses-icon.png',
			'supports' => array( 'title', 'editor', 'thumbnail', 'comments', 'author' ),
			//'rewrite' => array( 'slug' => 'courses' ),
			'show_in_rest' => true
		)
	);
}
add_action( 'init', 'create_courses_post_type' );
