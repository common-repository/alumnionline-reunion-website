<?php
/*************************************
// FORUM FUNCTIONS
 **************************************/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *  Count posts
 **/
function alumnionline_reunion_website_post_count( $parentid, $category_id ) {
	$args = array(
		'post_type'   => 'message-post',
		'meta_key'    => 'alumnionline_school_id',
		'meta_value'  => alumnionline_reunion_website_get_user_meta_school_id(),
		'numberposts' => -1,
		'tax_query'   => array(
			array(
				'taxonomy' => 'message-category',
				'field'    => 'id',
				'terms'    => array( $category_id ),
				'operator' => 'IN',
			),
		),
	);

	if ( 0 < $parentid ) {
		$args['post_parent'] = $parentid;
	}

	$posts = get_posts( $args );

	$postcount = count( $posts );
	if ( 0 < $parentid ) {
		++$postcount;
	}

	return $postcount;
}
/*************************************
 * Count topics
 **************************************/
function alumnionline_reunion_website_topics_count( $category_id ) {

	$posts = get_posts(
		array(
			'post_type'   => 'message-post',
			'meta_key'    => 'alumnionline_school_id',
			'meta_value'  => alumnionline_reunion_website_get_user_meta_school_id(),
			'numberposts' => -1,
			'post_parent' => 0,
			'tax_query'   => array(
				array(
					'taxonomy' => 'message-category',
					'field'    => 'id',
					'terms'    => array( $category_id ),
					'operator' => 'IN',
				),
			),
		)
	);
	return count( $posts );
}
/*************************************
 * Edit post
 **************************************/
function alumnionline_reunion_website_update_message( $subject, $message, $message_id, $category ) {

	if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
		return;
	} else {
		$documentroot = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) );
		require_once $documentroot . '/wp-load.php';
	}

	$post = get_post( $message_id );

	if ( ! alumnionline_reunion_website_secure_content( $post->post_author ) ) {
		return 0;
	}

	$my_post = array(
		'ID'           => $message_id,
		'post_title'   => $subject,
		'post_content' => $message,
	);

	// Update the post into the database.
	wp_update_post( $my_post );
	wp_remove_object_terms( $message_id, array( $category ), 'message-category' );
	wp_set_post_terms( $message_id, array( $category ), 'message-category' );

		// log activity.
		alumnionline_reunion_website_event_logger( 'Message Post: ' . $message );

	return $message_id;
}

/**
 * Insert post
 **/
function alumnionline_reunion_website_insert_message( $subject, $message, $category, $replyid ) {

	if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
		return;
	} else {
		$documentroot = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) );
		require_once $documentroot . '/wp-load.php';
	}

	$post = array(
		'post_title'   => $subject,
		'post_content' => $message,
		'post_status'  => 'publish',
		'post_parent'  => $replyid,
		'post_author'  => get_current_user_id(),
		'post_type'    => 'message-post',
	);
	wp_insert_post( $post );

	// log activity.
	alumnionline_reunion_website_event_logger( 'Message Post: ' . $message );

	$latest_post = get_posts( 'post_type=message-post&numberposts=1' );

	// update school id.
	$school_id = get_user_meta( get_current_user_id(), 'alumnionline_school_id', true );
	if ( '' == $school_id ) {
		$school_id = 0;
	}
	update_post_meta( $latest_post[0]->ID, 'alumnionline_school_id', $school_id );

	wp_set_post_terms( $latest_post[0]->ID, array( $category ), 'message-category' );

	return $latest_post[0]->ID;

	return 0;
}

/**
 * Display last post
 **/
function alumnionline_reunion_website_last_post_check( $category, $parentid ) {

	$values = array();

	$posts = get_posts(
		array(
			'post_type'   => 'message-post',
			'meta_key'    => 'alumnionline_school_id',
			'meta_value'  => alumnionline_reunion_website_get_user_meta_school_id(),
			'numberposts' => 1,
			'post_status' => 'publish',
			'post_parent' => $parentid,
			'tax_query'   => array(
				array(
					'taxonomy' => 'message-category',
					'field'    => 'id',
					'terms'    => array( $category ),
					'operator' => 'IN',
				),
			),
		)
	);
	foreach ( $posts as $post ) {
		$values['last_post_id'] = $post->ID;
		$values['post_date']    = gmdate( 'n-j-Y h:i:s A', strtotime( $post->post_date ) );

		$user = get_user_by( 'id', $post->post_author );
		if ( '' !== $user->user_nicename ) {
			$username = $user->user_nicename;
		} elseif ( '' !== $user->display_name ) {
			$username = $user->display_name;
		} elseif ( '' !== $user->user_login ) {
			$username = $user->user_login;
		}

		$values['post_name'] = $username;

	}

	if ( count( $values ) < 1 && 0 !== $parentid ) {
		$post = get_post( $parentid );
		if ( isset( $post ) ) {
			$values['last_post_id'] = $post->ID;
			$values['post_date']    = gmdate( 'n-j-Y h:i:s A', strtotime( $post->post_date ) );

			$user = get_user_by( 'id', $post->post_author );
			if ( '' !== $user->user_nicename ) {
				$username = $user->user_nicename;
			} elseif ( '' !== $user->display_name ) {
				$username = $user->display_name;
			} elseif ( '' !== $user->user_login ) {
				$username = $user->user_login;
			}

			$values['post_name'] = $username;
		}
	}

		return $values;
}

/**
 * Remove post
 **/
function alumnionline_reunion_website_remove_post( $messageid ) {
	global $wpdb;

	$post = get_post( $messageid );

	if ( ! is_object( $post ) ) {
		return;
	}

	if ( alumnionline_reunion_website_secure_content( $post->post_author ) ) {

		$args = array(
			'post_parent' => $messageid,
			'post_type'   => 'message-post',
			'meta_key'    => 'alumnionline_school_id',
			'meta_value'  => get_user_meta( get_current_user_id(), 'alumnionline_school_id', true ),
		);

		$posts = get_posts( $args );

		if ( is_array( $posts ) && count( $posts ) > 0 ) {

			// Delete all the Children of the Parent Page.
			foreach ( $posts as $post ) {
				alumnionline_reunion_website_delete_all_attached_media( $post->ID );
				wp_delete_post( $post->ID, true );

			}
		}

		// Delete the Parent Page.
		alumnionline_reunion_website_delete_all_attached_media( $messageid );
		wp_delete_post( $messageid, true );

		return __( 'Message removed', 'alumnionline-reunion-website' );
	}
		return __( 'You are not allowed to delete this post', 'alumnionline-reunion-website' );
}
/**
 * Remove attachments
 **/
