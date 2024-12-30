<div class="video full_width">

    <h3><?php the_title(); ?></h3>  
    <div class="row">

            <?php $videoLink = get_field('video_link');
            $embedLink = null;
            if ( str_contains( $videoLink, "embed" ) ) {
                $embedLink = $videoLink . "/?rel=0&showinfo=0";
            } else {
                $embedCode = getVideoEmbedCode($videoLink);
                if ( (str_contains( $videoLink, "v=" ) && str_contains( $videoLink, "youtube" ) ) || str_contains( $videoLink, "youtu.be" ) ) {
                    $embedLink =  "https://www.youtube.com/embed/" . $embedCode . "/?rel=0&showinfo=0";
                } elseif ( str_contains( $videoLink, "vimeo" ) ) {
                    $embedLink = "https://player.vimeo.com/video/" . $embedCode;
                }
            }

            if ($embedLink != null) :

            ?>
                <div class="video_wrapper">
                    <iframe src="<?php echo $embedLink; ?>" allow="encrypted-media" allowfullscreen></iframe>
                </div>

            <?php else : ?>

                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/no-video-placeholder.jpg"/>

            <?php endif; ?>

    </div>
    <div class="row desc">
        <p><?php the_field('description'); ?></p>
        <?php $author = get_the_author_meta('user_login'); ?>
        <h4>Submitted by <a href="/membership-account/member-profile/?pu=<?php echo $author; ?>"><?php echo $author; ?></a></h4>
    </div>

</div>