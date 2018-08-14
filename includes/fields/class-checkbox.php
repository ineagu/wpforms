<?php
/**
 * Checkbox field.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Field_Checkbox extends WPForms_Field {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Define field type information
		$this->name     = __( 'Checkboxes', 'wpforms' );
		$this->type     = 'checkbox';
		$this->icon     = 'fa-check-square-o';
		$this->order    = 11;
		$this->defaults = array(
			1 => array(
				'label'   => __( 'First Choice', 'wpforms' ),
				'value'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => __( 'Second Choice', 'wpforms' ),
				'value'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => __( 'Third Choice', 'wpforms' ),
				'value'   => '',
				'default' => '',
			),
		);
	}

	/**
	 * Field options panel inside the builder.
	 *
	 * @since 1.0.0
	 * @param array $field
	 */
	public function field_options( $field ) {

		//--------------------------------------------------------------------//
		// Basic field options
		//--------------------------------------------------------------------//
		
		// Options open markup
		$this->field_option( 'basic-options', $field, array( 'markup' => 'open' ) );
		
		// Label
		$this->field_option( 'label', $field );

		// Choices
		$this->field_option( 'choices', $field );

		// Description
		$this->field_option( 'description',   $field );

		// Required toggle
		$this->field_option( 'required', $field );

		// Options close markup
		$this->field_option( 'basic-options', $field, array( 'markup' => 'close' ) );
		
		//--------------------------------------------------------------------//
		// Advanced field options
		//--------------------------------------------------------------------//
	
		// Options open markup
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'open' ) );
		
		// Show Values toggle option
		$tooltip     = __( 'Check this to manual set form field values.', 'wpforms' );
		$show_values = isset( $field['show_values'] ) ? $field['show_values'] : '0';
		$show_values = $this->field_element( 'checkbox', $field, array( 'slug' => 'show_values', 'value' => $show_values, 'desc' => __( 'Show Values', 'wpforms' ), 'tooltip' => $tooltip ), false );
		$this->field_element( 'row', $field, array( 'slug' => 'show_values', 'content' => $show_values ) );

		// Input columns
		$this->field_option( 'input_columns', $field );

		// Hide label
		$this->field_option( 'label_hide', $field );

		// Custom CSS classes
		$this->field_option( 'css', $field );

		// Dynamic choice auto-populating toggle
		$this->field_option( 'dynamic_choices', $field );

		// Dynamic choice source
		$this->field_option( 'dynamic_choices_source', $field );

		// Options close markup
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'close' ) );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 * @param array $field
	 */
	public function field_preview( $field ) {

		$values  = !empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
		$dynamic = !empty( $field['dynamic_choices'] ) ? $field['dynamic_choices'] : false;

		// Label
		$this->field_preview_option( 'label', $field );

		// Field checkbox elements
		echo '<ul class="primary-input">';

			// Check to see if this field is configured for Dynamic Choices, 
			// either auto populating from a post type or a taxonomy.
			if ( 'post_type' == $dynamic && !empty( $field['dynamic_post_type'] ) ) {
			
				// Post type dynamic populating
				$source = $field['dynamic_post_type'];
				$total  = wp_count_posts( $source );
				$total  = $total->publish;
				$args   = array( 
					'post_type'      => $source, 
					'posts_per_page' => 20, 
					'orderby'        => 'title', 
					'order'          => 'ASC' ,
				);
				$posts  = get_posts( apply_filters( 'wpforms_dynamic_choice_post_type_args', $args, $field, $this->form_id ) );
				$values = array();

				foreach ( $posts as $post ) {
					$values[] = array( 'label' => $post->post_title );
				}

			} elseif ( 'taxonomy' == $dynamic && !empty( $field['dynamic_taxonomy'] ) ) {

				// Taxonomy dynamic populating
				$source = $field['dynamic_taxonomy'];
				$total  = wp_count_terms( $source );
				$args   = array( 
					'taxonomy'   => $source, 
					'hide_empty' => false, 
					'number'     => 20
				); 
				$terms 	= get_terms( apply_filters( 'wpforms_dynamic_choice_taxonomy_args', $args, $field, $this->form_id ) );
				$values = array();

				foreach ( $terms as $term ) {
					$values[] = array( 'label' => $term->name );
				}
			}		

			// Notify if currently empty
			if ( empty( $values ) ) {
				$values = array( 'label' => __( '(empty)', 'wpforms' ) );
			}
			
			// Individual checkbox options
			foreach ( $values as $key => $value ) {
			
				$default  = isset( $value['default'] ) ? $value['default'] : '';
				$selected = checked( '1', $default, false );
			
				printf( '<li><input type="checkbox" %s disabled>%s</li>', $selected, $value['label'] );
			}

		echo '</ul>';

		// Dynamic population is enabled and contains more than 20 items
		if ( isset( $total ) && $total > 20  ) {
			echo '<div class="wpforms-alert-dynamic wpforms-alert wpforms-alert-warning">';
				printf( __( 'Showing the first 20 choices.<br> All %d choices will be displayed when viewing the form.', 'wpforms' ), absint( $total ) );
			echo '</div>';
		}

		// Description
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 * @param array $field
	 * @param array $form_data
	 */
	public function field_display( $field, $field_atts, $form_data ) {
	
		// Setup and sanitize the necessary data
		$field             = apply_filters( 'wpforms_checkbox_field_display', $field, $field_atts, $form_data );
		$field_placeholder = !empty( $field['placeholder']) ? esc_attr( $field['placeholder'] ) : '';
		$field_required    = !empty( $field['required'] ) ? ' required' : '';
		$field_class       = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_class'] ) );
		$field_id          = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_id'] ) );
		$field_data        = '';	
		$form_id           = $form_data['id'];
		$dynamic           = !empty( $field['dynamic_choices'] ) ? $field['dynamic_choices'] : false;
		$choices           = $field['choices'];

		if ( !empty( $field_atts['input_data'] ) ) {
			foreach ( $field_atts['input_data'] as $key => $val ) {
			  $field_data .= ' data-' . $key . '="' . $val . '"';
			}
		}

		// Check to see if this field is configured for Dynamic Choices, 
		// either auto populating from a post type or a taxonomy.
		if ( 'post_type' == $dynamic && !empty( $field['dynamic_post_type'] ) ) {
		
			// Post type dynamic populating
			$source = $field['dynamic_post_type'];
			$args   = array( 
				'post_type'      => $source, 
				'posts_per_page' => -1, 
				'orderby'        => 'title', 
				'order'          => 'ASC' ,
			);
			$posts   = get_posts( apply_filters( 'wpforms_dynamic_choice_post_type_args', $args, $field, $form_data['id'] ) );
			$choices = array();

			foreach ( $posts as $post ) {
				$choices[] = array( 
					'value' => $post->ID,
					'label' => $post->post_title
				);
			}

			$field['show_values'] = true;

		} elseif ( 'taxonomy' == $dynamic && !empty( $field['dynamic_taxonomy'] ) ) {

			// Taxonomy dynamic populating
			$source = $field['dynamic_taxonomy'];
			$args   = array( 
				'taxonomy'   => $source, 
				'hide_empty' => false, 
			); 
			$terms 	 = get_terms( apply_filters( 'wpforms_dynamic_choice_taxonomy_args', $args, $field, $form_data['id'] ) );
			$choices = array();
			
			foreach ( $terms as $term ) {
				$choices[] = array( 
					'value' => $term->term_id,
					'label' => $term->name
				);
			}

			$field['show_values'] = true;
		}

		// List
		printf( '<ul id="%s" class="%s" %s>', $field_id, $field_class, $field_data );
			
			foreach( $choices as $key=> $choice ) {
			
				$selected = isset( $choice['default'] ) ? '1' : '0' ;
				$val      = isset( $field['show_values'] ) ?  esc_attr( $choice['value'] ) : esc_attr( $choice['label'] );
			
				printf( '<li class="choice-%d">', $key );
					
					// Checkbox elements
					printf( '<input type="checkbox" id="wpforms-%d-field_%d_%d" name="wpforms[fields][%d][]" value="%s" %s %s>',
						$form_id, 
						$field['id'],
						$key,
						$field['id'],
						$val,
						checked( '1', $selected, false ),
						$field_required
					);
					
					printf( '<label class="wpforms-field-label-inline" for="wpforms-%d-field_%d_%d">%s</label>', $form_id, $field['id'], $key, wp_kses_post( $choice['label'] ) );
				
				echo '</li>';
			}
		
		echo '</ul>';
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @since 1.0.2
	 * @param int $field_id
	 * @param array $field_submit
	 * @param array $form_data
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		$field_submit = (array) $field_submit;
		$field        = $form_data['fields'][$field_id];
		$dynamic      = !empty( $field['dynamic_choices'] ) ? $field['dynamic_choices'] : false;
		$name         = sanitize_text_field( $field['label'] );
		$value_raw    = implode( "\n", array_map( 'sanitize_text_field', $field_submit ) );
		$value        = '';

		$data = array(
			'name'      => $name,
			'value'     => '',
			'value_raw' => $value_raw,
			'id'        => absint( $field_id ),
			'type'      => $this->type,
		);

		if ( 'post_type' == $dynamic && !empty( $field['dynamic_post_type'] ) ) {

			// Dynamic population is enabled using post type
			$value_raw                 = implode( ',', array_map( 'absint', $field_submit ) );
			$data['value_raw']         = $value_raw;
			$data['dynamic']           = 'post_type';
			$data['dynamic_items']     = $value_raw;
			$data['dynamic_post_type'] = $field['dynamic_post_type'];
			$posts                     = array();

			foreach ( $field_submit as $id ) {
				$post = get_post( $id );

				if ( ! is_wp_error( $post ) && !empty( $post ) && $data['dynamic_post_type'] == $post->post_type ) {
					$posts[] = esc_html( $post->post_title );
				}
			}

			$data['value'] = !empty( $posts ) ? implode( "\n", array_map( 'sanitize_text_field', $posts ) ) : '';

		} elseif ( 'taxonomy' == $dynamic && !empty( $field['dynamic_taxonomy'] ) ) {

			// Dynamic population is enabled using taxonomy
			$value_raw                 = implode( ',', array_map( 'absint', $field_submit ) );
			$data['value_raw']         = $value_raw;
			$data['dynamic']           = 'taxonomy';
			$data['dynamic_items']     = $value_raw;
			$data['dynamic_taxonomy']  = $field['dynamic_taxonomy'];
			$terms                     = array();

			foreach ( $field_submit as $id ) {
				$term = get_term( $id, $field['dynamic_taxonomy'] );

				if ( ! is_wp_error( $term ) && !empty( $term ) ) {
					$terms[] = esc_html( $term->name );
				}
			}

			$data['value'] = !empty( $terms ) ? implode( "\n", array_map( 'sanitize_text_field', $terms ) ) : '';
			
		} else {

			// Normal processing, dynamic population is off

			// If show_values is true, that means values posted are the raw values
			// and not the labels. So we need to get the label values.
			if ( !empty( $field['show_values'] ) && '1' == $field['show_values'] ) {
				
				$value = array();

				foreach( $field_submit as $field_submit_single ) {
					foreach( $field['choices'] as $choice ) {
						if ( $choice['value'] == $field_submit_single ) {
							$value[] = $choice['label'];
							break;
						}
					}
				}
				
				$data['value'] = !empty( $value ) ? implode( "\n", array_map( 'sanitize_text_field', $value ) ) : '';

			} else {
				$data['value'] = $value_raw;
			}
		}

		// Push field details to be saved
		wpforms()->process->fields[$field_id] = $data;
	}
}
new WPForms_Field_Checkbox;