function alumnionline_reunion_website_delete_all_attached_media( $post_id ) {

		$attachments = get_attached_media( '', $post_id );

	foreach ( $attachments as $attachment ) {
		wp_delete_attachment( $attachment->ID, 'true' );
	}
}

	/**
	 * Create gallary code
	 **/
function alumnionline_reunion_website_create_gallery_code( $post_id ) {

	$attachments = get_attached_media( '', $post_id );
	$ids         = '';
	foreach ( $attachments as $attachment ) {
		$ids .= esc_attr( $attachment->ID ) . ', ';
	}

	if ( '' != $ids ) {
		$gallery = '[gallery ids="' . rtrim( $ids, ',' ) . '" link="file"]';
		return $gallery;
	}
}

/**
 * Secure content
 */
function alumnionline_reunion_website_secure_content( $memberid ) {
	if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
		return;
	} else {
		$documentroot = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) );
		require_once $documentroot . '/wp-load.php';
	}

	if ( current_user_can( 'manage_options' ) ) {
		return 1;
	} elseif ( get_current_user_id() == $memberid ) {
		return 1;
	}

	return 0;
}

/***
 * Display post popup
 ***/
function alumnionline_reunion_website_display_post_overlay() {

	if ( ! current_user_can( 'use_alumnionline_reunion_website_features' ) ) {
		return;
	}

	echo '<div id="alumnionline_reunion_website_post_message_div" class="alumnionline-reunion-website-modal-wrapper" aria-label="' . esc_html__( 'Post Message', 'alumnionline-reunion-website' ) . '" role="dialog" aria-modal="true">

	<div class="alumnionline-reunion-website-modal-inner">
	<div id="alumnionline-reunion-website-post-status-message" class="alumnionline-reunion-website-status-message" aria-live="polite"></div>
	<a class="alumnionline-reunion-website-modal-close" href="#" role="button"><i aria-hidden="true" class="fas fa-times"></i><span class="screen-reader-text">' . esc_html__( 'Close', 'alumnionline-reunion-website' ) . '</span></a>
	<form action="" method="post" name="alumnionline_reunion_website_post_message_form" id="alumnionline_reunion_website_post_message_form" class="alumnionline_reunion_website_forms" enctype="multipart/form-data">';

	echo '<p><label for="alumnionline-reunion-website-post-category">' . esc_html__( 'Category:', 'alumnionline-reuion-website' ) . '</label>
			<select name="alumnionline-reunion-website-post-category" id="alumnionline-reunion-website-post-category">';
			$terms = get_terms(
				array(
					'taxonomy'   => 'message-category',
					'hide_empty' => false,
				)
			);
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

		foreach ( $terms as $term ) {
			echo '<option value="';
			echo esc_attr( $term->term_id );
			echo '">';
			echo esc_attr( $term->name );
			echo '</option>';
		}
	}
				echo '</select></p>
			  <label for="alumnionline-reunion-website-post-subject">' . esc_html__( 'Subject:', 'alumnionline-reunion-website' ) . '</label>
			 <input name="alumnionline-reunion-website-post-subject" type="text" id="alumnionline-reunion-website-post-subject" value="" size="40" maxlength="45" />
		  </p><p><label for="alumnionline-reunion-website-post-content">' . esc_html__( 'Message:', 'alumnionline-reunion-website' ) . '</label><br>
			<textarea id="alumnionline-reunion-website-post-content" name="alumnionline-reunion-website-post-content" rows="5" cols="45" ></textarea><br>';

			$settings = alumnionline_reunion_website_get_settings();
	if ( ! isset( $settings['alumnionline_disable_photos'] ) || 'false' == $settings['alumnionline_disable_photos'] || '' == $settings['alumnionline_disable_photos'] ) {
		echo '<p><label for="alumnionline-reunion-website-upload">' . esc_html__( 'Choose Photos: (JPG Only)', 'alumnionline-reunion-website' ) . '<input name="alumnionline-reunion-website-upload[]" id="alumnionline-reunion-website-upload" type="file" accept="text/jpg" multiple /></label></p>';
	}
	echo '<p>';
	if ( current_user_can( 'manage_options' ) ) {
		echo ' <a href="#" class="alumnionline_reunion_website_post_edit_link alumnionline-reunion-website-button">' . esc_html__( 'Edit in Wordpess', 'alumnionline-reunion-website' ) . '</a>';
	}

			echo '<input id="alumnionline-reunion-website-post-btn" type="Submit" name="alumnionline-reunion-website-post-btn" value="' . esc_html__( 'Save', 'alumnionline-reunion-website' ) . '">';

	echo '</p>';
			echo '</form>
			</div>
			</div>';
}

/**
 * Display forum index
 ***/
