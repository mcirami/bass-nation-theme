<?php
function fep_cus_fep_menu_buttons( $menu )
{
	unset( $menu['announcements'] );
	$menu['message_box']['title'] = "Inbox";
	$menu['message_box']['action'] = "messagebox&fep-filter=inbox";

	return $menu;
}
add_filter( 'fep_menu_buttons', 'fep_cus_fep_menu_buttons', 99 );

