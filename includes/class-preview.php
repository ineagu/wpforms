<?php
/**
 * Preview class.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.1.5
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Preview {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.1.5
	 */
	public function __construct() {

		// Maybe load a preview page
		add_action( 'init', array( $this, 'init' ) );

		// Hide preview page from admin
		add_action( 'pre_get_posts', array( $this, 'form_preview_hide' ) );
	}

	/**
	 * Determing if the user should see a preview page, if so, party on.
	 *
	 * @since 1.1.5
	 */
	public function init() {

		// Check for preview param with allowed values
		if ( empty( $_GET['wpforms_preview'] ) || !in_array( $_GET['wpforms_preview'], array( 'print', 'form' ) ) ) {
			return;
		}

		// Check for authenticated user with correct capabilities
		if ( !is_user_logged_in() || !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) ) {
			return;
		}

		// Print preview
		if ( 'print' == $_GET['wpforms_preview'] && !empty( $_GET['entry_id'] ) ) {
			$this->print_preview();
		}

		// Form preview
		if ( 'form' == $_GET['wpforms_preview'] && !empty( $_GET['form_id'] ) ) {
			$this->form_preview();
		}
	}

	/**
	 * Print Preview.
	 *
	 * @since 1.1.5
	 */
	public function print_preview() {

		// Load entry details
		$entry = wpforms()->entry->get( absint( $_GET['entry_id'] ) );

		// Double check that we found a real entry
		if ( ! $entry || empty( $entry ) ) {
			return;
		}

		// Get form details
		$form_data = wpforms()->form->get( $entry->form_id, array( 'content_only' => true ) );
		
		// Double check that we found a valid entry
		if ( ! $form_data || empty( $form_data ) ) {
			return;
		}
		?>
		<!doctype html>
		<html>
		<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title>WPForms Print Preview - <?php echo ucfirst( sanitize_text_field( $form_data['settings']['form_title'] ) ); ?> </title>
		<meta name="description" content="">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex,nofollow,noarchive">
		<link rel="stylesheet" href="<?php echo includes_url('css/buttons.min.css'); ?>" type="text/css">
		<link rel="stylesheet" href="<?php echo WPFORMS_PLUGIN_URL; ?>assets/css/wpforms-preview.css" type="text/css">
		<script type="text/javascript" src="<?php echo includes_url('js/jquery/jquery.js'); ?>"></script>
		<script type="text/javascript" src="<?php echo WPFORMS_PLUGIN_URL; ?>assets/js/wpforms-preview.js"></script>
		</head>
		<body class="wp-core-ui">
			<div class="wpforms-preview" id="print">
				<h1>
					<?php echo sanitize_text_field( $form_data['settings']['form_title'] ); ?> <span> - <?php printf( __( 'Entry #%d', 'wpforms' ), absint( $entry->entry_id ) ); ?></span>
					<div class="buttons">
						<a href="" class="button button-secondary close-window">Close</a>
						<a href="" class="button button-primary print">Print</a>
					</div>
				</h1>
				<?php
				$fields = apply_filters( 'wpforms_entry_single_data', wpforms_decode( $entry->fields ), $entry, $form_data );

				if ( empty( $fields ) ) {

					// Whoops, no fields! This shouldn't happen under normal use cases.
					echo '<p class="no-fields">' . __( 'This entry does not have any fields', 'wpforms' ) . '</p>';

				} else {

					echo '<div class="fields">';

					// Display the fields and their values
					foreach ( $fields as $key => $field ) {

						$field_value  = apply_filters( 'wpforms_html_field_value', wp_strip_all_tags( $field['value'] ), $field, $form_data );
						$field_class  = sanitize_html_class( 'wpforms-field-' . $field['type'] );
						$field_class .= empty( $field_value ) ? ' empty' : '';  

						echo '<div class="wpforms-entry-field ' . $field_class . '">';

							// Field name
							echo '<p class="wpforms-entry-field-name">';
								echo !empty( $field['name'] ) ? wp_strip_all_tags( $field['name'] ) : sprintf( __( 'Field ID #%d', 'wpforms' ), absint( $field['id'] ) );
							echo '</p>';

							// Field value
							echo '<p class="wpforms-entry-field-value">';
								echo !empty( $field_value ) ? nl2br( make_clickable( $field_value ) ) : __( 'Empty', 'wpforms' );
							echo '</p>';

						echo '</div>';
					}

					echo '</div>';
				}
				?>
				</div><!-- .wrap -->
			<p class="site"><a href="<?php echo home_url(); ?>"><?php echo get_bloginfo( 'name'); ?></a></p>
		</body>
		<?php
		exit();
	}

	/**
	 * Check if preview page exists, if not create it.
	 *
	 * @since 1.1.9
	 */
	public function form_preview_check() {

		if ( !is_admin() )
			return;
	
		// Verify page exits
		$preview = get_option( 'wpforms_preview_page' );

		if ( $preview ) {

			$preview_page = get_post( $preview );

			// Check to see if the visibility has been changed, if so correct it
			if ( !empty( $preview_page ) && 'private' != $preview_page->post_status ) {
				$preview_page->post_status = 'private';
				wp_update_post( $preview_page );
				return;
			} elseif ( !empty( $preview_page ) ) {
				return;
			}
		}

		// Create the custom preview page
		$content  = '<p>' . __( 'This is the WPForms preview page. All your form previews will be handled on this page.', 'wpforms' ) . '</p>';
		$content .= '<p>' . __( 'The page is set to private, so it is not publically accessible. Please do not delete this page :) .', 'wpforms' ) . '</p>';
		$args = array(
			'post_type'		 => 'page',
			'post_name'		 => 'wpforms-preview',
			'post_author'	 => 1,
			'post_title'	 => __( 'WPForms Preview', 'wpforms' ),
			'post_status'	 => 'private',
			'post_content'	 => $content,
			'comment_status' => 'closed'
		);
		$id = wp_insert_post( $args );
		if ( $id ) {
			update_option( 'wpforms_preview_page', $id );
		}
	}

	/**
	 * Preview page URL.
	 *
	 * @since 1.1.9
	 * @param int $form_id
	 * @return string
	 */
	public function form_preview_url( $form_id ) {

		$id = get_option( 'wpforms_preview_page' );

		if ( ! $id ) {
			return home_url();
		}

		$url = get_permalink( $id );

		if ( ! $url ) {
			return home_url();
		}

		return add_query_arg( array( 'wpforms_preview' => 'form', 'form_id' => absint( $form_id ) ), $url );
	}

	/**
	 * Fires when form preview might be detected.
	 *
	 * @since 1.1.9
	 */
	public function form_preview() {

		add_filter( 'the_posts', array( $this, 'form_preview_query' ), 10, 2 );
	}

	/**
	 * Tweak the page content for form preview page requests.
	 *
	 * @since 1.1.9
	 * @param array $posts
	 * @param object $query
	 * @return array
	 */
	public function form_preview_query( $posts, $query ) {

		// One last cap check, just for fun.
		if ( !is_user_logged_in() || !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) ) {
			return $posts;
		}

		// Only target main query
		if ( ! $query->is_main_query() ) {
			return $posts;
		}

		// If our queried object ID does not match the preview page ID, return early.
		$preview_id = absint( get_option( 'wpforms_preview_page' ) );
		$queried    = $query->get_queried_object_id();
		if ( $queried && $queried != $preview_id && isset( $query->query_vars['page_id'] ) && $preview_id != $query->query_vars['page_id'] ) {
			return $posts;
		}

		// Get the form details
		$form = wpforms()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) );

		if ( ! $form || empty( $form ) ) {
			return $posts;
		}

		// Customize the page content
		$title     = sanitize_text_field( $form['settings']['form_title'] );
		$shortcode = '[wpforms id="' . absint( $form['id'] ) . '"]';
		$content   = __( 'This is a preview of your form. This page not publically accessible.', 'wpforms' );
		if ( !empty( $_GET['new_window'] ) ) {
			$content .= ' <a href="javascript:window.close();">' . __( 'Close this window', 'wpforms' ) . '.</a>';
		}
		$posts[0]->post_title   = $title . __( ' Preview', 'wpforms' );
		$posts[0]->post_content = $content . $shortcode;
		$posts[0]->post_status  = 'public';

		return $posts;
	}
	
	/**
	 * Hide the preview page from admin
	 *
	 * @since 1.2.3
	 * @param object $query
	 */
	function form_preview_hide( $query ) {
	
		if( $query->is_main_query() && is_admin() && isset( $query->query_vars['post_type'] ) && 'page' == $query->query_vars['post_type'] ) {
			 $wpforms_preview = intval( get_option( 'wpforms_preview_page' ) );
			 if( $wpforms_preview ) {
			 	$exclude = $query->query_vars['post__not_in'];
			 	$exclude[] = $wpforms_preview;
			 	$query->set( 'post__not_in', $exclude );
			 }
		}
	}	
}