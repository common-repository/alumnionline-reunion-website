<?php
/**
 * The template for displaying message-posts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

$settings = alumnionline_reunion_website_get_settings();
if ( isset( $settings['alumnionline_maincontent_id'] ) ) {
$maincontentid = $settings['alumnionline_maincontent_id'];
}
else $maincontentid = 'site-content';

?>
<main id="<?php echo esc_attr($maincontentid ); ?>">
	<div class="alumnonline-reunion-plugin-message-post-wrapper">
		<?php

		if ( have_posts() ) {

			while ( have_posts() ) {
				the_post();

				// secure posts by school id.
				alumnionline_reunion_website_secure_posts( $post->ID );

				if ( has_post_thumbnail() ) {
					?>
		<div class="alumnonline-reunion-plugin-featured-image">
					<?php the_post_thumbnail( 'large' ); ?>
		</div>
					<?php
				}

				echo '<h2>';
				the_title();
				echo '</h2>';

				the_content();

				if ( function_exists( 'alumnionline_reunion_website_create_gallery_code' ) ) {
					echo do_shortcode( alumnionline_reunion_website_create_gallery_code( $post->ID ) );
				}
			}
		}

		?>

<?php comments_template(); ?>

	</div>

</main><!-- #site-content -->


<?php get_footer(); ?>