function alumnionline_reunion_website_forum_index() {
	echo '<div id="alumnionline-reunion-website-view-forum">';

	echo '<h2 id="pageName">' . esc_html__( 'Message Board', 'alumnionline-reunion-website' ) . ' <span id="alumnionline-reunion-website-category-title"><span></h2>';
		echo '<div id="alumnionline-reunion-website-forum-status-message" class="alumnionline-reunion-website-status-message" aria-live="polite"></div>';
		echo '<div class="alumnionline-reunion-website-button-bar">';
		echo '<button class="alumnionline-reunion-website-changeview alumnionline-reunion-website-home-view" data-type="home"> <i class="fas fa-home" aria-hidden="true"></i> ' . esc_html__( 'HOME', 'alumnionline-reunion-website' ) . '</button> ';

	if ( current_user_can( 'use_alumnionline_reunion_website_features' ) ) {
		echo '<button data-id="alumnionline_reunion_website_post_message_div" title="' . esc_html__( 'opens dialog', 'alumnionline-reunion-website' ) . '" class="alumnionline-reunion-website-modal-open alumnionline-reunion-website-post-button"><i class="fas fa-plus-circle" aria-hidden="true"></i> ' . esc_html__( 'POST', 'alumnionline-reunion-website' ) . '</button> ';
	}

		echo '<button class="alumnionline-reunion-website-modal-open" data-id="alumnionline_reunion_website_search_div"><i class="fas fa-search" aria-hidden="true"></i> ' . esc_html__( 'SEARCH', 'alumnionline-reunion-website' ) . '</button> ';
		echo '</div>';
		echo '<div id="alumnionline_reunion_website_search_div" class="alumnionline-reunion-website-modal-wrapper" aria-label="' . esc_html__( 'Search Message Board', 'alumnionline-reunion-website' ) . '" role="dialog" aria-modal="true">';
		echo '<div class="alumnionline-reunion-website-modal-inner">
	<div id="alumnionline-reunion-website-search-status-message" class="alumnionline-reunion-website-status-message" aria-live="polite"></div>
	<a class="alumnionline-reunion-website-modal-close" href="#" role="button"><i aria-hidden="true" class="fas fa-times"></i><span class="screen-reader-text">' . esc_html__( 'Close', 'alumnionline-reunion-website' ) . '</span></a>';
		echo '<form name="alumnionline-reunion-website-searchform" class="alumnionline_reunion_website_forms" id="alumnionline-reunion-website-searchform" method="get" action="#" role="search" aria-label="' . esc_html__( 'Search Message Board', 'alumnionline-reunion-website' ) . '">';

		echo '<label for="alumnionline-reunion-website-search-keyword">' . esc_html__( 'Keyword:', 'alumnionline-reunion-website' ) . '</label><br><input type="text" name="keyword" id="alumnionline-reunion-website-search-keyword" value="';
		echo '">';
		echo '<button id="alumnionline-reunion-website-search-btn" >' . esc_html__( 'Search', 'alumnionline-reunion-website' ) . '</button>';

		echo '</form>
		</div>
		</div>';

		alumnionline_reunion_website_forum_index_content();
		echo '</div>';
}

/**
 * Display forum index content
 **/
function alumnionline_reunion_website_forum_index_content() {
	global $wpdb;
	$limit = 15;
	echo '<span class="alumnionline-reunion-website-refresh"><a href="#" data-type="home" class="alumnionline-reunion-website-changeview alumnionline-reunion-website-refreshbtn alumnionline-reunion-website-action-button" role="button"><i class="fas fa-sync" aria-hidden="true"></i> ' . esc_html__( 'REFRESH', 'alumnionline-reunion-website' ) . '</a></span>';
	echo '<div id="alumnionline-reunion-website-view-content">';
	echo '<table class="alumnionline-reunion-website-table alumnionline-reunion-website-index">
<tr>';
	echo '<th>' . esc_html__( 'Forum', 'alumnionline-reunion-website' ) . '</th>';
	echo '<th>' . esc_html__( 'Topics', 'alumnionline-reunion-website' ) . '</th>';
	echo '<th>' . esc_html__( 'Posts', 'alumnionline-reunion-website' ) . '</th>';
	echo '<th>' . esc_html__( 'Last Post', 'alumnionline-reunion-website' ) . '</th>';
	echo '</tr>';

	$terms = get_terms(
		array(
			'taxonomy'   => 'message-category',
			'hide_empty' => false,
		)
	);
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {

		foreach ( $terms as $term ) {

			$last_post    = '';
			$last_post_id = '';
			$posts        = 0;
			$topics       = 0;
			$member       = '';

			// last post data.
			$values = alumnionline_reunion_website_last_post_check( esc_attr( $term->term_id ), 0 );
			if ( $values ) {
				$last_post_id = $values['last_post_id'];
				$last_post    = $values['post_date'];
				$member       = $values['post_name'];
			}

			$topics = alumnionline_reunion_website_topics_count( $term->term_id );
			$posts  = alumnionline_reunion_website_post_count( 0, $term->term_id );
			echo '<tr>
			  <td><a href="#" data-categoryid="' . esc_attr( $term->term_id ) . '" data-category="' . esc_attr( $term->name ) . '" class="alumnionline-reunion-website-changeview" role="button">' . esc_attr( $term->name ) . '</a>
				<br />' . esc_attr( $term->description ) . '
			 </td>
			 </td>
			 <td>' . esc_attr( $topics ) . '</td>
			 <td>' . esc_attr( $posts ) . '</td>';
			echo '<td>';
			if ( '' !== $last_post_id ) {
				echo esc_attr( $last_post ) . ' - ' . esc_attr( $member );
			}
			echo '</td>';
			echo '</tr>';
		}
	}

	echo '</table>';

	if ( current_user_can( 'use_alumnionline_reunion_website_features' ) ) {
		alumnionline_reunion_website_display_post_overlay();
	}

	echo '</div>';
}

/**
 * Display topic
 **/
