<?php
/**
 * Bass Nation functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Bass_Nation
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '3.1.9' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function bass_nation_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on Bass Nation, use a find and replace
		* to change 'bass-nation' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'bass-nation', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'bass-nation' ),
			'mobile' => __( 'Mobile Menu', 'bass-nation' ),
			'members' => __( 'Members Menu', 'bass-nation' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'bass_nation_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'bass_nation_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function bass_nation_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'bass_nation_content_width', 640 );
}
add_action( 'after_setup_theme', 'bass_nation_content_width', 0 );

// Custom script loader class.
require get_template_directory() . '/classes/class-daricbennett-script-loader.php';

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function bass_nation_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'bass-nation' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'bass-nation' ),
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1>',
		)
	);
}
add_action( 'widgets_init', 'bass_nation_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function bass_nation_scripts() {
	wp_enqueue_style( 'bass-nation-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'bass-nation-style', 'rtl', 'replace' );

	/* My Added Styles */
	wp_enqueue_style( 'main-style',  get_template_directory_uri() . '/css/main.min.css', array(), _S_VERSION, 'all');
	wp_enqueue_style( 'fancybox', get_template_directory_uri() . '/js/vendor/fancybox/jquery.fancybox.min.css');

	wp_enqueue_script( 'bass-nation-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	/* My Added Scripts */
	wp_enqueue_script( 'fancybox_js', get_template_directory_uri() . '/js/vendor/fancybox/jquery.fancybox.min.js', array('jquery'), '', true );
	/* if ((is_page('lessons') && is_user_logged_in()) || get_the_ID() == 7){
		wp_enqueue_script('shuffle-js', 'https://cdn.jsdelivr.net/npm/shufflejs@6.1.1/dist/shuffle.min.js', array('jquery'), null, true);
	} */
	wp_enqueue_script( 'main_js', get_template_directory_uri() . '/js/built.min.js', array('jquery'), _S_VERSION, true );
	wp_enqueue_script( 'vimeo', get_template_directory_uri() . '/js/vendor/vimeothumb/jquery-vimeothumb.min.js', array('jquery'), '', true );
	wp_enqueue_script( 'vimeo_player', 'https://player.vimeo.com/api/player.js', array('jquery'), '', true );
	
	wp_enqueue_script( 'calendly', 'https://assets.calendly.com/assets/external/widget.js', array('jquery'), '1', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}

	if(is_user_logged_in()) {
		wp_localize_script( 'main_js', 'myAjaxurl', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	wp_localize_script( 'main_js', 'currentPage', array(
		'pageName' 	=> get_the_title(),
		'postType' 	=> get_post()->post_type,
		'postSlug' 	=> get_permalink(),
		'pageId'	=> get_the_ID()
	) );

}
add_action( 'wp_enqueue_scripts', 'bass_nation_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

require_once get_template_directory() . '/inc/helpers.php';

/**
 * Plugin specific
 */
require_once get_template_directory() . '/inc/plugin-functions/acf-functions.php';
require_once get_template_directory() . '/inc/plugin-functions/bbpress-functions.php';
require_once get_template_directory() . '/inc/plugin-functions/pmpro-functions.php';
require_once get_template_directory() . '/inc/plugin-functions/front-end-pm-functions.php';
require_once get_template_directory() . '/inc/plugin-functions/one-user-avatar.php';
/**
 * ShortCodes
 */
require_once get_template_directory() . '/inc/shortcodes.php';

/**
 * Custom Post Types
 */
require_once get_template_directory() . '/inc/post-types/cpt-lesson.php';
require_once get_template_directory() . '/inc/post-types/cpt-video.php';
require_once get_template_directory() . '/inc/post-types/cpt-tv-video.php';
require_once get_template_directory() . '/inc/post-types/cpt-live-stream.php';
require_once get_template_directory() . '/inc/post-types/cpt-course.php';
/**
 * Taxonomies
 */
require_once get_template_directory() . '/inc/taxonomies/tax-lessons-category.php';
require_once get_template_directory() . '/inc/taxonomies/tax-lessons-level.php';

require_once get_template_directory() . '/inc/custom_register_methods.php';