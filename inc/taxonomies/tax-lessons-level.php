<?php
function lessons_level_taxonomy() {
	register_taxonomy(
		'level',  //The name of the taxonomy. Name should be in slug form (must not contain capital letters or spaces).
		'lessons',  //post type name
		array(
			'hierarchical' => true,
			'label' => 'Level',  //Display name
			'query_var' => true,
			'show_ui'   => true,
			'show_admin_column' => true,
			'show_in_rest' => true
		)
	);
}
add_action( 'init', 'lessons_level_taxonomy');