function alumnionline_reunion_website_topic_view( $parentid, $offset ) {

	$settings = alumnionline_reunion_website_get_settings();
	$limit    = $settings['pagination_limit'];
	echo '<a href="#" data-messageid="' . esc_attr( $parentid ) . '" class="alumnionline-reunion-website-changeview alumnionline-reunion-website-changeview-hidden" role="button" aria-hidden="true" tabindex="-1" style="display:none;">Trigger Link</a>';

	echo '<span class="alumnionline-reunion-website-refresh"><a href="#" data-messageid="' . esc_attr( $parentid ) . '" class="alumnionline-reunion-website-changeview alumnionline-reunion-website-refreshbtn alumnionline-reunion-website-action-button" role="button"><i class="fas fa-sync" aria-hidden="true"></i> ' . esc_html__( 'REFRESH', 'alumnionline-reunion-website' ) . '</a></span>';

	$count = 0;
	echo '<table class="alumnionline-reunion-website-table alumnionline-reunion-website-topicview">
	  <tr>
	  <th>' . esc_html__( 'Author', 'alumnionline-reunion-website' ) . '</th>
		<th>' . esc_html__( 'Subject', 'alumnionline-reunion-website' ) . '</th>
		<th>' . esc_html__( 'Post', 'alumnionline-reunion-website' ) . '</th>
		<th>' . esc_html__( 'Actions', 'alumnionline-reunion-website' ) . '</th>
	  </tr>
	';

	// display parent post.
	if ( '0' === $offset ) {
		$post = get_post( $parentid );
		if ( isset( $post ) ) {
			$user = get_user_by( 'id', $post->post_author );
			if ( '' !== $user->user_nicename ) {
				$username = $user->user_nicename;
			} elseif ( '' !== $user->display_name ) {
				$username = $user->display_name;
			} elseif ( '' !== $user->user_login ) {
				$username = $user->user_login;
			}
			$postcontent = $post->post_content;
			echo '<tr class="alumnionline_reunion_website_post_toppost">';
			echo '<td>' . esc_attr( $username ) . '</td>';
			echo '<td>' . esc_attr( $post->post_title ) . '</td>';
			echo '<td>' . wp_kses_post( apply_filters( 'the_content', $postcontent ) );
			if ( '' != alumnionline_reunion_website_create_gallery_code( $post->ID ) ) {
				echo ' <a class="alumnionline-reunion-website-action-button alumnionline-reunion-website-photo-button" href="' . esc_url( get_the_permalink( $post->ID ) ) . '"><i class="fas fa-image" aria-hidden="true"></i> ' . esc_html__( 'VIEW PHOTOS', 'alumnionline-reunion-website' ) . '</a> ';
			}
			echo '</td>';
			echo '<td>';
			if ( alumnionline_reunion_website_secure_content( $post->post_author ) ) {
				echo '<button data-id="alumnionline_reunion_website_post_message_div" title="' . esc_html__( 'opens dialog', 'alumnionline-reunion-website' ) . '" class="alumnionline-reunion-website-modal-open alumnionline-reunion-website-post-button alumnionline-reunion-website-action-button" data-editid="' . esc_attr( $post->ID ) . '"><i class="fas fa-edit" aria-hidden="true"></i> ' . esc_html__( 'EDIT', 'alumnionline-reunion-website' ) . '</button> ';
				echo '<button class="alumnionline-reunion-website-delete-button alumnionline-reunion-website-action-button" data-deleteid="' . esc_attr( $post->ID ) . '"><i class="fas fa-times" aria-hidden="true"></i> ' . esc_html__( 'DELETE', 'alumnionline-reunion-website' ) . '</button> ';

			}
			echo ' <a class="alumnionline-reunion-website-action-button alumnionline-reunion-website-action-button" href="' . esc_url( get_the_permalink( $post->ID ) ) . '"><i class="fas fa-eye" aria-hidden="true"></i> ' . esc_html__( 'VIEW', 'alumnionline-reunion-website' ) . '</a> ';
			if ( current_user_can( 'use_alumnionline_reunion_website_features' ) ) {
				echo '<span id="alumnionline-reunion-website-replybtn"><button data-id="alumnionline_reunion_website_post_message_div" title="' . esc_html__( 'opens dialog', 'alumnionline-reunion-website' ) . '" class="alumnionline-reunion-website-modal-open alumnionline-reunion-website-post-button alumnionline-reunion-website-replybtn" data-replyid="' . esc_attr( $post->ID ) . '"><i class="fab fa-replyd" aria-hidden="true"></i> ' . esc_html__( 'REPLY', 'alumnionline-reunion-website' ) . '</button> </span>';
			}
			echo '</td>';

			echo '</tr>';
			++$count;
		}
	}

	// display child posts.
	$args = array(
		'posts_per_page' => $limit,
		'post_type'      => 'message-post',
		'offset'         => $offset,
		'post_parent'    => $parentid,
	);

	$posts = get_posts( $args );

	$args      = array(
		'post_type'      => 'message-post',
		'posts_per_page' => -1,
		'post_parent'    => $parentid,
	);
	$totalpost = get_posts( $args );
	$totalrows = count( $totalpost );

	alumnionline_reunion_website_pagination_bar( $offset, $totalrows, $parentid, $limit, 'messageid' );

	foreach ( $posts as $post ) {
		++$count;
		$user = get_user_by( 'id', $post->post_author );
		if ( '' !== $user->user_nicename ) {
			$username = $user->user_nicename;
		} elseif ( '' !== $user->display_name ) {
			$username = $user->display_name;
		} elseif ( '' !== $user->user_login ) {
			$username = $user->user_login;
		}

		echo '<tr>';
		echo '<td>' . esc_attr( $username ) . '</td>';
		echo '<td>' . esc_attr( $post->post_title ) . '</td>';
		echo '<td>';
		echo wp_kses_post( apply_filters( 'the_content', $postcontent ) );
		if ( '' != alumnionline_reunion_website_create_gallery_code( $post->ID ) ) {
			echo ' <a class="alumnionline-reunion-website-action-button alumnionline-reunion-website-photo-button" href="' . esc_url( get_the_permalink( $post->ID ) ) . '"><i class="fas fa-image" aria-hidden="true"></i> ' . esc_html__( 'VIEW PHOTOS', 'alumnionline-reunion-website' ) . '</a> ';
		}
		echo '</td>';
		echo '<td>';
		if ( alumnionline_reunion_website_secure_content( $post->post_author ) ) {
			echo '<button data-id="alumnionline_reunion_website_post_message_div" title="' . esc_html__( 'opens dialog', 'alumnionline-reunion-website' ) . '" class="alumnionline-reunion-website-modal-open alumnionline-reunion-website-post-button alumnionline-reunion-website-action-button" data-editid="' . esc_attr( $post->ID ) . '"><i class="fas fa-edit" aria-hidden="true"></i> ' . esc_html__( 'EDIT', 'alumnionline-reunion-website' ) . '</button> ';
			echo '<button class="alumnionline-reunion-website-delete-button alumnionline-reunion-website-action-button" data-deleteid="' . esc_attr( $post->ID ) . '"><i class="fas fa-times" aria-hidden="true"></i> ' . esc_html__( 'DELETE', 'alumnionline-reunion-website' ) . '</button> ';
		}
		echo ' <a class="alumnionline-reunion-website-action-button" href="' . esc_url( get_the_permalink( $post->ID ) ) . '"><i class="fas fa-eye" aria-hidden="true"></i> ' . esc_html__( 'VIEW', 'alumnionline-reunion-website' ) . '</a> ';
		echo '</td>';

		echo '</tr>';
	}
	echo '</table>';

	if ( current_user_can( 'use_alumnionline_reunion_website_features' ) ) {
		alumnionline_reunion_website_display_post_overlay();
	}

	echo '<div id="alumnionline_reunion_website_results">' . esc_attr( $count ) . ' ' . esc_html__( 'messages displayed.', 'alumnionline-reunion-website' ) . '</div>';
}

