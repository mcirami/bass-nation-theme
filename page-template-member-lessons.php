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
$pageId = get_the_ID();

if (pmpro_hasMembershipLevel() || $pageId == 7) {

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
    } elseif ($title == "Courses") {
        $args = array (
            'post_type' => 'courses',
            'order_by' => 'post_date',
            'order' => 'DESC',
            'posts_per_page' => -1,
        );
    } elseif ($pageId == 7) {
        $args = array (
            'post_type' => 'lessons',
            'tax_query' => array (
                array (
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => 'free-lessons',
                    'order_by' => 'post_date',
                    'order' => 'DESC',
                    'posts_per_page' => -1,
                )
            ),
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

    $catTerms = get_terms('category');
    $levelTerms = get_terms('level', array(
            'orderby' => 'description'
    ));

    $lessons = new WP_Query($args);
}?>

<div class="lessons_page full_width page_content <?php if ($pageId == 7){ echo "free_lessons"; } ?> <?php if (is_user_logged_in()){ echo "member";} ?>">

    <header class="sub_header full_width">
        <div class="container">
            <h2><?php echo $title; //the_field('page_header'); ?></h2>
        </div><!-- .container -->
    </header>

<?php if (pmpro_hasMembershipLevel()  || $pageId == 7) : ?>

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

            <?php if ($pageId == 7) : ?>
                <div id="free_video_share_column" style="display: none;">
                    <div class="call_to_action">
                        <div class="upgrade ">
                            <h3>Get Full Access To Everything!</h3>
                            <a class="button yellow" href="/register">
                                Start FREE Today!
                            </a>
                        </div>
                    </div>
                    <div class="share_buttons">                
                        <div class="social_button_wrap">
                            <a class="facebook" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u=<?php the_field('share_link'); ?>">
                                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/icon-facebook-f.png" alt="Facebook Share Link"/>
                                Share
                            </a>
                        </div>
                        <div class="social_button_wrap">
                            <a class="email" href="mailto:?&subject=Awesome Bass Lesson!&body=Check%20out%20this%20bass%20lesson%20I%20found%20on%20http%3A//daricbennett.com...%0A%0A
                            <?php  the_field('share_link');?>"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/email-envelope.png" alt="Email Envelope"/>
                                Email
                            </a>
                        </div>
                        <div class="social_button_wrap">
                            <a target="_blank" class="page" href="<?php echo $lessonLink;?>">Lesson Page</a>
                        </div>
                    </div>
                </div>
                <div class="free_lessons_section">
                    <div class="container">
                
                    <div class="intro_text full_width">
                    
                        <?php $heading = get_field('heading_text');

                        if ($heading != '') : ?>
                            <h3><?php the_field('heading_text'); ?></h3>
                        <?php endif; ?>

                        <?php $desc = get_field('description');

                        if ($desc != '') : ?>
                            <p><?php the_field('description' , false, false); ?></p>
                        <?php endif; ?>
                    </div><!-- intro_text -->

                        <?php $videoLink = get_field('intro_video_link');

                        if ($videoLink != '') : ?>

                            <div class="top_video_section full_width">
                                <div class="video_wrap">
                                    <div class="video_wrapper full_width">
                                        <iframe src="<?php the_field('intro_video_link'); ?>" allowfullscreen></iframe>
                                    </div>
                                </div>
                                <div class="social_media_column">

                                    <?php get_template_part( 'template-parts/content', 'social-media' ); ?>

                                    <div class="button_wrap">
                                        <a class="button yellow" href="/register">Start My Full Access Free Trial Now!
                                            <span>
                                                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
                                            </span>
                                        </a>
                                    </div>
                                </div>
                                
                            </div>

                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <section class="video_list full_width">

                <div class="container">

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
                                <input class="textfield filter__search js-shuffle-search" type="text" name="search" placeholder="Search Lesson By Keyword" data-search>
                            </div>
                        </div><!-- filter_controls -->
           
                        <?php if ($title == "Favorite Lessons") : ?>

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

                                        if($title == "Lessons" || $title == "Favorite Lessons" || $pageId == 7) :
                                            $hide = get_field('hide_lesson');

                                            if (!$hide) : ?>

                                                <?php 
                                                    set_query_var( 'pageId', $pageId);
                                                    get_template_part('template-parts/content', 'member-lesson'); 
                                                ?>

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
<div id="members_only_video_pop">
    <div id="members_only_content">
        <span class="close_popup" title="Close">
            <svg xmlns="http://www.w3.org/2000/svg" version="1" viewBox="0 0 24 24"><path d="M13 12l5-5-1-1-5 5-5-5-1 1 5 5-5 5 1 1 5-5 5 5 1-1z"></path></svg>
        </span>
        <img class="logo" src="<?php  echo esc_url( get_template_directory_uri() ); ?>/images/logo.png" alt="Bass Nation Logo"/>
        <h2>This Lesson Is </br> For Members Only</h2>
        <div class="button_wrap">
            <a class="button yellow" href="/register">
                Start My Free Trial For Full Access!
                <span>
                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
                </span>
            </a>
            
        </div>
    </div>
</div>

<?php get_footer(); ?>
