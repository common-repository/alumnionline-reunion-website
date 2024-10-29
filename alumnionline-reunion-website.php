<?php
/**
 * Plugin Name: AlumniOnline Reunion Website
 * Plugin URI: https://www.alumnionlineservices.com/our-plugins/alumnionline-reunion-website/
 * Description: Create a full featured reunion website in minutes. Complete with message board, photo gallery, member list, user management and more.
 * Author: AlumniOnline Web Services LLC
 * Author URI: https://www.alumnionlineservices.com
 * Version: 1.0.8
 * Text Domain: alumnionline-reunion-website
 * License: GPL v2 - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define constants
 *
 * @since 1.0
 */
if ( ! defined( 'ALUMNIONLINE_REUNION_WEBSITE_VERSION_NUM' ) ) {
	define( 'ALUMNIONLINE_REUNION_WEBSITE_VERSION_NUM', '1.0.8' ); // Plugin version constant.
}
if ( ! defined( 'ALUMNIONLINE_REUNION_WEBSITE_FOLDER' ) ) {
	define( 'ALUMNIONLINE_REUNION_WEBSITE_FOLDER', trim( dirname( plugin_basename( __FILE__ ) ), '/' ) ); // Name of the plugin folder.
}
if ( ! defined( 'ALUMNIONLINE_REUNION_WEBSITE_DIR' ) ) {
	define( 'ALUMNIONLINE_REUNION_WEBSITE_DIR', plugin_dir_path( __FILE__ ) ); // Plugin directory absolute path with the trailing slash. Useful for using with includes.
}
if ( ! defined( 'ALUMNIONLINE_REUNION_WEBSITE_URL' ) ) {
	define( 'ALUMNIONLINE_REUNION_WEBSITE_URL', plugin_dir_url( __FILE__ ) ); // URL to the plugin folder with the trailing slash.
}

// include additional files.
require ALUMNIONLINE_REUNION_WEBSITE_DIR . 'res/rest.php';
require ALUMNIONLINE_REUNION_WEBSITE_DIR . 'res/settings.php';
require ALUMNIONLINE_REUNION_WEBSITE_DIR . 'res/functions.php';
require ALUMNIONLINE_REUNION_WEBSITE_DIR . 'res/forum-functions.php';


// Register activation hook (this has to be in the main plugin file or refer bit.ly/2qMbn2O).
register_activation_hook( __FILE__, 'alumnionline_reunion_website_activate_plugin' );

// register deactivation hook.
register_deactivation_hook( __FILE__, 'alumnionline_reunion_website_deactivate_plugin' );

// register uninstall hook.
register_uninstall_hook( __FILE__, 'alumnionline_reunion_website_uninstall_plugin' );

/**
 * Plugin activatation todo list
 *
 * This function runs when user activates the plugin. Used in register_activation_hook in the main plugin file.
 *
 * @since 1.0
 */
function alumnionline_reunion_website_activate_plugin( $network_wide = false ) {
	global $wpdb;

	if ( is_multisite() ) {

		$blog_ids = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			$settings = alumnionline_reunion_website_get_settings();
			alumnionline_reunion_website_create_custom_role();
			alumnionline_reunion_website_create_custom_pages();
			restore_current_blog();
		}
	} else {
		$settings = alumnionline_reunion_website_get_settings();
		alumnionline_reunion_website_create_custom_role();
		alumnionline_reunion_website_create_custom_pages();
	}
}

/**
 * Create plugin pages
 **/
function alumnionline_reunion_website_create_custom_pages() {
	$message_post = array(
		'post_title'   => __( 'Message Board', 'alumnionline-reunion-website' ),
		'post_content' => '',
		'post_status'  => 'publish',
		'post_author'  => 1,
		'post_type'    => 'page',
	);
	if ( ! alumnionline_reunion_website_getpost_id_by_title( __( 'Message Board', 'alumnionline-reunion-website' ) ) ) {
		wp_insert_post( $message_post );
	}

	if ( isset( $settings['alumnionline_classmate_site'] ) && 'true' == $settings['alumnionline_classmate_site'] ) {
		$pagetitle = __( 'Member Search', 'alumnionline-reunion-website' );
	} else {
		$pagetitle = __( 'Classmate Search', 'alumnionline-reunion-website' );
	}

	$post = array(
		'post_title'   => $pagetitle,
		'post_content' => '',
		'post_status'  => 'publish',
		'post_author'  => 1,
		'post_type'    => 'page',
	);
	if ( ! alumnionline_reunion_website_getpost_id_by_title( $pagetitle ) ) {
		wp_insert_post( $post );
	}
}

