<?php
/**
 * @package boiler
 */
	$postType = esc_html(get_post_type());

    $videoLink = get_field('video_link');

    if ($postType == "videos") {
	    $embedCode = getVideoEmbedCode($videoLink);
    }

?>


<div class="column full_width">

    <div class="row">

	    <?php if ($postType == "videos") : ?>

	        <?php if ($embedCode) : ?>

	            <a href="<?php the_permalink(); ?>">
					<?php if (str_contains($videoLink, "vimeo")) : ?>
						<img class="youtube_img"
							srcset="
								https://vumbnail.com/<?php echo $embedCode; ?>_large.jpg 640w, 
								https://vumbnail.com/<?php echo $embedCode; ?>_medium.jpg 200w, 
								https://vumbnail.com/<?php echo $embedCode; ?>_small.jpg 100w
							"
							src="https://vumbnail.com/<?php echo $embedCode; ?>.jpg" 
							alt="Video Thumbnail" 
						/>
					<?php else: ?>
	                	<img class="youtube_img" src="https://img.youtube.com/vi/<?php echo $embedCode; ?>/mqdefault.jpg" alt="Video Thumbnail" />
					<?php endif; ?>
	            </a>
	        <?php else:  ?>
	            <a href="<?php the_permalink(); ?>">
	                <img class="default" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/no-video-placeholder.jpg" alt="Video Thumbnail"/>
	            </a>
	        <?php endif; ?>

	    <?php else :

		        if ($image = get_field('course_image')) {
		    ?>
				    <a href="<?php the_permalink(); ?>">
					    <!--<img class="og_img" src="<?php /*echo $ogImage[0];*/?>" />-->
                        <img src="<?php echo $image['url']; ?>" alt="">
				    </a>

		        <?php } elseif($image = get_field('og_image')) {
			        $attachment_id = get_field('og_image');
			        $size = "video-thumb";
			        $ogImage = wp_get_attachment_image_src( $attachment_id, $size );
			        ?>
			        <a href="<?php the_permalink(); ?>">
				        <img class="og_img" src="<?php echo $ogImage[0];?>" />
			        </a>

		        <?php } else { ?>
				        <a href="<?php the_permalink(); ?>">
			                <img class="default" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/lessons-screenshot.jpg" />
			            </a>
		        <?php } ?>

	    <?php endif; ?>


    </div>
    <div class="row text">
        <h3><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h3>

		<?php
            $post_id = get_the_ID();
            $commentCount = wp_count_comments( $post_id );
	    if($postType != "courses") :
            ?>
			<div class="desc_wrap">
				<p><?php the_field('description'); ?></p>
			</div>
	    <?php endif; ?>
	    <?php if ($postType == "videos") : ?>

	        <?php $author = get_the_author_meta('user_login'); ?>
	        <h4>Submitted by <a href="/membership-account/member-profile/?pu=<?php echo $author; ?>"><?php echo $author; ?></a></h4>

		<?php endif; ?>

		<?php if($postType != "courses") : ?>
			<h4><?php  if ($postType == "videos") { echo "Thread Replies: "; } else { echo  "Episode Inquiries: "; } ?>
		        <?php echo $commentCount->total_comments; ?>
	        </h4>
		<?php endif; ?>

	    <?php if ($postType != "videos") : ?>

	        <h4 class="sub_title"><?php the_field('sub_title'); ?></h4>

	    <?php endif; ?>


	    
    </div>
	<div class="button_wrap">
		<a class="button yellow" href="<?php the_permalink(); ?>">
			<?php if ($postType == "videos") { 
				echo "Open Thread"; 
			} else {  
				the_field('button_text'); 
			} ?>
				<span>
					<img src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/arrow-right.svg" alt="Bass Nation Logo"/>
				</span>
		</a>
	</div>
</div>