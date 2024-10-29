<?php
/**
 * SETTINGS FUNCTIONS
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}


/*******************************************************
 * Add admin menu pages
 ******************************************************/
function alumnionline_reunion_website_add_menu_links() {

	add_options_page( __( 'AlumniOnline Reunion Website', 'alumnionline-reunion-website' ), __( 'AlumniOnline Reunion Website', 'alumnionline-reunion-website' ), 'manage_options', 'alumnionline-reunion-website', 'alumnionline_reunion_website_admin_interface_render' );

	add_menu_page( __( 'Reunion Website', 'alumnionline-reunion-website/' ), __( 'Reunion Website', 'alumnionline-reunion-website' ), 'manage_options', 'alumnionline-reunion-website', 'alumnionline_reunion_website_admin_interface_render', 'dashicons-format-chat', 2 );

	add_submenu_page( 'alumnionline-reunion-website/', esc_html__( 'Settings', 'alumnionline-reunion-website' ), esc_html__( 'Settings', 'alumnionline-reunion-website' ), 'manage_options', 'alumnionline-reunion-website/', 'alumnionline_reunion_website_admin_interface_render' );

	$settings = alumnionline_reunion_website_get_settings();
	if ( isset( $settings['alumnionline_forum_page'] ) && '' !== $settings['alumnionline_forum_page'] ) {
		add_submenu_page( 'alumnionline-reunion-website/', esc_html__( 'Message Board', 'alumnionline-reunion-website' ), esc_html__( 'Message Board', 'alumnionline-reunion-website' ), 'manage_options', esc_url( get_permalink( $settings['alumnionline_forum_page'] ) ), null );
	}
	if ( isset( $settings['alumnionline_classmate_site'] ) && 'true' == $settings['alumnionline_classmate_site'] ) {
		$linktext = esc_html__( 'Member Search', 'alumnionline-reunion-website' );
	} else {
		$linktext = esc_html__( 'Classmate Search', 'alumnionline-reunion-website' );
	}
	if ( isset( $settings['alumnionline_classmate_search_page'] ) && '' !== $settings['alumnionline_classmate_search_page'] ) {
		add_submenu_page( 'alumnionline-reunion-website/', $linktext, $linktext, 'manage_options', esc_url( get_permalink( $settings['alumnionline_classmate_search_page'] ) ), null );
	}
}
add_action( 'admin_menu', 'alumnionline_reunion_website_add_menu_links' );

/*********************************************
 * Register Settings
 *********************************************/
function alumnionline_reunion_website_register_settings() {

	register_setting(
		'alumnionline_reunion_website_settings_group',           // Group name.
		'alumnionline_reunion_website_settings',                     // Setting name = html form <input> name on settings form.
		'alumnionline_reunion_website_validater_and_sanitizer'   // Input sanitizer.
	);

	add_settings_section(
		'alumnionline_reunion_website_general_settings_section',                         // ID.
		'',      // Title.
		'alumnionline_reunion_website_general_settings_section_callback',                    // Callback Function.
		'alumnionline-reunion-website-settings'                                          // Page slug.
	);

	add_settings_field(
		'alumnionline_reunion_website_general_settings_field',                           // ID.
		__( 'General Settings', 'alumnionline-reunion-website' ),                  // Title.
		'alumnionline_reunion_website_general_settings_field_callback',                  // Callback function.
		'alumnionline-reunion-website-settings',                                         // Page slug.
		'alumnionline_reunion_website_general_settings_section'                          // Settings Section ID.
	);
}
add_action( 'admin_init', 'alumnionline_reunion_website_register_settings' );

/***************************************************************
 * Validate and sanitize user input before its saved to database
 *************************************************************/
function alumnionline_reunion_website_validater_and_sanitizer( $settings ) {
	foreach ( $settings as $key => $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key_inner => $value_inner ) {
				$settings[ $key ][ $key_inner ] = sanitize_text_field( $value_inner );
			}
		} else {
			$settings[ $key ] = sanitize_text_field( $value );
		}
	}

	// add pro plugin fields.
	$settings = apply_filters( 'alumnionline_reunion_website_validater_and_sanitizer', $settings );

	return $settings;
}

/*********************
 * Get settings
 *********************/
function alumnionline_reunion_website_get_settings() {

	$settings = get_option( 'alumnionline_reunion_website_settings' );

	if ( '' === $settings ) {
		$settings = alumnionline_reunion_website_set_default_settings();
	}

	return $settings;
}
/**
 * Set the default values.
 */
function alumnionline_reunion_website_set_default_settings() {

	// if they are not set create default options and save them.
	$defaults = array(
		'alumnionline_forum_page'            => alumnionline_reunion_website_getpost_id_by_title( __( 'Message Board', 'alumnionline-reunion-website' ) ),
		'alumnionline_classmate_search_page' => alumnionline_reunion_website_getpost_id_by_title( __( 'Classmates Search', 'alumnionline-reunion-website' ) ),
		'pagination_limit'                   => '15',
		'file_size_limit'                    => '2',
		'alumnionline_nav_menu'              => '',
		'alumnionline_public_content'        => '',
		'alumnionline_disable_logging'       => 'true',
		'alumnionline_maincontent_id'        => 'maincontent',
	);

	update_option( 'alumnionline_reunion_website_settings', $defaults );

	return $defaults;
}


