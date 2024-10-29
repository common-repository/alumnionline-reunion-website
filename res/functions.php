<?php
/**
 * COMMON FUNCTIONS
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**********************************************************************
 * Redirect after login
 **********************************************************************/
function alumnionline_reunions_plugin_custom_login_redirect( $redirect_to, $request, $user ) {
	// Get the current user's role.
	if ( isset( $user->roles[0] ) ) {
		$user_role = $user->roles[0];
	} else {
		$user_role = 'subscriber';
	}

	$settings = alumnionline_reunion_website_get_settings();
	if ( isset( $settings['alumnionline_forum_page'] ) && '' !== $settings['alumnionline_forum_page'] ) {
		$page = get_the_permalink( $settings['alumnionline_forum_page'] );
	} else {
		$page = '/';
	}

	// Set the URL to redirect users to based on their role.
	if ( 'subscriber' === $user_role ) {
		$redirect_to = esc_url( $page );
	}

	return $redirect_to;
}
add_filter( 'login_redirect', 'alumnionline_reunions_plugin_custom_login_redirect', 10, 3 );

/**
 * Check capability before displaying content
 */
function alumnionline_reunion_website_secure_content_template_redirect() {
	global $post;

	if ( ! isset( $post->post_type ) && ! is_author() ) {
		return;
	}

	$redirect_to = '/';

	$settings = alumnionline_reunion_website_get_settings();

	if ( 'message-post' === $post->post_type && ! alumnionline_reunion_website_secure_by_role() ) {
		wp_safe_redirect( esc_url_raw( $redirect_to ) );
		exit();
	}

	if ( is_author() && ! alumnionline_reunion_website_secure_by_role() ) {
		wp_safe_redirect( esc_url_raw( $redirect_to ) );
		exit();
	}

	if ( isset( $settings['alumnionline_forum_page'] ) && $post->ID == $settings['alumnionline_forum_page'] && ! alumnionline_reunion_website_secure_by_role() ) {
		wp_safe_redirect( esc_url_raw( $redirect_to ) );
		exit();
	}

	if ( isset( $settings['alumnionline_classmate_search_page'] ) && $post->ID == $settings['alumnionline_classmate_search_page'] && ! alumnionline_reunion_website_secure_by_role() ) {
		wp_safe_redirect( esc_url_raw( $redirect_to ) );
		exit();
	}
}
add_action( 'template_redirect', 'alumnionline_reunion_website_secure_content_template_redirect' );



/************************************************************
 * Display forum
 *************************************************************/
