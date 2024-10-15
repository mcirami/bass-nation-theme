<?php

/**
 * Template Name: Member Lessons
 *
 * The template for displaying lessons page.
 *
 *
 * @package boiler
 */

get_header();

$title = get_the_title();

if (pmpro_hasMembershipLevel()) {

    $favorites = get_user_favorites();

    global $post;

    if ($title == "Lessons") {

    	//$ourCurrentPage = get_query_var('pages');

        $args = array(
            'post_type' => 'lessons',
            'order_by' => 'post_date',
            'order' => 'DESC',
            'posts_per_page' => -1
	        //'paged' => $ourCurrentPage
        );

	    $catTerms = get_terms('category');
	    $levelTerms = get_terms('level', array(
	    		'orderby' => 'description'
	    ));

    } elseif ($title == "Courses") {
        $args = array (
            'post_type' => 'courses',
            'order_by' => 'post_date',
            'order' => 'DESC',
            'posts_per_page' => -1,
        );
    } else {
        $args = array(
            'post_type' => 'lessons',
            'order_by' => 'post_date',
            'order' => 'DESC',
            'post__in' => $favorites,
            //'posts_per_page' => -1,
        );
    }

    $lessons = new WP_Query($args);
}


?>

<div class="lessons_page full_width page_content <?php if (is_user_logged_in()){ echo "member";} ?>">

    <header class="sub_header full_width">
        <div class="container">
            <h2><?php echo $title; //the_field('page_header'); ?></h2>
        </div><!-- .container -->
    </header>

<?php if (pmpro_hasMembershipLevel()) : ?>

        <div id="video_player" class="full_width">
            <div id="video_iframe_wrap"></div>
            <div id="video_content_wrap"></div>
        </div>

        <div class="full_width">

            <?php if ($title == "Lessons") : 
                    $featured_courses = get_field('featured_courses');
                
                    if ($featured_courses) :?>
                        <section class="banner_wrap full_width">
                            <div class="container">
                                <div class="title_wrap">
                                    <h3>Featured Courses</h3>
                                </div>
                                <ul>
                                    <?php foreach ($featured_courses as $post):
                                        setup_postdata($post);
                                        $image = get_field( 'course_image', $post->ID );
                                        ?>
                                        <li>
                                            <a class="image_link" href="<?php the_permalink(); ?>">
                                                <img src="<?php echo $image['url']; ?>" alt="">
                                            </a>
                                            <a href="<?php the_permalink(); ?>">
                                                <p><?php the_title(); ?></p>
                                            </a>
                                        </li>

                                    <?php endforeach ?>
                                </ul>
                            </div>
                        </section>
                        <?php 
                            wp_reset_postdata();
                            endif; ?>
                    <?php endif; ?>

            <section class="video_list full_width">

                <div class="container">
                    <?php if ($title == "Lessons" || $title == "Courses" || $title == "Favorite Lessons") : ?>
                        <div class="videos_wrap">
                            <div class="filter_controls full_width" <?php
                                if($title == "Courses" || $title == "Favorite Lessons") {
                                    echo "style=display:none;";
                                }?>>

                                <div class="filters filters-group">
                                    <h3>Filter Lessons By<span>:</span></h3>
                                    <p>(select as many as you like)</p>
                                    <ul id="lesson_grid" class="filter_list full_width filter-options">
                                        <!-- <li data-multifilter="all" class="active all">All</li> -->
                                        <li data-group="all" class="active all">All</li>
                                        <?php foreach ($levelTerms as $levelTerm) : ?>

                                            <!-- <li data-multifilter="<?php echo $levelTerm->term_id;?>"><?php echo $levelTerm->name;?></li> -->
                                            <li data-group="<?php echo $levelTerm->term_id; ?>"><?php echo $levelTerm->name;?></li>
                                        <?php endforeach; ?>

                                        <?php foreach ($catTerms as $catTerm) :

                                                if($catTerm->slug !== "members-only" && $catTerm->slug !== "uncategorized" && $catTerm->slug !== "free-lessons" && $catTerm->slug !== "ultra-beginner-series") :
                                            ?>
                                                        <!-- <li data-multifilter="<?php echo $catTerm->term_id;?>"><?php echo $catTerm->name;?></li> -->
                                                        <li data-group="<?php echo $catTerm->term_id;?>"><?php echo $catTerm->name;?></li>
                                                <?php endif; ?>

                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="search_box">
                                    <input type="text" name="search" placeholder="Search Lesson By Keyword" data-search>
                                </div>
                            </div><!-- filter_controls -->
                        <?php elseif ($title == "Favorite Lessons") : ?>

                            <div class="top_content full_width">
                                <?php if ($favorites != null) :
                                        $favCount = get_user_favorites_count();
                                    ?>
                                    <h3><?php echo $favCount; ?> <?php if( $favCount == 1) { echo "Favorite"; } else {echo "Favorites";}?></h3>
                                <?php endif; ?>

                                <?php the_clear_favorites_button(); ?>
                            </div>

                        <?php endif; ?>


                        <div id="filter_images" class="shuffle-container full_width">

                            <?php if ($favorites == null && $title == "Favorite Lessons") : ?>

                                <div class="text_wrap full_width">
                                    <h2>You have no Favorite Lessons</h2>
                                    <div class="button_wrap full_width">
                                        <a class="button red" href="/lessons">Go To Lesson page Now!</a>
                                    </div>

                                </div>

                            <?php else : ?>

                                <?php if ( $lessons->have_posts() ) : while( $lessons->have_posts() ) : $lessons->the_post();

                                        if($title == "Lessons" || "Favorite Lessons") :
                                            $hide = get_field('hide_lesson');

                                            if (!$hide) : ?>

                                                <?php get_template_part('template-parts/content', 'member-lesson'); ?>

                                            <?php endif; ?> <!-- hide -->

                                        <?php elseif($title == "Courses") : ?>

                                            <?php get_template_part('template-parts/content', 'all-courses'); ?>

                                        <?php endif;?> 

                                    <?php endwhile; //query loop


                                        /*previous_posts_link();
                                        next_posts_link('Next Page', $lessons->max_num_pages);*/
                                    else :

                                        echo 'no posts found';

                                    endif; // if has posts

                                    wp_reset_query();
                                ?>

                            <?php endif; ?>
                            <div class="js-shuffle-sizer"></div>
                        </div><!-- filtr-container -->
                    </div>
                </div><!-- container -->
            </section><!-- video_list -->
        </div><!-- full_width -->

<?php else :

    get_template_part( 'template-parts/content', 'not-member' );  ?>

<?php endif; ?>

</div><!-- lessons_page -->



<?php get_footer(); ?>
