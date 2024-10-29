<?php
/**
 * REST FUNCTIONS
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Register endpoints
 */
add_action(
	'rest_api_init',
	function () {

		register_rest_route(
			'alumnionline_reunion_website/v1',
			'/forumsearch',
			array(
				'methods'             => 'GET',
				'callback'            => 'alumnionline_reunion_website_forum_search_rest',
				'permission_callback' => function () {
					return alumnionline_reunion_website_secure_by_role();
				},
			)
		);

		register_rest_route(
			'alumnionline_reunion_website/v1',
			'/deletemessage',
			array(
				'methods'             => 'GET',
				'callback'            => 'alumnionline_reunion_website_forum_delete_message_rest',
				'permission_callback' => function () {
					return current_user_can( 'use_alumnionline_reunion_website_features' );
				},
			)
		);

		register_rest_route(
			'alumnionline_reunion_website/v1',
			'/changeview',
			array(
				'methods'             => 'GET',
				'callback'            => 'alumnionline_reunion_website_change_view_rest',
				'permission_callback' => function () {
					return alumnionline_reunion_website_secure_by_role();
				},
			)
		);

		register_rest_route(
			'alumnionline_reunion_website/v1',
			'/postmessage',
			array(
				'methods'             => 'POST',
				'callback'            => 'alumnionline_reunion_website_forum_postmessage_rest',
				'permission_callback' => function () {
					return current_user_can( 'use_alumnionline_reunion_website_features' );
				},
			)
		);

		register_rest_route(
			'alumnionline_reunion_website/v1',
			'/getmessage',
			array(
				'methods'             => 'GET',
				'callback'            => 'alumnionline_reunion_website_forum_getmessage_rest',
				'permission_callback' => function () {
					return alumnionline_reunion_website_secure_by_role();
				},
			)
		);
	}
);
/**
 * Remove message
 */
function alumnionline_reunion_website_forum_delete_message_rest() {

	check_ajax_referer( 'wp_rest', '_wpnonce' );

	if ( ! isset( $_GET['deleteid'] ) ) {
		return;
	} else {
		$messageid = sanitize_text_field( wp_unslash( $_GET['deleteid'] ) );
		$message   = alumnionline_reunion_website_remove_post( $messageid );

		echo '<div id="alumnionline_reunion_website_results">';
		echo esc_attr( $message );
		echo '</div>';

	}
	die();
}

/**
 * Get message
 **/
function alumnionline_reunion_website_forum_getmessage_rest() {

	check_ajax_referer( 'wp_rest', '_wpnonce' );

	if ( ! isset( $_GET['messageid'] ) ) {
		return;
	} else {
		$messageid = sanitize_text_field( wp_unslash( $_GET['messageid'] ) );

		$values = alumnionline_reunion_website_retrieve_message_values( $messageid );
		foreach ( $values as $key => $value ) {
			echo '<value id="alumnionline-reunion-website-values-' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</value>';
		}
	}
	die();
}

/**
 * Search
 **/
function alumnionline_reunion_website_forum_search_rest() {

	check_ajax_referer( 'wp_rest', '_wpnonce' );

	if ( ! isset( $_GET['keyword'] ) ) {
		return;
	} else {
		$keyword = sanitize_text_field( wp_unslash( $_GET['keyword'] ) );
		alumnionline_reunion_website_search_view( $keyword );

	}
	die();
}

/**
 * Change view
 **/
function alumnionline_reunion_website_change_view_rest() {

	check_ajax_referer( 'wp_rest', '_wpnonce' );

	if ( ! isset( $_GET['type'] ) || ! isset( $_GET['value'] ) ) {
		die();
	}

	$offset = 0;

	// display category screen.
	if ( 'category' === $_GET['type'] ) {
		$category_id = sanitize_text_field( wp_unslash( $_GET['value'] ) );

		if ( isset( $_GET['offset'] ) ) {
			$offset = sanitize_text_field( wp_unslash( $_GET['offset'] ) );
		}

		alumnionline_reunion_website_category_view( $category_id, $offset );
	}
	// display home screen.
	if ( 'home' === $_GET['type'] ) {
		alumnionline_reunion_website_forum_index_content();
	}

	// display messages.
	if ( 'message' === $_GET['type'] ) {
		$messageid = sanitize_text_field( wp_unslash( $_GET['value'] ) );
		if ( isset( $_GET['offset'] ) ) {
			$offset = sanitize_text_field( wp_unslash( $_GET['offset'] ) );
		}
		alumnionline_reunion_website_topic_view( $messageid, $offset );
	}

	die();
}

/**
 * Post message
 **/
function alumnionline_reunion_website_forum_postmessage_rest() {
	check_ajax_referer( 'wp_rest', '_wpnonce' );
	$error   = '';
	$editid  = '';
	$replyid = '';

	if ( ! isset( $_POST['alumnionline-reunion-website-post-subject'] ) || '' === $_POST['alumnionline-reunion-website-post-subject'] ) {
		esc_html_e( 'Error: Subject is invalid.', 'alumnionline-reunion-website' );
		die();
	}

	if ( ! isset( $_POST['alumnionline-reunion-website-post-content'] ) || '' === $_POST['alumnionline-reunion-website-post-content'] ) {
		esc_html_e( 'Error: Message is invalid.', 'alumnionline-reunion-website' );
		die();
	}

	if ( ! isset( $_POST['alumnionline-reunion-website-post-category'] ) || '' === $_POST['alumnionline-reunion-website-post-category'] ) {
		esc_html_e( 'Error: Category is invalid.', 'alumnionline-reunion-website' );
		die();
	}

	if ( isset( $_FILES['alumnionline-reunion-website-upload']['name'][0] ) && '' !== $_FILES['alumnionline-reunion-website-upload']['name'][0] ) {
		$error = alumnionline_reunion_website_gallery_photo_validate();
		if ( '' !== $error ) {
			echo esc_attr( $error );
			die();
		}
	}

	if ( isset( $_POST['alumnionline-reunion-website-post-editid'] ) ) {
		$editid = sanitize_text_field( wp_unslash( $_POST['alumnionline-reunion-website-post-editid'] ) );
	}

	if ( isset( $_POST['alumnionline-reunion-website-post-replyid'] ) ) {
		$replyid = sanitize_text_field( wp_unslash( $_POST['alumnionline-reunion-website-post-replyid'] ) );
	}

	$message  = sanitize_text_field( wp_unslash( $_POST['alumnionline-reunion-website-post-content'] ) );
	$category = sanitize_text_field( wp_unslash( $_POST['alumnionline-reunion-website-post-category'] ) );
	$subject  = sanitize_text_field( wp_unslash( $_POST['alumnionline-reunion-website-post-subject'] ) );

	if ( '' !== $editid ) {
		$results = alumnionline_reunion_website_update_message( $subject, $message, $editid, $category );
	} else {
		$results = alumnionline_reunion_website_insert_message( $subject, $message, $category, $replyid );
	}
	if ( $results > 0 ) {
		esc_html_e( 'Message saved successfully. ', 'alumnionline-reunion-website' );

		if ( isset( $_FILES ) && '' !== $_FILES['alumnionline-reunion-website-upload']['name'][0] ) {
			$error = alumnionline_reunion_website_gallery_photo_post( $results );
		}

		if ( isset( $error ) && '' !== $error ) {
			esc_html_e( 'Error: ', 'alumnionline-reunion-website' );
			echo esc_attr( $error );
		}
	} else {
		esc_html_e( 'Error: An unexpected error occured. Please try again. ', 'alumnionline-reunion-website' );
	}

	die();
}
