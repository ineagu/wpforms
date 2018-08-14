<?php
/**
 * Single line text field.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Field_Text extends WPForms_Field {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Define field type information
		$this->name  = __( 'Single Line Text', 'wpforms' );
		$this->type  = 'text';
		$this->icon  = 'fa-text-width';
		$this->order = 3;
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
		
		//$this->field_option( 'meta',        $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'label',         $field );
		$this->field_option( 'description',   $field );
		$this->field_option( 'required',      $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'close' ) );
	
		//--------------------------------------------------------------------//
		// Advanced field options
		//--------------------------------------------------------------------//
	
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'size',             $field );
		$this->field_option( 'placeholder',      $field );
		$this->field_option( 'label_hide',       $field );
		$this->field_option( 'default_value',    $field );
		$this->field_option( 'css',              $field );
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'close' ) );
	}

	/**
	 * Field preview inside the builder.
	 *
	 * @since 1.0.0
	 * @param array $field
	 */
	public function field_preview( $field ) {

		$placeholder = !empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		$this->field_preview_option( 'label', $field );

		printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $placeholder );

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
		$field             = apply_filters( 'wpforms_text_field_display', $field, $field_atts, $form_data );
		$field_placeholder = !empty( $field['placeholder']) ? esc_attr( $field['placeholder'] ) : '';
		$field_required    = !empty( $field['required'] ) ? ' required' : '';
		$field_class       = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_class'] ) );
		$field_id          = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_id'] ) );
		$field_value       = !empty( $field['default_value'] ) ? esc_attr( apply_filters( 'wpforms_process_smart_tags', $field['default_value'], $form_data ) ) : '';
		$field_data        = '';

		if ( !empty( $field_atts['input_data'] ) ) {
			foreach ( $field_atts['input_data'] as $key => $val ) {
			  $field_data .= ' data-' . $key . '="' . $val . '"';
			}
		}

		// Primary text field
		printf( 
			'<input type="text" name="wpforms[fields][%d]" id="%s" class="%s" value="%s" placeholder="%s" %s %s>',
			$field['id'],
			$field_id,
			$field_class,
			$field_value,
			$field_placeholder,
			$field_required,
			$field_data
		);
	}
}
new WPForms_Field_Text;