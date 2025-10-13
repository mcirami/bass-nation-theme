<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'gdbbx_fs' ) ) {
	// Create a helper function for easy SDK access.
	function gdbbx_fs() {
		global $gdbbx_fs;

		if ( ! isset( $gdbbx_fs ) ) {
			// Include Freemius SDK.
			$gdbbx_fs = fs_dynamic_init( array(
				'id'                  => '17805',
				'slug'                => 'gd-bbpress-attachments',
				'premium_slug'        => 'gd-bbpress-toolbox',
				'type'                => 'plugin',
				'public_key'          => 'pk_661bebdf7bef26f6377980a388290',
				'is_premium'          => false,
				'premium_suffix'      => 'Pro',
				'has_premium_version' => true,
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'trial'               => array(
					'days'               => 7,
					'is_require_payment' => false,
				),
				'menu'                => array(
					'slug'    => 'gdbbpress_attachments',
					'contact' => false,
					'parent'  => array(
						'slug' => 'edit.php?post_type=forum',
					),
				),
			) );
		}

		return $gdbbx_fs;
	}

	// Init Freemius.
	gdbbx_fs();
	// Signal that SDK was initiated.
	do_action( 'gdbbx_fs_loaded' );

	function forumtoolbox_premium_support_forum_url( $support_forum_url ) : string {
		return 'https://support.dev4press.com/forums/forum/plugins/gd-bbpress-toolbox/';
	}

	if ( gdbbx_fs()->is_premium() ) {
		gdbbx_fs()->add_filter( 'support_forum_url', 'forumtoolbox_premium_support_forum_url' );
	}

	gdbbx_fs()->add_filter( 'pricing/disable_single_package', '__return_true' );
	gdbbx_fs()->add_filter( 'pricing/show_annual_in_monthly', '__return_false' );
}