function alumnionline_reunions_plugin_content( $content ) {

	if ( ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$settings = alumnionline_reunion_website_get_settings();
	if ( ! alumnionline_reunion_website_secure_by_role() ) {
		return $content;
	}

	if ( isset( $settings['alumnionline_forum_page'] ) && '' !== $settings['alumnionline_forum_page'] && is_page( $settings['alumnionline_forum_page'] ) ) {

		$content .= alumnionline_reunion_website_forum_index();

	}
		return $content;
}
add_filter( 'the_content', 'alumnionline_reunions_plugin_content' );

/**
 * Display classmates search page content
 **/
function alumnionline_reunion_website_classmates_search_page( $content ) {

	if ( ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$settings = alumnionline_reunion_website_get_settings();
	if ( ! alumnionline_reunion_website_secure_by_role() ) {
		return $content;
	}

	if ( isset( $settings['alumnionline_classmate_search_page'] ) && '' !== $settings['alumnionline_classmate_search_page'] && is_page( $settings['alumnionline_classmate_search_page'] ) ) {

		// add pro plugin filter.
		if ( function_exists( 'alumnionline_reunion_website_pro_classmates_search_page_content' ) ) {
			$content = apply_filters( 'alumnionline_reunion_website_classmates_search_page_callback', '' );
		} else {
			$content = alumnionline_reunion_website_classmate_search();
		}
	}

		return $content;
}
add_filter( 'the_content', 'alumnionline_reunion_website_classmates_search_page' );

/**
 * Display classmate search
 */
function alumnionline_reunion_website_classmate_search() {

	$content = '';

	$content .= '<div class="alumnionline-reunion-website-classmate-search">';

	$count      = 0;
	$user_array = array();

	$roles = array( 'alumnionline_reunion_website_user' );

		$users = new WP_User_Query(
			array(
				'role__in'       => $roles,
				'author__not_in' => array( 1 ),
				'meta_key'       => 'alumnionline_school_id',
				'meta_value'     => alumnionline_reunion_website_get_user_meta_school_id(),
				'orderby'        => 'last_name',
				'order'          => 'ASC',
			)
		);

		$users_found = $users->get_results();

	foreach ( $users_found as $user ) {
		$user_array[ $count ]['ID']           = $user->ID;
		$roles                                = (array) $user->roles;
		$user_array[ $count ]['firstname']    = $user->first_name;
		$user_array[ $count ]['fullname']    = '';
		$user_array[ $count ]['lastname']     = $user->last_name;
		$user_array[ $count ]['display_name'] = $user->display_name;
		$user_array[ $count ]['user_email']   = $user->user_email;
		++$count;
	}

	foreach ( $user_array as $user ) {
		$avatarurl = 'https://secure.gravatar.com/avatar/?s=96&d=mm&r=g';

		$content .= '<div class="alumnionline-reunion-website-classmate-details">';
		if ( isset( $user['ID'] ) && '' != $user['ID'] ) {
			$content  .= '<a href="' . esc_url( get_author_posts_url( $user['ID'] ) ) . '">';
			$avatarurl = get_avatar_url( $user['ID'] );
		}

		$content .= '<img src="' . esc_url( $avatarurl ) . '">';

		$content .= '<p>';
		if ( '' != $user['firstname'] && '' != $user['lastname'] ) {
			$content .= esc_attr( $user['firstname'] ) . ' ' . esc_attr( $user['lastname'] );
		} else {
			$content .= esc_attr( $user['display_name'] );
		}
		$content .= '</p>';

		$content .= '</a>';

		$customfields = get_user_meta( $user['ID'] );
		$content     .= '<div class="alumnionline-reunion-website-otherinfo">';
		foreach ( $customfields as $key => $value ) {
			if ( strstr( $key, 'alumnionline_' ) && ! strstr( $key, '_alumnionline_' ) && ! strstr( $key, 'alumnionline_school_id' ) ) {
				$content .= '<span class="alumnionline-reunion-website-label">';
				$content .= esc_attr( strtoupper( str_replace( array( 'alumnionline_reunion_website', 'alumnionline_', '_' ), array( '', '', ' ' ), $key ) ) );
				$content .= ': ';
				$content .= '</span>';
				if ( is_array( $value ) ) {
					$content .= esc_attr( $value[0] );
				} else {
					$content .= esc_attr( $value );
				}
				$content .= '<br>';
			}
		}
		$content .= '</div>';

		$content .= '</div>';
	}

	return $content;
}

/**
 * Add author template
 */
function alumnionline_reunion_website_author_template( $original_template ) {

	if ( ! function_exists( 'alumnionline_reunion_website_pro_author_template' ) ) {
		$author_template = ALUMNIONLINE_REUNION_WEBSITE_DIR . 'templates/author.php';

		if ( file_exists( $author_template ) ) {
			return $author_template;
		}
	}

	return $original_template;
}
add_action( 'author_template', 'alumnionline_reunion_website_author_template' );

/**
 * Add message template
 */
function alumnionline_reunion_website_message_post_template( $original_template ) {

	if ( ! function_exists( 'alumnionline_reunion_website_pro_message_post_template' ) ) {
		$message_template = ALUMNIONLINE_REUNION_WEBSITE_DIR . 'templates/single-message-post.php';

		if ( 'message-post' === get_post_type( get_the_ID() ) ) {
			if ( file_exists( $message_template ) ) {
				return $message_template;
			}
		}
	}

	return $original_template;
}
add_action( 'single_template', 'alumnionline_reunion_website_message_post_template' );

/**
 * Secure posts by school id
 */
function alumnionline_reunion_website_secure_posts( $postid ) {
	$school_id = alumnionline_reunion_website_get_user_meta_school_id();

	if ( alumnionline_reunion_website_get_meta_record( $postid, 'alumnionline_school_id' ) != $school_id ) {
		die();
	}
}
/**
 * Secure content by role
 */
function alumnionline_reunion_website_secure_by_role() {
	$settings = alumnionline_reunion_website_get_settings();
	if ( current_user_can( 'use_alumnionline_reunion_website_features' ) || 'true' == $settings['alumnionline_public_content'] ) {
		return 1;
	}
	return 0;
}

/**
 * Get School ID for public content
 */
function alumnionline_reunion_website_get_user_meta_school_id() {

	$school_id = get_user_meta( get_current_user_id(), 'alumnionline_school_id', true );

	$settings = alumnionline_reunion_website_get_settings();

	if ( '' == $school_id && 'true' == $settings['alumnionline_public_content'] ) {
		$school_id = 0;
	}
	return $school_id;
}

/*****************************************
 * Display custom profile fields
 ****************************************/
function alumnionline_reunion_website_display_custom_user_profile_fields( $user ) {
	if ( ! function_exists( 'alumnionline_reunion_website_pro_display_custom_user_profile_fields' ) ) {
		wp_nonce_field( '-1', '_alumnionline_reunion_website_nonce' );
		?>
<h2><?php esc_html_e( 'School Information', 'alumnionline-reunion-website' ); ?>
</h2>
<table class="form-table">
	<tr class="alumnionlineorg-admin-only">
		<th><label
				for="alumnionline_school_id"><?php esc_html_e( 'SCHOOL ID', 'alumnionline-reunion-website' ); ?></label>
		</th>
		<td>
			<input type="text" name="alumnionline_school_id" id="alumnionline_school_id"
				value="<?php echo esc_attr( get_the_author_meta( 'alumnionline_school_id', $user->ID ) ); ?>"
				class="regular-text" readonly /><br />
		</td>
	</tr>
	<tr>
		<th><label
				for="alumnionline_class"><?php esc_html_e( 'CLASS', 'alumnionline-reunion-website' ); ?></label>
		</th>
		<td>
			<input type="text" name="alumnionline_class" id="alumnionline_class"
				value="<?php echo esc_attr( get_the_author_meta( 'alumnionline_class', $user->ID ) ); ?>"
				class="regular-text" />
		</td>
	</tr>

</table>
		<?php
	}
}
	add_action( 'show_user_profile', 'alumnionline_reunion_website_display_custom_user_profile_fields' );
	add_action( 'edit_user_profile', 'alumnionline_reunion_website_display_custom_user_profile_fields' );
	add_action( 'user_new_form', 'alumnionline_reunion_website_display_custom_user_profile_fields' );

/**
 * Save user profile
 */
function alumnionline_reunion_website_save_custom_user_profile_fields( $user_id ) {

	if ( ! function_exists( 'alumnionline_reunion_website_pro_save_custom_user_profile_fields' ) ) {
		if ( ! isset( $_REQUEST['_alumnionline_reunion_website_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_alumnionline_reunion_website_nonce'] ) ) ) {
			die();
		}

		if ( isset( $_POST['alumnionline_school_id'] ) && '' != $_POST['alumnionline_school_id'] ) {
			$school_id = sanitize_text_field( wp_unslash( $_POST['alumnionline_school_id'] ) );
			update_user_meta( $user_id, 'alumnionline_school_id', $school_id );
		} else {
			update_user_meta( $user_id, 'alumnionline_school_id', 0 );
		}

		if ( isset( $_POST['alumnionline_class'] ) && '' != $_POST['alumnionline_class'] ) {
			$alumnionline_class = sanitize_text_field( wp_unslash( $_POST['alumnionline_class'] ) );
			update_user_meta( $user_id, 'alumnionline_class', $alumnionline_class );
		}
	}
}
add_action( 'personal_options_update', 'alumnionline_reunion_website_save_custom_user_profile_fields' );
add_action( 'edit_user_profile_update', 'alumnionline_reunion_website_save_custom_user_profile_fields' );

/**
 * Set default school id
 */
function alumnionline_reunion_website_set_default_school_id( $user_id ) {
	if ( '' == get_user_meta( $user_id, 'alumnionline_school_id', true ) ) {
		update_user_meta( $user_id, 'alumnionline_school_id', 0 );
	}
}
add_action( 'user_register', 'alumnionline_reunion_website_set_default_school_id' );

/**
 * Get meta value from ACF or WordPress custom field
 */
function alumnionline_reunion_website_get_meta_record( $postid, $value ) {

	if ( class_exists( 'ACF' ) && function_exists( 'get_field' ) ) {
		return get_field( $value, $postid, true );
	} else {
		return get_post_meta( $postid, $value, true );
	}
}

/**
 * Add custom field group for ACF to message board
 */
function alumnionline_reunion_website_acf_add_local_field_groups() {
	$fieldarray = array();

	$fieldarray[] = array(
		'key'   => 'alumnionline_school_id',
		'label' => __( 'SCHOOL ID', 'alumnionline-reunion-website' ),
		'name'  => 'alumnionline_school_id',
		'type'  => 'text',
	);

	acf_add_local_field_group(
		array(
			'key'      => 'alumnionline_basic_group_1',
			'title'    => __( 'School Information', 'alumnionline-reunion-website' ),

			'fields'   => $fieldarray,
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'message-post',
					),
				),
			),
		)
	);
}
add_action( 'acf/init', 'alumnionline_reunion_website_acf_add_local_field_groups' );

