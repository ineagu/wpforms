<?php
/**
 * All the form goodness and basics.
 *
 * Contains a bunch of helper methods as well.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Form_Handler {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Register wpforms custom post type
		$this->register_cpt();

		// Add wpforms to new-content admin bar menu
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 99 );
	}

	/**
	 * Registers the custom post type to be used for forms.
	 *
	 * @since 1.0.0
	 */
	public function register_cpt() {

		// Custom post type arguments, which can be filtered if needed
		$args = apply_filters( 'wpforms_post_type_args',
			array(
				'labels'              => array(),
				'public'              => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_admin_bar'   => false,
				'rewrite'             => false,
				'query_var'           => false,
				'can_export'          => false,
				'supports'            => array( 'title' ),
			)
		);

		// Register the post type
		register_post_type( 'wpforms', $args );
	}

	/**
	 * Adds "WPForm" item to new-content admin bar menu item.
	 *
	 * @since 1.1.7.2
	 * @param object $wp_admin_bar
	 */
	public function admin_bar( $wp_admin_bar ) {

		if ( !is_admin_bar_showing() || !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) ) {
			return;
		}

		$args = array(
			'id'     => 'wpform',
			'title'  => 'WPForm',
			'href'   => admin_url( 'admin.php?page=wpforms-builder' ),
			'parent' => 'new-content',
		);
		$wp_admin_bar->add_node( $args );	
	}

	/**
	 * Fetches forms
	 *
	 * @since 1.0.0
	 * @param int $id
	 * @param array $args
	 */
	public function get( $id = '', $args = array() ) {

		$args = apply_filters( 'wpforms_get_form_args', $args );

		if ( false === $id ) {
			return false;
		}

		if ( !empty( $id ) ) {
			// @todo add $id array support
			// If ID is provided, we get a single form
			$forms = get_post( absint( $id ) );

			if ( !empty( $args['content_only'] ) && !empty( $forms ) ) {
	 			$forms = wpforms_decode( $forms->post_content );
			}

		} else {
			// No ID provided, get multiple forms
			$forms = get_posts(
				wp_parse_args(
				$args,
				array(
					'post_type'     => 'wpforms',
					'orderby'       => 'id',
					'order'         => 'ASC', // old->new
					'no_found_rows' => true,
					'nopaging'      => true,
					// 'cache_results' => false,
				))
			);
		}

		if ( empty( $forms ) ) {
			return false;
		}

		return $forms;
	}

	/**
	 * Deletes forms
	 *
	 * @since 1.0.0
	 * @param array $ids
	 * @return boolean
	 */
	public function delete( $ids = array() ) {

		// Check for permissions
		if ( !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) )
			return false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {

			$form = wp_delete_post( $id, true );

			if ( class_exists( 'WPForms_Entry_Handler' ) ) {
				$entries = wpforms()->entry->delete_by( 'form_id', $id );
			}

			if ( ! $form  ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Add new form.
	 *
	 * @since 1.0.0
	 * @param string $title
	 * @param array $args
	 * @return mixed
	 */
	public function add( $title = '', $args = array(), $data = array() ) {

		// Check for permissions
		if ( !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) )
			return false;

		// Must have a title
		if ( empty( $title ) )
			return false;

		$args = apply_filters( 'wpforms_create_form_args' , $args, $data );

		$form_content = array(
			'field_id' => '0',
			'settings' => array(
				'form_title' => sanitize_text_field( $title ),
				'form_desc' => '',
			),
		);

		// Merge args and create the form
		$form = wp_parse_args(
			$args,
			array(
				'post_title'  => esc_html( $title ),
				'post_status' => 'publish',
				'post_type'   => 'wpforms',
				'post_content' => wp_slash( json_encode( $form_content ) ),
			)
		);
		$form_id = wp_insert_post( $form );

		do_action( 'wpforms_create_form', $form_id, $form, $data );

		return $form_id;
	}

	/**
	 * Updates form
	 *
	 * @since 1.0.0
	 * @param string $title
	 * @param array $args
	 * @return mixed
	 */
	public function update( $form_id = '', $data = array(), $args = array() ) {

		// This filter breaks forms if they contain HTML
		remove_filter( 'content_save_pre', 'balanceTags', 50 );

		// Check for permissions
		if ( !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) )
			return false;

		if ( empty( $data ) )
			return false;

		if ( empty( $form_id ) ) {
			$form_id = $data['id'];
		}

		$data = wp_unslash( $data );

		if ( !empty( $data['settings']['form_title'] ) ) {
			$title = $data['settings']['form_title'];
		} else {
			$title = get_the_title( $form_id );
		}

		if ( !empty( $data['settings']['form_desc'] ) ) {
			$desc = $data['settings']['form_desc'];
		} else {
			$desc = '';
		}

		$data['field_id'] = !empty( $data['field_id'] ) ? absint( $data['field_id'] ) : '0';

		// Perserve form meta.
		$meta = $this->get_meta( $form_id );
		if ( $meta ) {
			$data['meta'] = $meta;
		}

		// Preserve field meta.
		if ( isset( $data['fields'] ) )	{
			foreach ( $data['fields'] as $i => $field_data ) {
				if ( isset( $field_data['id'] ) ) {
					$field_meta = $this->get_field_meta( $form_id, $field_data['id'] );
					if ( $field_meta ) {
						$data['fields'][ $i ]['meta'] = $field_meta;
					}
				}
			}
		}

		// Sanitize - don't allow tags for users who do not have appropriate cap
		if ( !current_user_can( 'unfiltered_html' ) ) {
			array_walk_recursive( $data, 'wp_strip_all_tags' );
		}

		$form = array(
			'ID'           => $form_id,
			'post_title'   => esc_html( $title ),
			'post_excerpt' => $desc,
			'post_content' => wp_slash( json_encode( $data ) ),
		);
		$form    = apply_filters( 'wpforms_save_form_args', $form, $data, $args );
		$form_id = wp_update_post( $form );

		do_action( 'wpforms_save_form', $form_id, $form );

		return $form_id;
	}

	/**
	 * Duplicate forms.
	 *
	 * @since 1.1.4
	 * @param array $ids
	 * @return boolean
	 */
	public function duplicate( $ids = array() ) {

		// Check for permissions
		if ( !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) )
			return false;

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {

			// Get original entry
			$form = get_post( $id );

			// Confirm form exists
			if ( ! $form || empty( $form ) ) {
				return false;
			}

			// Get the form data
			$new_form_data = wpforms_decode( $form->post_content );

			// Remove form ID from title if present
			$new_form_data['settings']['form_title'] = str_replace('(ID #' . absint( $id ) . ')', '', $new_form_data['settings']['form_title'] );

			// Create the duplicate form
			$new_form = array(
				'post_author'    => $form->post_author,
				'post_content'   => wp_slash( json_encode( $new_form_data ) ),
				'post_excerpt'   => $form->post_excerpt,
				'post_status'    => $form->post_status,
				'post_title'     => $new_form_data['settings']['form_title'],
				'post_type'      => $form->post_type,
			);
			$new_form_id = wp_insert_post( $new_form );

			if ( ! $new_form_id || is_wp_error( $new_form_id ) ) {
				return false;
			}

			// Set new form title
			$new_form_data['settings']['form_title'] .= ' (ID #' . absint( $new_form_id ) . ')';
			
			// Set new form ID
			$new_form_data['id'] = absint( $new_form_id );

			// Update new duplicate form
			$new_form_id = $this->update( $new_form_id, $new_form_data );

			if ( ! $new_form_id || is_wp_error( $new_form_id ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the next available field ID and increment by one.
	 *
	 * @since 1.0.0
	 * @param int $form_id
	 * @return mixed int or false
	 */
	public function next_field_id( $form_id ) {

		// Check for permissions
		if ( !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) )
			return false;

		if ( empty( $form_id ) )
			return false;

		$form = $this->get( $form_id, array( 'content_only' => true ) );

		if ( !empty( $form['field_id'] ) ) {
			$field_id = absint( $form['field_id'] );
			$form['field_id']++;
		} else {
			$field_id = '0';
			$form['field_id'] = '1';
		}

		$this->update( $form_id, $form );

		return $field_id;
	}

	/**
	 * Get private meta information for a form.
	 *
	 * @since 1.0.0
	 */
	public function get_meta( $form_id, $field = '' ) {

		if ( empty( $form_id ) )
			return false;

		$data = $this->get( $form_id, array( 'content_only' => true ) );

		if ( isset( $data['meta'] ) ) {
			if ( empty( $field ) ) {
				return $data['meta'];
			} elseif ( isset( $data['meta'][$field] ) ) {
				return $data['meta'][$field];
			}
		}
		return false;
	}

	/**
	 * Get private meta information for a form field.
	 *
	 * @since 1.0.0
	 */
	public function get_field( $form_id, $field_id = '' ) {

		if ( empty( $form_id ) ) {
			return false;
		}

		$data = $this->get( $form_id, array( 'content_only' => true ) );

		return isset( $data['fields'][ $field_id ] ) ? $data['fields'][ $field_id ] : false;
	}

	/**
	 * Get private meta information for a form field.
	 *
	 * @since 1.0.0
	 */
	public function get_field_meta( $form_id, $field = '' ) {

		$field = $this->get_field( $form_id, $field );
		if ( ! $field ) {
			return false;
		}

		return isset( $field['meta'] ) ? $field['meta'] : false;
	}
}