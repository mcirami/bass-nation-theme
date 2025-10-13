<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GDATTAdmin {
	private array $page_ids = array();
	private bool $admin_plugin = false;

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'load' ) );
	}

	public static function instance() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new GDATTAdmin();
		}

		return $instance;
	}

	public function admin_init() {
		$_page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_tab    = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$this->admin_plugin = $_page === 'gdbbpress_attachments';

		if ( $this->admin_plugin ) {
			wp_enqueue_style( 'gd-bbpress-attachments', GDBBPRESSATTACHMENTS_URL . "css/admin.css", array(), GDBBPRESSATTACHMENTS_VERSION );

			if ( $_tab === 'tools' ) {
				if ( ! empty( $_action ) ) {
					require_once( GDBBPRESSATTACHMENTS_PATH . 'code/tools.php' );

					GDATTTools::instance()->process_action();

					exit;
				}
			}
		}
	}

	public function load() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		add_filter( 'plugin_action_links', array( $this, 'plugin_actions' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_links' ), 10, 2 );
	}

	public function admin_menu() {
		$this->page_ids[] = add_submenu_page( 'edit.php?post_type=forum', 'GD bbPress Attachments', __( 'Attachments', 'gd-bbpress-attachments' ), GDBBPRESSATTACHMENTS_CAP, 'gdbbpress_attachments', array(
			$this,
			'menu_attachments',
		) );

		$this->admin_load_hooks();
	}

	public function admin_load_hooks() {
		foreach ( $this->page_ids as $id ) {
			add_action( 'load-' . $id, array( $this, 'load_admin_page' ) );
		}
	}

	public function plugin_actions( $links, $file ) {
		if ( $file == 'gd-bbpress-attachments/gd-bbpress-attachments.php' ) {
			$settings_link = '<a href="edit.php?post_type=forum&page=gdbbpress_attachments">' . esc_html__( 'Settings', 'gd-bbpress-attachments' ) . '</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	public function plugin_links( $links, $file ) {
		if ( $file == 'gd-bbpress-attachments/gd-bbpress-attachments.php' ) {
			$links[] = esc_html__( 'Learn more about', 'gd-bbpress-attachments' ) . ': <a target="_blank" style="color: #cc0000; font-weight: bold;" href="https://www.dev4press.com/plugins/gd-bbpress-toolbox/">' . esc_html__( 'forumToolbox for bbPress', 'gd-bbpress-attachments' ) . '</a>' .
			           ' &amp; <a target="_blank" style="color: #cc0000; font-weight: bold;" href="https://www.dev4press.com/bbpress-club/">' . esc_html__( 'Get bbPress Plugins Club Membership', 'gd-bbpress-attachments' ) . '</a>';
		}

		return $links;
	}

	public function load_admin_page() {
		$screen = get_current_screen();

		$screen->set_help_sidebar( '
            <p><strong>Dev4Press:</strong></p>
            <p><a target="_blank" href="https://www.dev4press.com/">' . esc_html__( 'Website', 'gd-bbpress-attachments' ) . '</a></p>
            <p><a target="_blank" href="https://bsky.app/profile/dev4press.bsky.social">' . esc_html__( 'On BlueSky', 'gd-bbpress-attachments' ) . '</a></p>
            <p><a target="_blank" href="https://twitter.com/dev4press">' . esc_html__( 'On Twitter', 'gd-bbpress-attachments' ) . '</a></p>
            <p><a target="_blank" href="https://facebook.com/dev4press">' . esc_html__( 'On Facebook', 'gd-bbpress-attachments' ) . '</a></p>' );

		$screen->add_help_tab( array(
			"id"      => "gdpt-screenhelp-help",
			"title"   => esc_html__( 'Get Help', 'gd-bbpress-attachments' ),
			"content" => '<h2>' . esc_html__( 'General Plugin Information', 'gd-bbpress-attachments' ) . '</h2>
                <p><a href="https://www.dev4press.com/plugins/gd-bbpress-attachments/" target="_blank">' . esc_html__( 'Home Page on Dev4Press.com', 'gd-bbpress-attachments' ) . '</a> | 
                <a href="https://wordpress.org/plugins/gd-bbpress-attachments/" target="_blank">' . esc_html__( 'Home Page on WordPress.org', 'gd-bbpress-attachments' ) . '</a></p> 
                <h2>' . esc_html__( 'Getting Plugin Support', 'gd-bbpress-attachments' ) . '</h2>
                <p><a href="https://support.dev4press.com/forums/forum/plugins-free/gd-bbpress-attachments/" target="_blank">' . esc_html__( 'Support Forum on Dev4Press.com', 'gd-bbpress-attachments' ) . '</a> | 
                <a href="https://wordpress.org/support/plugin/gd-bbpress-attachments" target="_blank">' . esc_html__( 'Support Forum on WordPress.org', 'gd-bbpress-attachments' ) . '</a> </p>',
		) );

		$screen->add_help_tab( array(
			"id"      => "gdpt-screenhelp-website",
			"title"   => "Dev4Press",
			"sfc",
			"content" => '<p>' . esc_html__( 'On Dev4Press website you can find many useful plugins, themes and tutorials, all for WordPress. Please, take a few minutes to browse some of these resources, you might find some of them very useful.', 'gd-bbpress-attachments' ) . '</p>
                <p><a href="https://www.dev4press.com/plugins/" target="_blank"><strong>' . esc_html__( 'Plugins', 'gd-bbpress-attachments' ) . '</strong></a> - ' . esc_html__( 'We have more than 10 plugins available, some of them are commercial and some are available for free.', 'gd-bbpress-attachments' ) . '</p>
                <p><a href="https://www.dev4press.com/kb/" target="_blank"><strong>' . esc_html__( 'Knowledge Base', 'gd-bbpress-attachments' ) . '</strong></a> - ' . esc_html__( 'Premium and free tutorials for our plugins themes, and many general and practical WordPress tutorials.', 'gd-bbpress-attachments' ) . '</p>
                <p><a href="https://support.dev4press.com/forums/" target="_blank"><strong>' . esc_html__( 'Support Forums', 'gd-bbpress-attachments' ) . '</strong></a> - ' . esc_html__( 'Premium support forum for all with valid licenses to get help. Also, report bugs and leave suggestions.', 'gd-bbpress-attachments' ) . '</p>',
		) );
	}

	public function menu_attachments() {
		$options     = GDATTCore::instance()->o;
		$_user_roles = d4p_bbpress_get_user_roles();

		include GDBBPRESSATTACHMENTS_PATH . 'forms/panels.php';
	}
}
