<?php
/**
 * Created by PhpStorm.
 * User: matteocirami
 * Date: 11/21/17
 * Time: 9:31 AM
 */

//$title = get_query_var('pagename');

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
    } elseif (strpos($videoLink, "soundslice") !== false) {

        $displayKeyboard = get_field('display_keyboard_video');
        $notation = get_field('has_notation');

        if($notation) {
            $controls = '1';
            $display = 'yes';
        } else {
            $controls = '0';
            $display = 'no';
        }

        $embedCode = $videoLink . "?api=1&branding=2&fretboard=1&force_top_video=1&top_controls=" . $controls . "&scroll_type=2&narrow_video_height=48p&enable_waveform=0";

        if (get_field('display_keyboard_video')) {
            $embedKeyboard = $embedCode . "&recording_idx=0&keyboard=1";
        }

        $type = "soundslice";
    }

    $count = 0;
    $taxonomies = [];
    $index = 0;

    $categories = get_the_category();

    foreach ($categories as $category) {
        $taxonomies[$index] = intval($category->term_id);
        $index++;
    }

    $levels = get_the_terms($post->ID, 'level');

    if (is_array($levels) || is_object($levels)) {
        foreach ($levels as $level) {
            $taxonomies[$index] = intval($level->term_id);
            $index++;
        }
    }

    $totalCount = count($taxonomies);
    $hash = preg_replace( '/%..|[^a-zA-Z0-9-]/', '', $post->post_name);
    ?>

        <div class="column filtr-item" data-sort="value" data-category="<?php
            foreach ($taxonomies as $taxonomy) {
                echo $taxonomy;
                $count++;
                if ($count < $totalCount) {
                    echo ", ";
                }

            } ?>" >

            <?php if ($type == "soundslice" && get_field('display_keyboard_video')) : ?>

                    <input class="keyboard_embed" hidden data-embed="<?php echo $embedKeyboard;?>">

            <?php endif; ?>

            <?php
                $addFile = get_field('add_file');

                if (have_rows('files')) : ?>

                    <?php while (have_rows('files')) : the_row();?>

                        <a target="_blank" class="video_files" href="#" data-file="<?php the_sub_field('file'); ?>" data-text="<?php the_sub_field('file_text'); ?>"></a>

                    <?php endwhile; ?>

                <?php endif; ?>
            <div class="vid_image_wrap">

            <?php if ($type == 'youtube') : ?>

                    <a id="<?php echo $hash; ?>" class="play_video"
                       href="#<?php echo $hash;?>"
                       data-type="<?php echo "youtube";?>"
                       data-src="<?php echo $videoLink; ?>/?rel=0&showinfo=0&autoplay=1"
                       data-title="<?php echo the_title();?>"
                       data-postid="<?php echo $id; ?>"
                    >

            <?php elseif ($type == 'vimeo') : ?>

                    <a id="<?php echo $hash; ?>" class="play_video"
                       href="#<?php echo $hash;?>"
                       data-type="<?php echo "vimeo";?>"
                       data-src="<?php echo $videoLink; ?>/?autoplay=1"
                       data-title="<?php echo the_title();?>"
                       data-postid="<?php echo $id; ?>"
                    >

            <?php elseif ($type == 'soundslice') : ?>

                    <a id="<?php echo $hash; ?>" class="play_video"
                       href="#<?php echo $hash;?>"
                       data-replace="<?php the_field('vimeo_link'); ?>"
                       data-type="<?php echo "soundslice_video";?>"
                       data-src="<?php echo $embedCode; ?>"
                       data-title="<?php echo the_title();?>"
                       data-notation="<?php echo $display; ?>"
                       data-postid="<?php echo $id; ?>"                       
                    >

            <?php endif; ?><!-- type -->
                        <span class="lesson_description" style="display: none; z-index: -999;"><?php echo $desc; ?></span>

                        <?php
                                $attachment_id = get_field('og_image');
                                $size = "video-thumb";
                                $ogImage = wp_get_attachment_image_src( $attachment_id, $size );

                                /*if (!is_wp_error(video_thumbnail())) {
                                    $video_thumbnail = get_video_thumbnail();
                                }*/

                                if (!empty(get_the_post_thumbnail())) {
                                    $postThumbnail = get_the_post_thumbnail();
                                }


                               if (!empty($ogImage)) :
                        ?>
                            <img class="og_image" src="<?php echo $ogImage[0]; ?>" alt="">

                        <?php  //elseif ( !empty($video_thumbnail) ) : ?>

                            <!--<img class="get_video_thumbnail" src="<?php /*echo $video_thumbnail; */?>" alt="">-->

                        <?php elseif (!empty($postThumbnail)) :

                                    echo $postThumbnail;

                        else : ?>

                            <?php if ($type == 'youtube') { ?>

                                <img src="https://img.youtube.com/vi/<?php echo $embedCode; ?>/mqdefault.jpg" />

                            <?php } else { ?>

                                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/lessons-screenshot.jpg" />

                            <?php } ?>


                        <?php endif; ?><!-- video thumbnail -->

                    </a>

                    <div class="button_wrap full_width">
                        <?php the_favorites_button();?>
                    </div>
            </div>

            <div class="lesson_content full_width">
                <h4><?php the_title(); ?></h4>
                <p>Date Added <?php echo get_the_date('n/j/Y'); ?></p>
            </div>

        </div><!-- column -->