/**
 * Set default user role
 */
add_filter(
	'pre_option_default_role',
	function ( $default_role ) {
		return 'alumnionline_reunion_website_user';
	}
);

/**
 * Create custom role to secure features
 **/
function alumnionline_reunion_website_create_custom_role() {
	add_role(
		'alumnionline_reunion_website_user',
		__( 'AlumniOnline Reunion Website User', 'alumnionline-reunion-website' ),
		array(
			'read'                                      => true,
			'view_admin_dashboard'                      => false,
			'activate_plugins'                          => false,
			'deactivate_plugins'                        => false,
			'use_alumnionline_reunion_website_features' => true,
		)
	);

	$role = get_role( 'administrator' );
	$role->add_cap( 'use_alumnionline_reunion_website_features' );
}

/**
 * Redirect to settings page
 */
function alumnionline_reunion_website_activation_redirect( $plugin ) {
	if ( 'alumnionline-reunion-website/alumnionline-reunion-website.php' === $plugin ) {

		$url = esc_url( get_site_url() . '/wp-admin/admin.php?page=alumnionline-reunion-website' );
		wp_safe_redirect( $url );

		exit();
	}
}
add_action( 'activated_plugin', 'alumnionline_reunion_website_activation_redirect' );

/**
 * Print direct link to plugin settings in plugins list in admin
 *
 * @since 1.0
 */
function alumnionline_reunion_website_settings_link( $links ) {
	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( 'options-general.php?page=alumnionline-reunion-website' ) . '">' . __( 'Settings', 'alumnionline-reunion-website' ) . '</a>',
		),
		$links
	);
}
add_filter( 'plugin_action_links_' . ALUMNIONLINE_REUNION_WEBSITE_FOLDER . '/alumnionline-reunion-website.php', 'alumnionline_reunion_website_settings_link' );



/**
 * Deactivation
 */
function alumnionline_reunion_website_deactivate_plugin( $deactivate = 0 ) {

	wp_clear_scheduled_hook( 'alumnionline_reunion_website_daily_cron_hook' );
}

/**
 * Uninstall
 **/
function alumnionline_reunion_website_uninstall_plugin( $deactivate = 0 ) {
	global $wpdb;

	if ( is_multisite() ) {
		$blog_ids = $wpdb->get_col( 'SELECT blog_id FROM ' . $wpdb->blogs );
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );

			alumnionline_reunion_website_remove_options();
			wp_clear_scheduled_hook( 'alumnionline_reunion_website_daily_cron_hook' );

			// delete posts.
			$allposts = get_posts(
				array(
					'post_type'   => 'message-post',
					'numberposts' => -1,
				)
			);
			foreach ( $allposts as $eachpost ) {
				alumnionline_reunion_website_delete_all_attached_media( $eachpost->ID );
				wp_delete_post( $eachpost->ID, true );
			}
			// delete terms.
			$taxonomy_name = 'message-category';
			$terms         = get_terms(
				array(
					'taxonomy'   => $taxonomy_name,
					'hide_empty' => false,
				)
			);
			foreach ( $terms as $term ) {
						wp_delete_term( $term->term_id, $taxonomy_name );
			}

			restore_current_blog();
		}

		remove_role( 'alumnionline_reunion_website_user' );

	} else {
		alumnionline_reunion_website_remove_options();
		wp_clear_scheduled_hook( 'alumnionline_reunion_website_daily_cron_hook' );
		remove_role( 'alumnionline_reunion_website_user' );

		// delete posts.
		$allposts = get_posts(
			array(
				'post_type'   => 'message-post',
				'numberposts' => -1,
			)
		);
		foreach ( $allposts as $eachpost ) {
			alumnionline_reunion_website_delete_all_attached_media( $eachpost->ID );
			wp_delete_post( $eachpost->ID, true );
		}
		// delete terms.
		$taxonomy_name = 'message-category';
		$terms         = get_terms(
			array(
				'taxonomy'   => $taxonomy_name,
				'hide_empty' => false,
			)
		);
		foreach ( $terms as $term ) {
					wp_delete_term( $term->term_id, $taxonomy_name );
		}
	}
}