/**
 * Display pagination
 */
function alumnionline_reunion_website_pagination_bar( $offset, $totalrows, $recordid, $limit, $type ) {

	// Set $begin and $end to record range of the current page.
	$begin = ( $offset + 1 );
	$end   = ( $begin + ( $limit - 1 ) );

	if ( $end > $totalrows ) {
		$end = $totalrows;
	}

	if ( $totalrows < $limit ) {
		return;
	}

	echo '<p class="alumnionline-reunion-website-pagination">';

	// Don't display PREV link if on first page.
	if ( 0 !== $offset ) {
		$prevoffset = $offset - $limit;
		echo '<a href="#" class="alumnionline-reunion-website-changeview" data-' . esc_attr( $type ) . '="' . esc_attr( $recordid ) . '" data-offset="' . esc_attr( $prevoffset ) . '" role="button"> <span class="fa fa-chevron-left"></span> ' . esc_html__( 'Previous', 'alumnionline-reunion-website' ) . '</a>';
		echo ' | ';
	}

	// Calculate total number of pages in result.
	$pages = intval( $totalrows / $limit );

	// $pages now contains total number of pages needed unless there is a remainder from division.
	if ( $totalrows % $limit ) {
		// has remainder so add one page.
		++$pages;
	}
			// Now loop through the pages to create numbered links.
	// ex. 1 2 3 4 5 NEXT.
	for ( $i = 1;$i <= $pages;$i++ ) {
		$top    = ( $offset / $limit + 5 );
		$bottom = ( $offset / $limit - 5 );
		if ( 1 > $bottom ) {
			$bottom = 1;
			$top    = 11;
		}

		$newoffset = $limit * ( $i - 1 );
		if ( $i < $top && $i >= $bottom ) {

			if ( ( $offset / $limit ) === ( $i - 1 ) ) {
				$class = 'alumnionline-reunion-website-changeview-current';
			} else {
				$class = '';
			}

			echo '<a href="#" class="alumnionline-reunion-website-changeview ' . esc_attr( $class ) . '" data-' . esc_attr( $type ) . '="' . esc_attr( $recordid ) . '" data-offset="' . esc_attr( $newoffset ) . '" role="button"';
			if ( ( $offset / $limit ) === ( $i - 1 ) ) {
				echo ' aria-current="true" ';
			}
			echo '>' . esc_attr( $i );

			if ( ( $offset / $limit ) === ( $i - 1 ) ) {
				echo '<span class="screen-reader-text">';
				echo esc_html__( ' Selected', 'alumnionline-reunion-website' );
				echo '</span>';
			}
			echo '</a>';
			echo ' | ';
		}
	}
	// Check to see if current page is last page.
	if ( ! ( ( ( $offset / $limit ) + 1 ) === $pages ) && 1 !== $pages && $totalrows > $limit ) {
		// Not on the last page yet, so display a NEXT Link.
		$newoffset = $offset + $limit;
		echo ' <a href="#" class="alumnionline-reunion-website-changeview" data-' . esc_attr( $type ) . '="' . esc_attr( $recordid ) . '" data-offset="' . esc_attr( $newoffset ) . '" role="button">' . esc_html__( 'Next', 'alumnionline-reunion-website' ) . ' <span class="fa fa-chevron-right"></span></a>';
	}
	echo '</p>';
}

/**
 * Display category
 **/
