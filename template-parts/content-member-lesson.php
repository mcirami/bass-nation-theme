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
    $hash = $post->post_name;

    ?>

        <div class="column filtr-item" data-sort="value" data-groups='[<?php 
            foreach ($taxonomies as $taxonomy) {
                echo '"' . $taxonomy . '"';
                $count++;
                if ($count < $totalCount) {
                    echo ", ";
                }

            } ?>]' >

            <?php
                $addFile = get_field('add_file');
                $fileArray = [];
                if (have_rows('files')) : ?>

                    <?php while (have_rows('files')) : the_row();?>

                        <?php 
                            $object = [
                                'file'  => get_sub_field('file'),
                                'text'  => get_sub_field('file_text')
                            ];
                            $fileArray[] = $object;
                        ?>

                    <?php endwhile;?>
                   
                <?php endif; ?>

                <?php $extension = $type =='youtube' ? '/?rel=0&showinfo=0&autoplay=1' : '/?autoplay=1';  ?>
           
            <div class="vid_image_wrap">
                <?php $totalFileCount = count($fileArray); $fileCount = 0; ?>
                <a id="<?php echo $hash; ?>" class="play_video"
                    href="#<?php echo $hash;?>"
                    data-type="<?php echo $type;?>"
                    data-src="<?php echo $videoLink . $extension; ?>"
                    data-title="<?php echo the_title();?>"
                    data-postid="<?php echo $id; ?>"
                    data-desc="<?php echo htmlspecialchars($desc); ?>"
                    data-files='[<?php
                        foreach($fileArray as $file) {
                            $item = '{"file":"' . htmlspecialchars($file["file"]) . '",'.'"text":"'. htmlspecialchars($file["text"]) .'"}';
                            echo $item;
                            $fileCount++;
                            if ($fileCount < $totalFileCount) {
                                echo ", ";
                            }
                        }
                    ?>]'>
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

                    <?php elseif (!empty($postThumbnail)) :

                                echo $postThumbnail;

                        else : ?>

                            <?php if ($type == 'youtube') : ?>

                                <img src="https://img.youtube.com/vi/<?php echo $embedCode; ?>/mqdefault.jpg" />

                            <?php elseif ($type == 'vimeo') : 
                                $url = "https://vumbnail.com/" . $embedCode . ".jpg";
                            ?>
                                <img src="<?php echo $url; ?>" />
                            
                            <?php else : ?>

                                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/lessons-screenshot.jpg" />

                            <?php endif; ?>

                    <?php endif; ?><!-- video thumbnail -->

                </a>

                <div class="button_wrap full_width">
                    <?php the_favorites_button();?>
                </div>
            </div>

            <div class="lesson_content full_width">
                <h4 class="lesson__title"><?php the_title(); ?></h4>
                <p>Date Added <?php echo get_the_date('n/j/Y'); ?></p>
            </div>

        </div><!-- column -->