/**
 * Remove options
 */
function alumnionline_reunion_website_remove_options() {
	foreach ( wp_load_alloptions() as $option => $value ) {
		if ( 0 === strpos( $option, 'alumnionline_reunion_website_' ) ) {
			delete_option( $option );
		}
	}
}

/**********************************
 * Upgrade processes
 **********************************/
function alumnionline_reunion_website_upgrader() {

	// Get the current version of the plugin stored in the database.
	$current_ver = get_option( 'alumnionline_reunion_website_version', '0.0' );

	// Return if we are already on updated version.
	if ( version_compare( $current_ver, ALUMNIONLINE_REUNION_WEBSITE_VERSION_NUM, '==' ) ) {
		return;
	}

	// This part will only be excuted once when a user upgrades from an older version to a newer version.

	// Finally add the current version to the database. Upgrade todo complete.
	update_option( 'alumnionline_reunion_website_version', ALUMNIONLINE_REUNION_WEBSITE_VERSION_NUM );
}
	add_action( 'admin_init', 'alumnionline_reunion_website_upgrader' );


/*******************************
 * Enqueue Admin CSS and JS
 ********************************/
function alumnionline_reunion_website_enqueue_css_js( $hook ) {
	$settings = alumnionline_reunion_website_get_settings();
	if ( isset( $settings['file_size_limit'] ) ) {
		$file_size_limit = $settings['file_size_limit'];
	} else {
		$file_size_limit = 2;
	}

	wp_enqueue_style( 'alumnionline-reunion-website-main-css', ALUMNIONLINE_REUNION_WEBSITE_URL . 'main.css', '', ALUMNIONLINE_REUNION_WEBSITE_VERSION_NUM );

	// font awesome.
	if ( ! wp_style_is( 'fontawesome', 'enqueued' ) ) {
		// font awesome.
		wp_register_style( 'alumnionline-reunion-website-fontawesome-styles', 'https://use.fontawesome.com/releases/v5.15.4/css/all.css', array(), '' );
		wp_enqueue_style( 'alumnionline-reunion-website-fontawesome-styles' );
	}

	wp_enqueue_script( 'alumnionline-reunion-website-main-js', ALUMNIONLINE_REUNION_WEBSITE_URL . 'main.js', array( 'jquery' ), false, true );
	wp_localize_script(
		'alumnionline-reunion-website-main-js',
		'alumnionline_reunion_website_Variables',
		array(
			'resturl'       => esc_url_raw( get_rest_url() ),
			'siteurl'       => esc_url_raw( get_site_url() ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'fileselected'  => __( 'File Selected', 'alumnionline-reunion-website' ),
			'fileerrorsize' => sprintf( 'Limit file uploads to %d MB and below.', esc_attr( $file_size_limit ) ),
			'fileerrortype' => __( 'Only jpg files are accepted.', 'alumnionline-reunion-website' ),
			'contenterror'  => __( 'Message content is required', 'alumnionline-reunion-website' ),
			'subjecterror'  => __( 'Subject is required', 'alumnionline-reunion-website' ),
			'search'        => __( 'Search', 'alumnionline-reunion-website' ),
			'max_file_size' => esc_attr( $file_size_limit * 1000000 ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'alumnionline_reunion_website_enqueue_css_js' );
add_action( 'wp_enqueue_scripts', 'alumnionline_reunion_website_enqueue_css_js' );

/**
 * Log activities
 */
function alumnionline_reunion_website_event_logger( $event ) {
	$settings = alumnionline_reunion_website_get_settings();
	if ( isset( $settings['alumnionline_disable_logging'] ) && 'true' == $settings['alumnionline_disable_logging'] ) {

		$current_user = wp_get_current_user();
		if ( ( $current_user instanceof WP_User ) ) {
			$event .= ' User: ' . $current_user->user_login;
		}
		if ( function_exists( 'SimpleLogger' ) ) {
			SimpleLogger()->info( $event );
		}
	}
}