function alumnionline_reunion_website_category_view( $category_id, $offset ) {
	global $wpdb;

	$settings = alumnionline_reunion_website_get_settings();
	$limit    = $settings['pagination_limit'];

	$args = array(
		'post_type'   => 'message-post',
		'meta_key'    => 'alumnionline_school_id',
		'meta_value'  => alumnionline_reunion_website_get_user_meta_school_id(),
		'numberposts' => -1,
		'post_parent' => 0,
		'offset'      => $offset,
		'tax_query'   => array(
			array(
				'taxonomy' => 'message-category',
				'field'    => 'id',
				'terms'    => array( $category_id ),
				'operator' => 'IN',
			),
		),
	);

	$totalpost = get_posts( $args );
	$totalrows = count( $totalpost );

	$term_object = get_term( $category_id );

	echo '<span class="alumnionline-reunion-website-refresh"><a href="#" data-categoryid="' . esc_attr( $category_id ) . '" data-category="' . esc_attr( ucfirst( $term_object->slug ) ) . '" class="alumnionline-reunion-website-changeview alumnionline-reunion-website-action-button alumnionline-reunion-website-refreshbtn" role="button"><i class="fas fa-sync" aria-hidden="true"></i> ' . esc_html__( 'REFRESH', 'alumnionline-reunion-website' ) . '</a></span>';
	alumnionline_reunion_website_pagination_bar( $offset, $totalrows, $category_id, $limit, 'categoryid' );
	echo '
<table class="alumnionline-reunion-website-table alumnionline-reunion-website-categoryview">
  <tr>
    <th>' . esc_html__( 'Subject', 'alumnionline-reunion-website' ) . '</th>
	<th>' . esc_html__( 'Posts', 'alumnionline-reunion-website' ) . '</th>
	<th>' . esc_html__( 'Last Post', 'alumnionline-reunion-website' ) . '</th>
  </tr>
';
	$args['numberposts'] = $limit;
	$posts               = get_posts( $args );

	foreach ( $posts as $post ) {

		// last post data.
		$member    = '';
		$last_post = '';
		$values    = alumnionline_reunion_website_last_post_check( $category_id, $post->ID );
		if ( $values ) {
			$last_post_id = $values['last_post_id'];
			$last_post    = $values['post_date'];
			$member       = $values['post_name'];
		}

		$postcount = alumnionline_reunion_website_post_count( $post->ID, $category_id );

		echo '<tr>';
		echo '<td><a href="#" data-messageid="' . esc_attr( $post->ID ) . '" class="alumnionline-reunion-website-changeview" role="button">' . esc_attr( $post->post_title ) . '</a></td>';
		echo '<td>' . esc_attr( $postcount ) . '</td>';
		echo '<td>';
		if ( '' !== $last_post_id ) {
			echo esc_attr( $last_post ) . ' - ' . esc_attr( $member );
		}
		echo '</td>';
		echo '</tr>';
	}
	echo '</table>';

	if ( current_user_can( 'use_alumnionline_reunion_website_features' ) ) {
		alumnionline_reunion_website_display_post_overlay();
	}

	$resultsfound = count( $posts );
	echo '<div id="alumnionline_reunion_website_results">' . esc_attr( $resultsfound ) . ' ' . esc_html__( 'messages displayed.', 'alumnionline-reunion-website' ) . '</div>';
}


/**
 * Display search results
 **/
function alumnionline_reunion_website_search_view( $keyword ) {
	global $wpdb;

	?>
<table class="alumnionline-reunion-website-table alumnionline-reunion-website-searchview">
	<tr>
		<th>
			<?php esc_html_e( 'Author', 'alumnionline-reunion-website' ); ?>
		</th>
		<th><?php esc_html_e( 'Subject', 'alumnionline-reunion-website' ); ?>
		</th>
		<th><?php esc_html_e( 'Post', 'alumnionline-reunion-website' ); ?>
		</th>
		<th><?php esc_html_e( 'Action', 'alumnionline-reunion-website' ); ?>
		</th>
	</tr>

	<?php
	$posts = get_posts(
		array(
			'post_type'   => 'message-post',
			'meta_key'    => 'alumnionline_school_id',
			'meta_value'  => alumnionline_reunion_website_get_user_meta_school_id(),
			'numberposts' => -1,
			's'           => $keyword,
		)
	);

	foreach ( $posts as $post ) {

		$user = get_user_by( 'id', $post->post_author );
		if ( '' !== $user->user_nicename ) {
			$username = $user->user_nicename;
		} elseif ( '' !== $user->display_name ) {
			$username = $user->display_name;
		} elseif ( '' !== $user->user_login ) {
			$username = $user->user_login;
		}

		echo '<tr>';
		echo '<td>' . esc_attr( $username ) . '</td>';
		echo '<td>' . esc_attr( $post->post_title ) . '</td>';
		echo '<td>' . esc_attr( $post->post_content ) . '</td>';

		echo '<td>';
		if ( alumnionline_reunion_website_secure_content( $post->post_author ) ) {
			echo '<button data-id="alumnionline_reunion_website_post_message_div" title="' . esc_html__( 'opens dialog', 'alumnionline-reunion-website' ) . '" class="alumnionline-reunion-website-modal-open alumnionline-reunion-website-post-button alumnionline-reunion-website-action-button" data-editid="' . esc_attr( $post->ID ) . '"><i class="fas fa-edit" aria-hidden="true"></i> ' . esc_html__( 'EDIT', 'alumnionline-reunion-website' ) . '</button> ';
			echo '<button class="alumnionline-reunion-website-delete-button alumnionline-reunion-website-action-button" data-deleteid="' . esc_attr( $post->ID ) . '"><i class="fas fa-times" aria-hidden="true"></i> ' . esc_html__( 'DELETE', 'alumnionline-reunion-website' ) . '</button> ';
			echo ' <a class="alumnionline-reunion-website-action-button" href="' . esc_url( get_the_permalink( $post->ID ) ) . '"><i class="fas fa-eye" aria-hidden="true"></i> ' . esc_html__( 'VIEW', 'alumnionline-reunion-website' ) . '</a> ';
		}
		echo '</td>';

		echo '</tr>';
	}
	echo '</table>';

	if ( current_user_can( 'use_alumnionline_reunion_website_features' ) ) {
		alumnionline_reunion_website_display_post_overlay();
	}

	$resultsfound = count( $posts );
	echo '<div id="alumnionline_reunion_website_results">' . esc_attr( $resultsfound ) . ' ' . esc_html__( 'messages displayed.', 'alumnionline-reunion-website' ) . '</div>';

	alumnionline_reunion_website_display_post_overlay();
}

/**
 * Return message values
 **/
function alumnionline_reunion_website_retrieve_message_values( $messageid ) {
	global $wpdb;
	$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'posts WHERE ID = %d', $messageid ), ARRAY_A );
	$values  = array();
	foreach ( $results as $row ) {
		if ( alumnionline_reunion_website_secure_content( $row['post_author'] ) ) {
			$values['subject'] = $row['post_title'];
			$values['message'] = $row['post_content'];

			$taxonomy_names = wp_get_object_terms( $row['ID'], 'message-category' );
			if ( ! empty( $taxonomy_names ) ) {
				foreach ( $taxonomy_names as $tax ) {
					$values['category'] = $tax->term_id;
				}
			}
		}
	}
	return $values;
}

/**
 * Add links to menu
 */
