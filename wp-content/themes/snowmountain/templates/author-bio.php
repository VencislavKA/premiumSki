<?php
/**
 * The template to display the Author bio
 *
 * @package WordPress
 * @subpackage SNOWMOUNTAIN
 * @since SNOWMOUNTAIN 1.0
 */
?>

<div class="author_info author vcard" itemprop="author" itemscope itemtype="//schema.org/Person">

	<div class="author_avatar" itemprop="image">
		<?php 
		$snowmountain_mult = snowmountain_get_retina_multiplier();
		echo get_avatar( get_the_author_meta( 'user_email' ), 182*$snowmountain_mult );
		?>
	</div><!-- .author_avatar -->

	<div class="author_description">
		<h5 class="author_title" itemprop="name"><?php echo wp_kses_data(sprintf(__('About %s', 'snowmountain'), '<span class="fn">'.get_the_author().'</span>')); ?></h5>

		<div class="author_bio" itemprop="description">
            <div class="author_bio_description">
                <?php echo wp_kses(wpautop(get_the_author_meta( 'description' )), 'snowmountain_kses_content' ); ?>
            </div>
			<a class="author_link" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
				<?php printf( esc_html__( 'View all posts by %s', 'snowmountain' ), '<span class="author_name">' . esc_html(get_the_author()) . '</span>' ); ?>
			</a>
                <?php do_action('snowmountain_action_user_meta'); ?>
		</div><!-- .author_bio -->

	</div><!-- .author_description -->

</div><!-- .author_info -->
