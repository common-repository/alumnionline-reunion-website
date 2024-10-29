<?php
/**
 * The template for displaying authors
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

get_header();

$curauth = ( isset( $_GET['author_name'] ) ) ? get_user_by( 'slug', $author_name ) : get_userdata( intval( $author ) );

$settings = alumnionline_reunion_website_get_settings();
if ( isset( $settings['alumnionline_maincontent_id'] ) ) {
$maincontentid = $settings['alumnionline_maincontent_id'];
}
else $maincontentid = 'site-content';

?>
<main id="<?php echo esc_attr($maincontentid ); ?>">
	<div class="alumnonline-reunion-plugin-member-wrapper">
		<div class="alumnonline-reunion-plugin-featured-image">
			<?php
			$avatarurl = get_avatar_url( $curauth->ID );
			if ( '' != $avatarurl ) {
				echo '<img src="' . esc_url( $avatarurl ) . '">';
			}
			?>
		</div>
		<?php
		echo '<h2>';
		echo esc_attr( $curauth->first_name );
		echo ' ';
		echo esc_attr( $curauth->last_name );
		echo '</h2>';

		$customfields = get_user_meta( $curauth->ID );
		echo '<div class="alumnionline-reunion-website-otherinfo">';
		foreach ( $customfields as $key => $value ) {
			if ( strstr( $key, 'alumnionline_' ) && ! strstr( $key, '_alumnionline_' ) && ! strstr( $key, 'alumnionline_school_id' ) ) {
				echo '<span class="alumnionline-reunion-website-label">';
				echo esc_attr( strtoupper( str_replace( array( 'alumnionline_', '_' ), array( '', ' ' ), $key ) ) );
				echo ': ';
				echo '</span>';
				if ( is_array( $value ) ) {
					echo esc_attr( $value[0] );
				} else {
					echo esc_attr( $value );
				}
				echo '<br>';
			}
		}
			echo '</div>';

			comments_template();
			?>
	</div>
</main><!-- #site-content -->


<?php get_footer(); ?>