/**************************************************
 * Get post id
 * ************************************************ */
function alumnionline_reunion_website_getpost_id_by_title( $title ) {
	$posts = get_posts(
		array(
			'post_type'   => 'page',
			'title'       => $title,
			'post_status' => 'all',
			'numberposts' => 1,
		)
	);

	if ( ! empty( $posts ) ) {
		return $posts[0]->ID;
	}
}
/**************************************************
 * Callback function for General Settings section
 * ************************************************ */
function alumnionline_reunion_website_general_settings_section_callback() {
	$content = '';
}

/***************************************************
 * Callback function for General Settings field
 * *********************************************** */
function alumnionline_reunion_website_general_settings_field_callback() {

	$settings = alumnionline_reunion_website_get_settings();
	if ( ! is_array( $settings ) || count( $settings ) < 1 || ! isset( $settings['alumnionline_nav_menu'] ) ) {
		$settings = alumnionline_reunion_website_set_default_settings();
	}
	?>
<div class="alumnionline-reunion-website-setup-instructions">
	<p>
		<?php esc_html_e( 'When activated AlumniOnline Reunion Website creates the necessary pages, post types and menu links, sets the default role to "AlumniOnline Reunion Website User" and provides the necessary permissions to use the features of the website. The following steps should be completed to finish configuring your website:', 'alumnionline-reunion-website' ); ?>
	</p>
	<ol>
		<li><?php esc_html_e( 'Set the main menu value under the "Settings" tab on this page.', 'alumnionline-reunion-website' ); ?>
		</li>
		<li><?php esc_html_e( 'Review or change the default message board and classmate search pages.', 'alumnionline-reunion-website' ); ?>
		</li>
		<li><?php esc_html_e( 'Review or change additional settings as desired.', 'alumnionline-reunion-website' ); ?>
		</li>
		<li><?php esc_html_e( 'Enable "Anyone can register" on the General Settings page.', 'alumnionline-reunion-website' ); ?>
		</li>
	</ol>
	<p> <a
			href="https://www.alumnionlineservices.com/our-plugins/alumnionline-reunion-website/"><?php esc_html_e( 'The premium plugin includes many more features, including: reunion posts with option to rsvp and submit payments, memorial page to post lost friends, option to post missing classmates and search features to the member list.', 'alumnionline-reunion-website' ); ?></a>

	</p>
</div>
<a href="#" class="alumnionline-reunion-website-settings-section" role="button"
	data-id="alumnionline-reunion-website-pages" aria-expanded="false">
	<?php esc_html_e( 'Basic Settings', 'alumnionline-reunion-website' ); ?>
</a>
<div class="alumnionline-reunion-website-pages alumnionline-reunion-website-fields hidden">

	<p class="description">
		<label for="alumnionline_nav_menu">
			<?php esc_html_e( 'Select the main menu for your website to add register, login, logout and member links: ', 'alumnionline-reunion-website' ); ?>
			<select id="alumnionline_nav_menu" name="alumnionline_reunion_website_settings[alumnionline_nav_menu]">
				<option value="">
					<?php esc_html_e( 'Choose a nav menu ', 'alumnionline-reunion-website' ); ?>
				</option>

				<?php
				$menus = get_registered_nav_menus();
				foreach ( $menus as $location => $description ) {
					echo '<option value="' . esc_attr( $location ) . '"';
					if ( $settings['alumnionline_nav_menu'] === $location ) {
						echo ' selected ';
					}
					echo '>';
					echo esc_attr( $location ) . ' : ' . esc_attr( $description );
					echo '</option>';
				}
				?>
			</select>
		</label>

	<p class="description">
		<label for="alumnionline_forum_page">
			<?php esc_html_e( 'Message Board Page: ', 'alumnionline-reunion-website' ); ?>
			<select id="alumnionline_forum_page" name="alumnionline_reunion_website_settings[alumnionline_forum_page]">
				<option value="">
					<?php esc_html_e( 'Choose a page ', 'alumnionline-reunion-website' ); ?>
				</option>

				<?php
				$pages = get_pages();
				foreach ( $pages as $page ) {

					echo '<option value="' . esc_attr( $page->ID ) . '" ';
					if ( (int) $settings['alumnionline_forum_page'] === $page->ID ) {
						echo ' selected ';
					}
					echo '>';
					echo esc_attr( $page->post_title );
					echo '</option>';

				}
				?>
			</select>
		</label>
		<?php
		if ( isset( $settings['alumnionline_forum_page'] ) && '' !== $settings['alumnionline_forum_page'] ) {
			echo '<a href="' . esc_url( get_permalink( $settings['alumnionline_forum_page'] ) ) . '" target="_blank" title="';
			esc_html_e( 'opens a new tab', 'alumnionline-reunion-website' );
			echo '">';
			echo esc_attr( get_the_title( $settings['alumnionline_forum_page'] ) );
			echo '</a>';
		}
		?>
	</p>

	<p class="description">
		<label for="alumnionline_classmate_search_page">
			<?php esc_html_e( 'Classmate/Member Search Page: ', 'alumnionline-reunion-website-pro' ); ?>
			<select id="alumnionline_classmate_search_page"
				name="alumnionline_reunion_website_settings[alumnionline_classmate_search_page]">
				<option value="">
					<?php esc_html_e( 'Choose a page ', 'alumnionline-reunion-website-pro' ); ?>
				</option>
				<?php
				$pages = get_pages();
				foreach ( $pages as $page ) {
					echo '<option value="' . esc_attr( $page->ID ) . '"';
					if ( (int) $settings['alumnionline_classmate_search_page'] === $page->ID ) {
						echo ' selected ';
					}
					echo '>';
					echo esc_attr( $page->post_title );
					echo '</option>';

				}
				?>
				?>
			</select>
		</label>
		<?php
		if ( isset( $settings['alumnionline_classmate_search_page'] ) && '' !== $settings['alumnionline_classmate_search_page'] ) {
			echo '<a href="';
			echo esc_url( get_permalink( $settings['alumnionline_classmate_search_page'] ) );
			echo '" target="_blank" title="';
			esc_html_e( 'opens a new tab', 'alumnionline-reunion-website' );
			echo '">';
			echo esc_attr( get_the_title( $settings['alumnionline_classmate_search_page'] ) );
			echo '</a>';
		}
		?>
	</p>

	<p class="description">
		<label
			for="pagination_limit"><?php esc_html_e( 'Pagination Limit', 'alumnionline-reunion-website' ); ?>
			<input id="pagination_limit" 
			<?php
			if ( isset( $settings['pagination_limit'] ) ) {
				echo 'value="' . esc_attr( $settings['pagination_limit'] ) . '"';
			}
			?>
			name="alumnionline_reunion_website_settings[pagination_limit]" type="text"></label>
	</p>

	<p class="description">
		<label
			for="file_size_limit"><?php esc_html_e( 'Max File Size', 'alumnionline-reunion-website' ); ?>
			<input id="file_size_limit" 
			<?php
			if ( isset( $settings['file_size_limit'] ) ) {
				echo 'value="' . esc_attr( $settings['file_size_limit'] ) . '"';
			}
			?>
			name="alumnionline_reunion_website_settings[file_size_limit]"
			type="text"><?php esc_html_e( 'MB', 'alumnionline-reunion-website' ); ?></label>
	</p>
	<?php if ( function_exists( 'SimpleLogger' ) ) { ?>
	<p>
		<label for="alumnionline_disable_logging">
			<input type="checkbox" id="alumnionline_disable_logging" value="true"
				name="alumnionline_reunion_website_settings[alumnionline_disable_logging]" 
				<?php
				if ( 'true' === $settings['alumnionline_disable_logging'] ) {
					echo ' checked ';
				}
				?>
			>
			<?php esc_html_e( 'Enable activity log. (Requires the free Simple History plugin.)', 'alumnionline-reunion-website-pro' ); ?>
		</label>
	</p>
	<?php } ?>

	<p class="description">
		<label
			for="alumnionline_maincontent_id"><?php esc_html_e( 'Template Main Content ID', 'alumnionline-reunion-website' ); ?>
			<input id="alumnionline_maincontent_id" 
			<?php
			if ( isset( $settings['alumnionline_maincontent_id'] ) ) {
				echo 'value="' . esc_attr( $settings['alumnionline_maincontent_id'] ) . '"';
			}
			?>
			name="alumnionline_reunion_website_settings[alumnionline_maincontent_id]" type="text"></label>
	</p>
</div>

	<?php
	// add pro plugin fields.
	apply_filters( 'alumnionline_reunion_website_general_settings_field_callback', '' );
	?>

	<?php
}

/**
 * Admin interface renderer
 *
 * @since 1.0
 */
function alumnionline_reunion_website_admin_interface_render() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>

<div class="alumnionline-reunion-website-options-wrap">

	<h1><?php esc_html_e( 'AlumniOnline Reunion Website', 'alumnionline-reunion-website-pro' ); ?>
	</h1>

	<form action="options.php" method="post" class="alumnionline-reunion-website-options-form">
		<?php
			// Output nonce, action, and option_page fields for a settings page.
			settings_fields( 'alumnionline_reunion_website_settings_group' );

			// Prints out all settings sections added to a particular settings page.
			do_settings_sections( 'alumnionline-reunion-website-settings' ); // Page slug.

			// Output save settings button.
			submit_button( __( 'Save Settings', 'alumnionline-reunion-website' ) );
		?>
	</form>
</div>
	<?php
}
?>
