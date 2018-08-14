<?php
/**
 * Name text field.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Field_Name extends WPForms_Field {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Define field type information
		$this->name  = __( 'Name', 'wpforms' );
		$this->type  = 'name';
		$this->icon  = 'fa-user';
		$this->order = 15;

		// Set field to default to required
		add_filter( 'wpforms_field_new_required', array( $this, 'default_required' ), 10, 2 );
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
		
		$this->field_option( 'basic-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'label',         $field );

		// Format option
		$format  = !empty( $field['format'] ) ? esc_attr( $field['format'] ) : 'first-last';
		$tooltip = __( 'Select format to use for the name form field', 'wpforms' );
		$options = array(
			'simple'            => __( 'Simple', 'wpforms' ),
			'first-last'        => __( 'First Last', 'wpforms' ),
			'first-middle-last' => __( 'First Middle Last', 'wpforms' ),
		);
		$output  = $this->field_element( 'label',  $field, array( 'slug' => 'format', 'value' => __( 'Format', 'wpforms' ), 'tooltip' => $tooltip ), false );
		$output .= $this->field_element( 'select', $field, array( 'slug' => 'format', 'value' => $format, 'options' => $options ), false );
		$this->field_element( 'row',    $field, array( 'slug' => 'format', 'content' => $output ) );

		$this->field_option( 'description',   $field );
		$this->field_option( 'required',      $field );
		$this->field_option( 'basic-options', $field, array( 'markup' => 'close' ) );
		
		//--------------------------------------------------------------------//
		// Advanced field options
		//--------------------------------------------------------------------//
	
		$this->field_option( 'advanced-options', $field, array( 'markup' => 'open' ) );
		$this->field_option( 'size',             $field );
		
		echo '<div class="format-selected-' . $format . ' format-selected">';

			// Simple
			$simple_placeholder = !empty( $field['simple_placeholder'] ) ? esc_attr( $field['simple_placeholder'] ) : '';
			$simple_default     = !empty( $field['simple_default'] ) ? esc_attr( $field['simple_default'] ) : '';
			printf( '<div class="wpforms-clear wpforms-field-option-row wpforms-field-option-row-simple" id="wpforms-field-option-row-%d-simple" data-subfield="simple" data-field-id="%d">', $field['id'], $field['id'] );
				$this->field_element( 'label', $field, array( 'slug' => 'simple_placeholder', 'value' => __( 'Name', 'wpforms' ), 'tooltip' => __( 'Name field advanced options.', 'wpforms' ) ) );
				echo '<div class="placeholder">';
					printf( '<input type="text" class="placeholder" id="wpforms-field-option-%d-simple_placeholder" name="fields[%d][simple_placeholder]" value="%s">', $field['id'], $field['id'], $simple_placeholder );
					printf( '<label for="wpforms-field-option-%d-simple_placeholder" class="sub-label">%s</label>', $field['id'], __( 'Placeholder', 'wpforms' ) );
				echo '</div>';
				echo '<div class="default">';
					printf( '<input type="text" class="default" id="wpforms-field-option-%d-simple_default" name="fields[%d][simple_default]" value="%s">', $field['id'], $field['id'], $simple_default );
					printf( '<label for="wpforms-field-option-%d-simple_default" class="sub-label">%s</label>', $field['id'], __( 'Default Value', 'wpforms' ) );
				echo '</div>';
			echo '</div>';

			// First
			$first_placeholder = !empty( $field['first_placeholder'] ) ? esc_attr( $field['first_placeholder'] ) : '';
			$first_default     = !empty( $field['first_default'] ) ? esc_attr( $field['first_default'] ) : '';
			printf( '<div class="wpforms-clear wpforms-field-option-row wpforms-field-option-row-first" id="wpforms-field-option-row-%d-first" data-subfield="first-name" data-field-id="%d">', $field['id'], $field['id'] );
				$this->field_element( 'label', $field, array( 'slug' => 'first_placeholder', 'value' => __( 'First Name', 'wpforms' ), 'tooltip' => __( 'First name field advanced options.', 'wpforms' ) ) );
				echo '<div class="placeholder">';
					printf( '<input type="text" class="placeholder" id="wpforms-field-option-%d-first_placeholder" name="fields[%d][first_placeholder]" value="%s">', $field['id'], $field['id'], $first_placeholder );
					printf( '<label for="wpforms-field-option-%d-first_placeholder" class="sub-label">%s</label>', $field['id'], __( 'Placeholder', 'wpforms' ) );
				echo '</div>';
				echo '<div class="default">';
					printf( '<input type="text" class="default" id="wpforms-field-option-%d-first_default" name="fields[%d][first_default]" value="%s">', $field['id'], $field['id'], $first_default );
					printf( '<label for="wpforms-field-option-%d-first_default" class="sub-label">%s</label>', $field['id'], __( 'Default Value', 'wpforms' ) );
				echo '</div>';
			echo '</div>';

			// Middle
			$middle_placeholder = !empty( $field['middle_placeholder'] ) ? esc_attr( $field['middle_placeholder'] ) : '';
			$middle_default     = !empty( $field['middle_default'] ) ? esc_attr( $field['middle_default'] ) : '';
			printf( '<div class="wpforms-clear wpforms-field-option-row wpforms-field-option-row-middle" id="wpforms-field-option-row-%d-middle" data-subfield="middle-name" data-field-id="%d">', $field['id'], $field['id'] );
				$this->field_element( 'label', $field, array( 'slug' => 'middle_placeholder', 'value' => __( 'Middle Name', 'wpforms' ), 'tooltip' => __( 'Middle name field advanced options.', 'wpforms' ) ) );
				echo '<div class="placeholder">';
					printf( '<input type="text" class="placeholder" id="wpforms-field-option-%d-middle_placeholder" name="fields[%d][middle_placeholder]" value="%s">', $field['id'], $field['id'], $middle_placeholder );
					printf( '<label for="wpforms-field-option-%d-middle_placeholder" class="sub-label">%s</label>', $field['id'], __( 'Placeholder', 'wpforms' ) );
				echo '</div>';
				echo '<div class="default">';
					printf( '<input type="text" class="default" id="wpforms-field-option-%d-middle_default" name="fields[%d][middle_default]" value="%s">', $field['id'], $field['id'], $middle_default );
					printf( '<label for="wpforms-field-option-%d-middle_default" class="sub-label">%s</label>', $field['id'], __( 'Default Value', 'wpforms' ) );
				echo '</div>';
			echo '</div>';

			// Last
			$last_placeholder = !empty( $field['last_placeholder'] ) ? esc_attr( $field['last_placeholder'] ) : '';
			$last_default     = !empty( $field['last_default'] ) ? esc_attr( $field['last_default'] ) : '';
			printf( '<div class="wpforms-clear wpforms-field-option-row wpforms-field-option-row-last" id="wpforms-field-option-row-%d-last" data-subfield="last-name" data-field-id="%d">', $field['id'], $field['id'] );
				$this->field_element( 'label', $field, array( 'slug' => 'last_placeholder', 'value' => __( 'Last Name', 'wpforms' ), 'tooltip' => __( 'Last name field advanced options.', 'wpforms' ) ) );
				echo '<div class="placeholder">';
					printf( '<input type="text" class="placeholder" id="wpforms-field-option-%d-last_placeholder" name="fields[%d][last_placeholder]" value="%s">', $field['id'], $field['id'], $last_placeholder );
					printf( '<label for="wpforms-field-option-%d-last_placeholder" class="sub-label">%s</label>', $field['id'], __( 'Placeholder', 'wpforms' ) );
				echo '</div>';
				echo '<div class="default">';
					printf( '<input type="text" class="default" id="wpforms-field-option-%d-last_default" name="fields[%d][last_default]" value="%s">', $field['id'], $field['id'], $last_default );
					printf( '<label for="wpforms-field-option-%d-last_default" class="sub-label">%s</label>', $field['id'], __( 'Default Value', 'wpforms' ) );
				echo '</div>';
			echo '</div>';

		echo '</div>';

		$this->field_option( 'label_hide',       $field );
		$this->field_option( 'sublabel_hide',    $field );
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

		$simple_placeholder = !empty( $field['simple_placeholder'] ) ? esc_attr( $field['simple_placeholder'] ) : '';
		$first_placeholder  = !empty( $field['first_placeholder'] ) ? esc_attr( $field['first_placeholder'] ) : '';
		$middle_placeholder = !empty( $field['middle_placeholder'] ) ? esc_attr( $field['middle_placeholder'] ) : '';
		$last_placeholder   = !empty( $field['last_placeholder'] ) ? esc_attr( $field['last_placeholder'] ) : '';
		$format             = !empty( $field['format'] ) ? $field['format'] : 'first-last';

		$this->field_preview_option( 'label', $field );

		echo '<div class="format-selected-' . $format . ' format-selected">';
			echo '<div class="wpforms-simple">';
				printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $simple_placeholder );
			echo '</div>';
			echo '<div class="wpforms-first-name">';
				printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $first_placeholder );
				printf( '<label class="wpforms-sub-label">%s</label>', __( 'First' , 'wpforms') );
			echo '</div>';
			echo '<div class="wpforms-middle-name">';
				printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $middle_placeholder );
				printf( '<label class="wpforms-sub-label">%s</label>', __( 'Middle', 'wpforms' ) );
			echo '</div>';
			echo '<div class="wpforms-last-name">';
				printf( '<input type="text" placeholder="%s" class="primary-input" disabled>', $last_placeholder );
				printf( '<label class="wpforms-sub-label">%s</label>', __( 'Last', 'wpforms' ) );				
			echo '</div>';
		echo '</div>';

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
		$field                = apply_filters( 'wpforms_address_field_display', $field, $field_atts, $form_data );
		$field_required       = !empty( $field['required'] ) ? ' required' : '';
		$field_format         = !empty( $field['format'] ) ? $field['format'] : 'us';
		$field_class          = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_class'] ) );
		$field_id             = implode( ' ', array_map( 'sanitize_html_class', $field_atts['input_id'] ) );
		$field_sublabel       = !empty( $field['sublabel_hide'] ) ? 'wpforms-sublabel-hide' : '';
		$simple_placeholder   = !empty( $field['simple_placeholder'] ) ? esc_attr( $field['simple_placeholder'] ) : '';
		$simple_default       = !empty( $field['simple_default'] ) ? esc_attr( $field['simple_default'] ) : '';
		$first_placeholder    = !empty( $field['first_placeholder'] ) ? esc_attr( $field['first_placeholder'] ) : '';
		$first_default        = !empty( $field['first_default'] ) ? esc_attr( $field['first_default'] ) : '';
		$middle_placeholder   = !empty( $field['middle_placeholder'] ) ? esc_attr( $field['middle_placeholder'] ) : '';
		$middle_default       = !empty( $field['middle_default'] ) ? esc_attr( $field['middle_default'] ) : '';
		$last_placeholder     = !empty( $field['last_placeholder'] ) ? esc_attr( $field['last_placeholder'] ) : '';
		$last_default         = !empty( $field['last_default'] ) ? esc_attr( $field['last_default'] ) : '';
		$form_id              = $form_data['id'];

		// Simple name format
		if ( 'simple' == $field_format ) :

			printf( 
				'<input type="text" name="wpforms[fields][%d]" id="%s" class="%s" placeholder="%s" value="%s" %s>',
				$field['id'],
				$field_id,
				$field_class,
				$simple_placeholder,
				$simple_default,
				$field_required
			);

		// Expanded formats
		else:

			$columns = ( 'first-last' == $field_format ? 'one-half' : 'two-fifths' );

			printf( '<div class="wpforms-field-row %s">', $field_class );

				// First name
				printf( '<div class="wpforms-field-row-block wpforms-%s wpforms-first">', $columns );

					$first_class  = 'wpforms-field-name-first';
					$first_class .= !empty( $field_required ) ? ' wpforms-field-required' : '';
					$first_class .= !empty( wpforms()->process->errors[$form_id][$field['id']]['first'] ) ? ' wpforms-error' : '';

					printf( 
						'<input type="text" name="wpforms[fields][%d][first]" id="%s" class="%s" placeholder="%s" value="%s" %s>',
						$field['id'],
						"wpforms-{$form_id}-field_{$field['id']}",
						$first_class,
						$first_placeholder,
						$first_default,
						$field_required
					);

					if ( !empty( wpforms()->process->errors[$form_id][$field['id']]['first'] ) ) {
						printf( '<label id="wpforms-%d-field_%d-error" class="wpforms-error" for="wpforms-field_%d">%s</label>', $form_id, $field['id'], $field['id'], esc_html( wpforms()->process->errors[$form_id][$field['id']]['first'] ) );
					}
			
					printf( '<label for="wpforms-%d-field_%d" class="wpforms-field-sublabel %s">%s</label>', $form_id, $field['id'], $field_sublabel, __( 'First', 'wpforms' ) );

				echo '</div>';

				// Middle name
				if ( 'first-middle-last' == $field_format ) :

					echo '<div class="wpforms-field-row-block wpforms-one-fifth">';

						printf( 
							'<input type="text" name="wpforms[fields][%d][middle]" id="%s" class="%s" placeholder="%s" value="%s">',
							$field['id'],
							"wpforms-{$form_id}-field_{$field['id']}-middle",
							'wpforms-field-name-middle',
							$middle_placeholder,
							$middle_default
						);
						
						printf( '<label for="wpforms-%d-field_%d-middle" class="wpforms-field-sublabel %s">%s</label>', $form_id, $field['id'], $field_sublabel, __( 'Middle', 'wpforms' ) );

					echo '</div>';

				endif;

				// Last name
				printf( '<div class="wpforms-field-row-block wpforms-%s">', $columns );

					$last_class  = 'wpforms-field-name-last';
					$last_class .= !empty( $field_required ) ? ' wpforms-field-required' : '';
					$last_class .= !empty( wpforms()->process->errors[$form_id][$field['id']]['last'] ) ? ' wpforms-error' : '';

					printf( 
						'<input type="text" name="wpforms[fields][%d][last]" id="%s" class="%s" placeholder="%s" value="%s" %s>',
						$field['id'],
						"wpforms-{$form_id}-field_{$field['id']}-last",
						$last_class,
						$last_placeholder,
						$last_default,
						$field_required
					);

					if ( !empty( wpforms()->process->errors[$form_id][$field['id']]['last'] ) ) {
						printf( '<label id="wpforms-%d-field_%d-error" class="wpforms-error" for="wpforms-field_%d">%s</label>', $form_id, $field['id'], $field['id'], esc_html( wpforms()->process->errors[$form_id][$field['id']]['last'] ) );
					}
					
					printf( '<label for="wpforms-%d-field_%d-last" class="wpforms-field-sublabel %s">%s</label>', $form_id, $field['id'], $field_sublabel, __( 'Last', 'wpforms' ) );

				echo '</div>';

			echo '</div>';
		
		endif;
	}

	/**
	 * Validates field on form submit.
	 *
	 * @since 1.0.0
	 * @param int $field_id
	 * @param array $field_submit
	 * @param array $form_data
	 */
	public function validate( $field_id, $field_submit, $form_data ) {

		// Extended validation needed for the different name fields
		if ( !empty( $form_data['fields'][$field_id]['required'] ) ) {

			$form_id  = $form_data['id'];
			$format   = $form_data['fields'][$field_id]['format'];
			$required = apply_filters( 'wpforms_required_label', __( 'This field is required', 'wpforms' ) );
			
			if ( 'simple' == $format && empty( $field_submit ) ) {
				wpforms()->process->errors[$form_id][$field_id] = $required;
			}

			if ( ( 'first-last' == $format || 'first-middle-last' == $format ) && empty( $field_submit['first'] ) ) {
				wpforms()->process->errors[$form_id][$field_id]['first'] = $required;
			}

			if ( ( 'first-last' == $format || 'first-middle-last' == $format ) && empty( $field_submit['last'] ) ) {
				wpforms()->process->errors[$form_id][$field_id]['last'] = $required;
			}
		}
	}

	/**
	 * Formats field.
	 *
	 * @since 1.0.0
	 * @param int $field_id
	 * @param array $field_submit
	 * @param array $form_data
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		$name   = !empty( $form_data['fields'][$field_id]['label'] ) ? $form_data['fields'][$field_id]['label'] : '';
		$first  = !empty( $field_submit['first'] ) ? $field_submit['first'] : '';
		$middle = !empty( $field_submit['middle'] ) ? $field_submit['middle'] : '';
		$last   = !empty( $field_submit['last'] ) ? $field_submit['last'] : '';
	
		if ( is_array( $field_submit ) ) {
			$value = array( $first, $middle, $last );
			$value = array_filter( $value );
			$value = implode( ' ', $value );
		} else {
			$value = $field_submit;
		}

		wpforms()->process->fields[$field_id] = array(
			'name'     => sanitize_text_field( $name ),
			'value'    => sanitize_text_field( $value ),
			'id'       => absint( $field_id ),
			'type'     => $this->type,
			'first'    => sanitize_text_field( $first ),
			'middle'   => sanitize_text_field( $middle ),
			'last'     => sanitize_text_field( $last ),
		);
	}

	/**
	 * Name fields should default to being required.
	 *
	 * @since 1.0.8
	 * @param bool $required
	 * @param array $field
	 * @return bool
	 */
	public function default_required( $required, $field ) {

		if ( $field['type'] == 'name' ) {
			return true;
		}
		return $required;
	}
}
new WPForms_Field_Name;