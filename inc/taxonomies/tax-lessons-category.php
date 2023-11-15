<?php
function lessons_cat_taxonomy() {
	register_taxonomy(
		'category',  //The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
		'lessons',  //post type name
		array(
			'hierarchical' => true,
			'label' => 'Category',  //Display name
			'query_var' => true,
			'show_in_rest' => true,
			'show_ui'   => true,
			'show_admin_column' => true
		)
	);
}
add_action( 'init', 'lessons_cat_taxonomy');