/**
 * Set default school information values
 */
function alumnionline_reunion_website_acf_load_field_values( $field ) {

	$current_user = wp_get_current_user();

	if ( ! ( $current_user instanceof WP_User ) ) {
		return;
	}

	if ( is_array( $field ) && array_key_exists( 'key', $field ) && 'alumnionline_school_id' == $field['key'] ) {
		$school_id = get_user_meta( get_current_user_id(), 'alumnionline_school_id', true );
		if ( '' == $school_id ) {
			$school_id = 0;
		}
		$field['default_value'] = $school_id;
		$field['readonly']      = 1;
	}

	return $field;
}
add_filter( 'acf/load_field', 'alumnionline_reunion_website_acf_load_field_values' );


/**
 * Get trusted tags for sanitation
 */
function alumnionline_reunion_website_get_trusted_tags_array() {

	$trustedtags = array(
		'button'   => array(
			'style'       => array(),
			'class'       => array(),
			'id'          => array(),
			'data-id'     => array(),
			'title'       => array(),
			'data-userid' => array(),
		),
		'p'        => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'a'        => array(
			'style'       => array(),
			'class'       => array(),
			'id'          => array(),
			'data-status' => array(),
			'role'        => array(),
			'data-offset' => array(),
			'href'        => array(),
			'target'        => array(),
		),
		'img'      => array(
			'src'    => array(),
			'alt'    => array(),
			'width'  => array(),
			'height' => array(),
			'style'  => array(),
			'class'  => array(),
			'id'     => array(),
			'usemap' => array(),
		),
		'h1'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h2'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h3'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h4'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h5'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'h6'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'input'    => array(
			'style'  => array(),
			'class'  => array(),
			'id'     => array(),
			'type'   => array(),
			'name'   => array(),
			'value'  => array(),
			'src'    => array(),
			'border' => array(),
			'title'  => array(),
		),
		'pre'      => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'textarea' => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
			'cols'  => array(),
			'rows'  => array(),
			'name'  => array(),
		),
		'label'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
			'for'   => array(),
		),
		'select'   => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'span'     => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'i'        => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'fieldset' => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'caption'  => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'form'     => array(
			'style'  => array(),
			'class'  => array(),
			'id'     => array(),
			'action' => array(),
			'method' => array(),
			'target' => array(),
		),
		'legend'   => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'br'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'div'      => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
			'aria-live' => array(),
			'aria-label' => array(),
			'role' => array(),
		),
		'table'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'th'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'td'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'tr'       => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'tbody'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'thead'    => array(
			'style' => array(),
			'class' => array(),
			'id'    => array(),
		),
	);

	return $trustedtags;
}

?>
