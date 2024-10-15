<div class="column filtr-item" data-sort="value">
    <div class="vid_image_wrap all_courses">
        <a href="<?php echo the_permalink(); ?>">
            <?php
                $courseImg = "";
                if (!empty(get_the_post_thumbnail())) :
                    echo get_the_post_thumbnail();
                elseif (!empty(get_field('course_image'))) :
                    $image = get_field('course_image'); ?>

                    <img src="<?php echo $image['url']; ?>" />

               <?php else : ?>

                    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/lessons-screenshot.jpg" />

                <?php endif;?>
        </a>
    </div>
    <div class="lesson_content full_width">
        <h4><?php the_title(); ?></h4>
        <p>Date Added <?php echo get_the_date('n/j/Y'); ?></p>
    </div>
</div>