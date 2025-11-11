<?php

global $current_user;
function my_avatar_filter() {
    // Remove from show_user_profile hook
    remove_action( 'show_user_profile', array( 'wp_user_avatar', 'wpua_action_show_user_profile' ) );
    remove_action( 'show_user_profile', array( 'wp_user_avatar', 'wpua_media_upload_scripts' ) );

    // Remove from edit_user_profile hook
    remove_action( 'edit_user_profile', array( 'wp_user_avatar', 'wpua_action_show_user_profile' ) );
    remove_action( 'edit_user_profile', array( 'wp_user_avatar', 'wpua_media_upload_scripts' ) );

    // Add to edit_user_avatar hook
    add_action( 'edit_user_avatar', array( 'wp_user_avatar', 'wpua_action_show_user_profile' ) );
    add_action( 'edit_user_avatar', array( 'wp_user_avatar', 'wpua_media_upload_scripts' ) );
}

// Loads only outside of administration panel
if ( ! is_admin() ) {
    add_action( 'init','my_avatar_filter' );
}

do_action( 'edit_user_avatar',  $current_user  );