add_filter(
	'wp_nav_menu_items',
	function ( $items, $args ) {
		$settings = alumnionline_reunion_website_get_settings();
		if ( $args->theme_location === $settings['alumnionline_nav_menu'] ) {
			$links = '';
			if ( isset( $settings['alumnionline_forum_page'] ) && '' !== $settings['alumnionline_forum_page']
			&& '' !== $settings['alumnionline_forum_page'] ) {
				if ( alumnionline_reunion_website_secure_by_role() ) {
					$links .= '<li class="menu-item menu-item-has-children menu-item-memberlinks"><a href="' . esc_url( get_the_permalink( $settings['alumnionline_forum_page'] ) ) . '">';
					$links .= esc_html__( 'Members', 'alumnionline-reunion-website' );
					$links .= '</a><ul>';
					$links .= '<li class="menu-item menu-item-' . esc_attr( $settings['alumnionline_forum_page'] ) . '"><a href="' . esc_url( get_the_permalink( $settings['alumnionline_forum_page'] ) ) . '">';
					$links .= esc_html__( 'Message Board', 'alumnionline-reunion-website' );
					$links .= '</a></li>';

					if ( isset( $settings['alumnionline_classmate_search_page'] ) && '' !== $settings['alumnionline_classmate_search_page']
					&& '' !== $settings['alumnionline_classmate_search_page'] ) {
						$links .= '<li class="menu-item menu-item-memberlinks"><a href="' . esc_url( get_the_permalink( $settings['alumnionline_classmate_search_page'] ) ) . '">';
						if ( isset( $settings['alumnionline_classmate_site'] ) && 'true' == $settings['alumnionline_classmate_site'] ) {
							$links .= __( 'Member Search', 'alumnionline-reunion-website' );
						} else {
							$links .= __( 'Classmate Search', 'alumnionline-reunion-website' );
						}
						$links .= '</a></li>';
					}

					// add pro plugin fields.
					$links .= apply_filters( 'alumnionline_reunion_website_add_menu_items_callback', '' );
				}
				$links .= '<li class="alumnionline_reunion_website_offscreen">';
				$links .= '<a href="https://www.alumnionline.org/how-to/planning-a-reunion/">';
				$links .= esc_html__( 'Plan a Reunion', 'alumnionline-reunion-website' );
				$links .= '</a>';
				$links .= '</li>';
				$links .= '<li class="menu-item menu-item-loginlink alumnionline_reunion_website_hidemenitem_loggedin">';
				$links .= '<a href="' . esc_url( wp_login_url() ) . '">';
				$links .= esc_html__( 'Log In', 'alumnionline-reunion-website' );
				$links .= '</a>';
				$links .= '</li>';
	
				$links .= '<li class="menu-item menu-item-logoutlink alumnionline_reunion_website_hidemenitem_loggedout">';
				$links .= '<a href="' . esc_url( wp_login_url() ) . '?action=logout">';
				$links .= esc_html__( 'Log Out', 'alumnionline-reunion-website' );
				$links .= '</a>';
				$links .= '</li>';
				$links .= '<li class="menu-item menu-item-registerlink alumnionline_reunion_website_hidemenitem_loggedin">';
				$links .= '<a href="' . esc_url( wp_login_url() ) . '?action=register">';
				$links .= esc_html__( 'Register', 'alumnionline-reunion-website' );
				$links .= '</a>';
				$links .= '</li>';
				$links .= '</ul>';
				$links .= '</li>';
	
			}

			$items .= $links;
		}
		return $items;
	},
	20,
	2
);


/**
 * Process photo uploads
 **/
function alumnionline_reunion_website_gallery_photo_post( $lastpost ) {
	check_ajax_referer( 'wp_rest', '_wpnonce' );

	if ( ! isset( $_SERVER['DOCUMENT_ROOT'] ) || ! isset( $_FILES['alumnionline-reunion-website-upload'] ) ) {
		return;
	} else {
		$documentroot = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) );
		require_once $documentroot . '/wp-load.php';
	}

	$returnvalue = '';

	if ( isset( $_FILES['alumnionline-reunion-website-upload']['name'] ) ) {
		$filenames = array_map( 'sanitize_text_field', $_FILES['alumnionline-reunion-website-upload']['name'] );
		foreach ( $filenames as $key => $value ) {
			if ( $filenames[ $key ] ) {
				$file   = array(
					'name'     => sanitize_file_name( $_FILES['alumnionline-reunion-website-upload']['name'][ $key ] ),
					'type'     => sanitize_text_field( $_FILES['alumnionline-reunion-website-upload']['type'][ $key ] ),
					'tmp_name' => sanitize_text_field( $_FILES['alumnionline-reunion-website-upload']['tmp_name'][ $key ] ),
					'error'    => sanitize_text_field( $_FILES['alumnionline-reunion-website-upload']['error'][ $key ] ),
					'size'     => sanitize_text_field( $_FILES['alumnionline-reunion-website-upload']['size'][ $key ] ),
				);
				$_FILES = array( 'alumnionline-reunion-website-upload' => $file );
				foreach ( $_FILES as $file => $array ) {
					require_once sanitize_text_field( ABSPATH ) . 'wp-admin/includes/image.php';
					require_once sanitize_text_field( ABSPATH ) . 'wp-admin/includes/file.php';
					require_once sanitize_text_field( ABSPATH ) . 'wp-admin/includes/media.php';

					$file_id = media_handle_upload( $file, $lastpost );

					if ( is_wp_error( $file_id ) ) {
						$returnvalue = esc_html__( 'Error saving files.', 'alumnionline-reunion-website' );
					}
				}
			}
		}
	}

	return $returnvalue;
}

/**
 * Validate photos
 **/
