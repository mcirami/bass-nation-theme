<?php

    $type = null;
    $videoLink = get_field('member_lesson_link');
    $id = get_the_ID();
	$desc = get_field('lesson_description');

    if (strpos($videoLink, "youtube") !== false) {
        $str = explode("embed/", $videoLink);
        $embedCode = preg_replace('/\s+/', '',$str[1]);
        $type = "youtube";
    } elseif (strpos($videoLink, "vimeo") !== false) {
        $str = explode("video/", $videoLink);
        $embedCode = preg_replace('/\s+/', '',$str[1]);
        $type = "vimeo";
    }

    $attachment_id = get_field('og_image');
    $size = "video-thumb";
    $ogImage = wp_get_attachment_image_src( $attachment_id, $size );

    if (!empty(get_the_post_thumbnail())) {
        $postThumbnail = get_the_post_thumbnail();
    }

    ?>

        <a href="/register">
        
    <?php
    if (!empty($ogImage)) : ?>

        <img class="og_image" src="<?php echo $ogImage[0]; ?>" alt="">

<?php elseif (!empty($postThumbnail)) :

                echo $postThumbnail;

    else : ?>

        <?php if ($type == 'youtube') : ?>

            <img src="https://img.youtube.com/vi/<?php echo $embedCode; ?>/mqdefault.jpg" />

        <?php elseif ($type == 'vimeo') : ?>

                <img src="<?php echo "https://vumbnail.com/" . $embedCode . ".jpg"; ?>" />

        <?php else : ?>

            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/lessons-screenshot.jpg" />

        <?php endif; ?>


    <?php endif; ?><!-- video thumbnail -->
        </a>

    <div class="lesson_content full_width">
        <h4><?php the_title(); ?></h4>
    </div>