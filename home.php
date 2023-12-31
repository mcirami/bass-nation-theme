<?php
/**
 * The main template file.
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * @package boiler
 */

get_header(); ?>

    <section class="page_content full_width<?php if (is_user_logged_in()){ echo " member";} ?>">
        <header class="sub_header full_width">
            <div class="container">
                <h2><?php the_field('page_title'); ?></h2>
            </div><!-- .container -->
        </header>
        <div class="container">
            <article class="content">

                <?php if ( have_posts() ) : ?>

                    <?php while ( have_posts() ) : the_post(); ?>

                        <?php
                        /* Include the Post-Format-specific template for the content.
                         * If you want to overload this in a child theme then include a file
                         * called content-___.php (where ___ is the Post Format name) and that will be used instead.
                         */
                        get_template_part( 'content', get_post_format() );
                        ?>

                    <?php endwhile; ?>

                    <?php boiler_content_nav( 'nav-below' ); ?>

                <?php else : ?>

                    <?php get_template_part( 'template-parts/no-results', 'index' ); ?>

                <?php endif; ?>
            </article>

            <?php //get_sidebar(); ?>

        </div>

    </section>

<?php get_footer(); ?>