function alumnionline_reunion_website_gallery_photo_validate() {
	check_ajax_referer( 'wp_rest', '_wpnonce' );
	$error    = '';
	$settings = alumnionline_reunion_website_get_settings();

	if ( ! isset( $_FILES['alumnionline-reunion-website-upload']['name'] ) || ! isset( $_FILES['alumnionline-reunion-website-upload']['tmp_name'] ) ) {
		return;
	}
	$total_files = count( $_FILES['alumnionline-reunion-website-upload']['name'] );

	for ( $i = 0; $i < $total_files; $i++ ) {

		if ( isset( $_FILES['alumnionline-reunion-website-upload']['tmp_name'][ $i ] ) && file_exists( sanitize_text_field( $_FILES['alumnionline-reunion-website-upload']['tmp_name'][ $i ] ) ) ) {

			if ( array_key_exists( $i, $_FILES['alumnionline-reunion-website-upload']['tmp_name'] ) ) {
				if ( file_exists( sanitize_text_field( $_FILES['alumnionline-reunion-website-upload']['tmp_name'][ $i ] ) ) ) {

					if ( isset( $_FILES['alumnionline-reunion-website-upload']['size'][ $i ] ) && $_FILES['alumnionline-reunion-website-upload']['size'][ $i ] > ( $settings['file_size_limit'] * 1000000 ) ) {
						$error  = __( 'Error: Attachments may not be larger than ', 'alumnionline-reunion-website' ) . esc_attr( $settings['file_size_limit'] );
						$error .= __( ' mb', 'alumnionline-reunion-website' );
					} elseif ( isset( $_FILES['alumnionline-reunion-website-upload']['type'][ $i ] ) && 'image/jpg' !== $_FILES['alumnionline-reunion-website-upload']['type'][ $i ] && 'image/jpeg' !== $_FILES['alumnionline-reunion-website-upload']['type'][ $i ] ) {
						$error  = __( 'Error: Only JPG files are accepted: ', 'alumnionline-reunion-website' );
						$error .= sanitize_text_field( $_FILES['alumnionline-reunion-website-upload']['type'][ $i ] );
					}
				}
			} else {
				$error = __( 'Error: File not found.', 'alumnionline-reunion-website' );
			}
		}
	}

	return $error;
}

/**
 * Create a message post type
 */
function alumnionline_reunion_website_message_post_type() {
	$labels = array(
		'name'               => __( 'Message Posts', 'alumnionline-reunion-website' ),
		'singular_name'      => __( 'Message Post', 'alumnionline-reunion-website' ),
		'add_new'            => __( 'Add New Message Post', 'alumnionline-reunion-website' ),
		'add_new_item'       => __( 'Add New Message Post', 'alumnionline-reunion-website' ),
		'edit_item'          => __( 'Edit Message Post', 'alumnionline-reunion-website' ),
		'new_item'           => __( 'New Message Post', 'alumnionline-reunion-website' ),
		'all_items'          => __( 'All Message Posts', 'alumnionline-reunion-website' ),
		'view_item'          => __( 'View Message Posts', 'alumnionline-reunion-website' ),
		'search_items'       => __( 'Search Message Posts', 'alumnionline-reunion-website' ),
		'featured_image'     => __( 'Featured Image', 'alumnionline-reunion-website' ),
		'set_featured_image' => __( 'Add Featured Image', 'alumnionline-reunion-website' ),
	);
	$args   = array(
		'labels'             => $labels,
		'description'        => '',
		'public'             => true,
		'menu_position'      => 3,
		'supports'           => array( 'title', 'editor', 'thumbnail', 'author', 'custom-fields', 'comments' ),
		'has_archive'        => false,
		'show_in_admin_bar'  => true,
		'show_in_nav_menus'  => false,
		'query_var'          => false,
		'publicly_queryable' => true,
		'taxonomies'         => array( 'message-category' ),
	);
	register_post_type( 'message-post', $args );
}
add_action( 'init', 'alumnionline_reunion_website_message_post_type' );

/**
 * Create a message taxonomy type
 **/
function alumnionline_reunion_website_add_custom_taxonomies() {

	register_taxonomy(
		'message-category',
		'message-post',
		array(
			'hierarchical' => true,
			'labels'       => array(
				'name'              => _x( 'Message Categories', 'taxonomy general name' ),
				'singular_name'     => _x( 'Message Category', 'taxonomy singular name' ),
				'search_items'      => __( 'Search Message Categories', 'alumnionline-reunion-website' ),
				'all_items'         => __( 'All Message Categories', 'alumnionline-reunion-website' ),
				'parent_item'       => __( 'Parent Message Category', 'alumnionline-reunion-website' ),
				'parent_item_colon' => __( 'Parent Message Category:', 'alumnionline-reunion-website' ),
				'edit_item'         => __( 'Edit Message Category', 'alumnionline-reunion-website' ),
				'update_item'       => __( 'Update Message Category', 'alumnionline-reunion-website' ),
				'add_new_item'      => __( 'Add New Message Category', 'alumnionline-reunion-website' ),
				'new_item_name'     => __( 'New Message Category', 'alumnionline-reunion-website' ),
				'menu_name'         => __( 'Message Category', 'alumnionline-reunion-website' ),
			),
			'rewrite'      => array(
				'slug'         => 'message-categories',
				'with_front'   => false,
				'hierarchical' => true,
			),
		)
	);

	// create default terms.
	alumnionline_reunion_website_create_terms();
}
add_action( 'init', 'alumnionline_reunion_website_add_custom_taxonomies', 0 );

/**
 * Create terms
 **/
function alumnionline_reunion_website_create_terms() {
	if ( ! term_exists( 'General Discussion', 'message-category' ) ) {
		wp_insert_term( 'General Discussion', 'message-category' );
	}
	if ( ! term_exists( 'Reunions', 'message-category' ) ) {
		wp_insert_term( 'Reunions', 'message-category' );
	}
	if ( ! term_exists( 'Announcements', 'message-category' ) ) {
		wp_insert_term( 'Announcements', 'message-category' );
	}
	if ( ! term_exists( 'Gallery', 'message-category' ) ) {
		wp_insert_term( 'Gallery', 'message-category' );
	}
	if ( ! term_exists( 'Sports', 'message-category' ) ) {
		wp_insert_term( 'Sports', 'message-category' );
	}
	if ( ! term_exists( 'Looking for Someone?', 'message-category' ) ) {
		wp_insert_term( 'Looking for Someone?', 'message-category' );
	}
	if ( ! term_exists( 'Where do you call home?', 'message-category' ) ) {
		wp_insert_term( 'Where do you call home?', 'message-category' );
	}
}
?>
