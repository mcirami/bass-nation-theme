<?php
function create_lesson_post_type() {
	register_post_type( 'lessons',
		array(
			'labels' => array(
				'name' => __( 'Lessons' ),
				'singular_name' => __( 'Lesson' ),
				'add_new' => ('Add New Lesson'),
				'add_new_item' => ('Add New Lesson'),
				'edit_item' => ('Edit Lesson'),
				'new_item' => ('New Lesson'),
				'view_item' => ('View Lessons'),
				'search_items' => ('Search Lessons'),
				'not_found' => ('No Lesson found'),
				'not_found_in_trash' => ('No Lesson￼found in Trash'),
				'parent_item_colon' => ('Parent Lesson:'),
				'menu_name' => ('Lessons'),
			),
			'public' => true,
			'has_archive' => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'menu_icon' => get_template_directory_uri() . '/images/bass-icon.png',
			'supports' => array( 'title', 'editor', 'thumbnail', 'comments', 'author' ),
			'rewrite' => array( 'slug' => 'free-bass-lessons' ),
			'show_in_rest' => true
		)
	);
}
add_action( 'init', 'create_lesson_post_type' );

?>