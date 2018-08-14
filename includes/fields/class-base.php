<?php
/**
 * Base field template.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
abstract class WPForms_Field {

	/**
	 * Full name of the field type, eg "Paragraph Text".
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $name;

	/**
	 * Type of the field, eg "textarea".
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $type;

	/**
	 * Font Awesome Icon used for the editor button, eg "fa-list".
	 *
	 * @since 1.0.0
	 * @var mixed
	 */
	public $icon = false;

	/**
	 * Priority order the field button should show inside the "Add Fields" tab.
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	public $order = 20;

	/**
	 * Field group the field belongs to.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $group =  'standard';

	/**
	 * Placeholder to hold default value(s) for some field types.
	 *
	 * @since 1.0.0
	 * @var mixed
	 */
	public $defaults;

	/**
	 * Current form ID in the admin builder.
	 *
	 * @since 1.1.1
	 * @var mixed, int or false
	 */
	public $form_id;

	/**
	 * Current form data in admin builder.
	 *
	 * @since 1.1.1
	 * @var mixed, int or false
	 */
	public $form_data;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $init = true ) {

		if ( ! $init ) {
			return;
		}

		// The form ID is to be accessed in the builder
		$this->form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

		// Bootstrap
		$this->init();

		// Add fields tab
		add_filter( 'wpforms_builder_fields_buttons', array( $this, 'field_button' ), 15 );

		// Field options tab
		add_action( "wpforms_builder_fields_options_{$this->type}", array( $this, 'field_options' ), 10 );

		// Preview fields
		add_action( "wpforms_builder_fields_previews_{$this->type}", array( $this, 'field_preview' ), 10 );

		// AJAX Add new field
		add_action( "wp_ajax_wpforms_new_field_{$this->type}", array( $this,'field_new' ) );

		// Display fields on front-end
		add_action( "wpforms_display_field_{$this->type}", array( $this, 'field_display' ), 10, 3 );

		// Validation on submit
		add_action( "wpforms_process_validate_{$this->type}", array( $this, 'validate'), 10, 3 );

		// Format
		add_action( "wpforms_process_format_{$this->type}", array( $this, 'format'), 10, 3 );
	}

	/**
	 * All systems go. Used by subclasses.
	 *
	 * @since 1.0.0
	 */
	public function init() {
	}

	/**
	 * Create the button for the 'Add Fields' tab, inside the form editor.
	 *
	 * @since 1.0.0
	 * @param array $fields
	 * @return array
	 */
	public function field_button( $fields ) {

		// Add field information to fields array
		$fields[$this->group]['fields'][] = array(
			'order' => $this->order,
			'name'  => $this->name,
			'type'  => $this->type,
			'icon'  => $this->icon,
		);
		// Wipe hands clean
		return $fields;
	}

	/**
	 * Creates the field options panel. Used by subclasses.
	 *
	 * @since 1.0.0
	 */
	public function field_options( $field ) {
	}

	/**
	 * Creates the field preview. Used by subclasses.
	 *
	 * @since 1.0.0
	 */
	public function field_preview( $field ) {
	}

	/**
	 * Helper function to create field option elements.
	 *
	 * Field option elements are peices that help create a field option. They
	 * are used to quickly build field options.
	 *
	 * @since 1.0.0
	 * @param string $option
	 * @param array $field
	 * @param array $args
	 * @param boolean $echo
	 * @return mixed echo or return string
	 */
	public function field_element( $option, $field, $args = array(), $echo = true ) {

		$id     = (int) $field['id'];
		$class  = !empty( $args['class'] ) ? sanitize_html_class( $args['class'] ) : '';
		$slug   = !empty( $args['slug'] ) ? sanitize_title( $args['slug'] ) : '';
		$data   = '';

		if ( !empty( $args['data'] ) ) {
			foreach ( $args['data'] as $key => $val ) {
			  $data .= ' data-' . $key . '="' . $val . '"';
			}
		}

		switch ( $option ) {

			// Row
			case 'row':
				$output = sprintf( '<div class="wpforms-field-option-row wpforms-field-option-row-%s %s" id="wpforms-field-option-row-%d-%s" data-field-id="%d">%s</div>', $slug, $class, $id, $slug, $id, $args['content'] );
				break;

			// Label
			case 'label':
				$output = sprintf( '<label for="wpforms-field-option-%d-%s">%s', $id, $slug, esc_html( $args['value'] ) );
				if ( isset( $args['tooltip'] ) && !empty( $args['tooltip'] ) ) {
					$output .=  sprintf( ' <i class="fa fa-question-circle wpforms-help-tooltip" title="%s"></i>', esc_attr( $args['tooltip'] ) );
				}
				if ( isset( $args['after_tooltip'] ) && !empty( $args['after_tooltip'] ) ) {
					$output .= $args['after_tooltip'];
				}
				$output .= '</label>';
				break;

			// Text input
			case 'text':
				$type        = !empty( $args['type'] ) ? esc_attr( $args['type'] ) : 'text';
				$placeholder = !empty( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : '';
				$before      = !empty( $args['before'] ) ? '<span class="before-input">' . esc_html( $args['before'] ) . '</span>' : '';
				if ( !empty( $before ) ) {
					$class .= ' has-before';
				}
				$output      = sprintf( '%s<input type="%s" class="%s" id="wpforms-field-option-%d-%s" name="fields[%d][%s]" value="%s" placeholder="%s" %s>', $before, $type, $class, $id, $slug, $id, $slug, esc_attr( $args['value'] ), $placeholder, $data );
				break;

			// Textarea
			case 'textarea':
				$rows   = !empty( $args['rows'] ) ? (int) $args['rows'] : '3';
				$output = sprintf( '<textarea class="%s" id="wpforms-field-option-%d-%s" name="fields[%d][%s]" rows="%d" %s>%s</textarea>', $class, $id, $slug, $id, $slug, $rows, $data, $args['value'] );
				break;

			// Checkbox
			case 'checkbox':
				$checked = checked( '1', $args['value'], false );
				$desc    = !empty( $args['desc'] ) ? $args['desc'] : '';
				$output  = sprintf( '<input type="checkbox" class="%s" id="wpforms-field-option-%d-%s" name="fields[%d][%s]" value="1" %s %s>', $class, $id, $slug, $id, $slug, $checked, $data );
				$output .= sprintf( '<label for="wpforms-field-option-%d-%s" class="inline">%s', $id, $slug, $args['desc'] );
				if ( isset( $args['tooltip'] ) && !empty( $args['tooltip'] ) ) {
					$output .=  sprintf( ' <i class="fa fa-question-circle wpforms-help-tooltip" title="%s"></i>', esc_attr( $args['tooltip'] ) );
				}
				$output .= '</label>';
				break;

			// Toggle
			case 'toggle':
				$checked = checked( '1', $args['value'], false );
				$icon    = $args['value'] ? 'fa-toggle-on' : 'fa-toggle-off';
				$cls     = $args['value'] ? 'wpforms-on' : 'wpforms-off';
				$status  = $args['value'] ? __( 'On', 'wpforms' ) : __( 'Off', 'wpforms' );
				$output  = sprintf( '<span class="wpforms-toggle-icon %s"><i class="fa %s" aria-hidden="true"></i> <span class="wpforms-toggle-icon-label">%s</span>', $cls, $icon, $status );
				$output .= sprintf( '<input type="checkbox" class="%s" id="wpforms-field-option-%d-%s" name="fields[%d][%s]" value="1" %s %s></span>', $class, $id, $slug, $id, $slug, $checked, $data );
				break;

			// Select
			case 'select':
				$options = $args['options'];
				$value   = isset( $args['value'] ) ? $args['value'] : ''; 
				$output  = sprintf( '<select class="%s" id="wpforms-field-option-%d-%s" name="fields[%d][%s]" %s>', $class, $id, $slug, $id, $slug, $data );
					foreach ( $options as $key => $option ) {
						$output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $key, $value, false ), $option );
					}
				$output .= '</select>';
				break;
		}

		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	 * Helper function to create common field options that are used frequently.
	 *
	 * @since 1.0.0
	 * @param string $option
	 * @param array $field
	 * @param array $args
	 * @param boolean $echo
	 * @return mixed echo or return string
	 */
	public function field_option( $option, $field, $args = array(), $echo = true ) {

		switch ( $option ) {

			//----------------------------------------------------------------//
			// Basic Fields
			//----------------------------------------------------------------//

			// Basic Options markup ------------------------------------------//

			case 'basic-options':
				$markup = !empty( $args['markup'] ) ? $args['markup'] : 'open';
				$class  = !empty( $args['class'] ) ? esc_html( $args['class'] ) : '';
				if ( $markup == 'open' ) {
					$output   = sprintf( '<div class="wpforms-field-option-group wpforms-field-option-group-basic" id="wpforms-field-option-basic-%d">', $field['id'] );
					$output  .= sprintf( '<a href="#" class="wpforms-field-option-group-toggle">%s <span>(ID #%d)</span> <i class="fa fa-angle-down"></i></a>', $this->name, $field['id'] );
					$output  .= sprintf( '<div class="wpforms-field-option-group-inner %s">', $class );
				} else {
					$output   = '</div></div>';
				}
				break;

			// Field Label ---------------------------------------------------//

			case 'label':
				$value   = !empty( $field['label'] ) ? esc_attr( $field['label'] ) : '';
				$tooltip = __( 'Enter text for the form field label. Field labels are recommended and can be hidden in the Advanced Settings.', 'wpforms' );
				$output  = $this->field_element( 'label', $field, array( 'slug' => 'label', 'value' => __( 'Label', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output .= $this->field_element( 'text',  $field, array( 'slug' => 'label', 'value' => $value ), false );
				$output  = $this->field_element( 'row',   $field, array( 'slug' => 'label', 'content' => $output ), false );
				break;

			// Field Description ---------------------------------------------//

			case 'description':
				$value   = !empty( $field['description'] ) ? esc_attr( $field['description'] ) : '';
				$tooltip = __( 'Enter text for the form field description.', 'wpforms' );
				$output  = $this->field_element( 'label',    $field, array( 'slug' => 'description', 'value' => __( 'Description', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output .= $this->field_element( 'textarea', $field, array( 'slug' => 'description', 'value' => $value ), false );
				$output  = $this->field_element( 'row',      $field, array( 'slug' => 'description', 'content' => $output ), false );
				break;

			// Field Required toggle -----------------------------------------//

			case 'required':
				$default = !empty( $args['default'] ) ? $args['default'] : '0';
				$value   = isset( $field['required'] ) ? $field['required'] : $default;
				$tooltip = __( 'Check this option to mark the field required. A form will not submit unless all required fields are provided.', 'wpforms' );
				$output  = $this->field_element( 'checkbox', $field, array( 'slug' => 'required', 'value' => $value, 'desc' => __( 'Required', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output  = $this->field_element( 'row',      $field, array( 'slug' => 'required', 'content' => $output ), false );
				break;

			// Field Meta (field type and ID) --------------------------------//

			case 'meta':
				$output  = sprintf( '<label>%s</label>', 'Type' );
				$output .= sprintf( '<p class="meta">%s <span class="id">(ID #%d)</span></p>', $this->name, $field['id']);
				$output  = $this->field_element( 'row', $field, array( 'slug' => 'meta', 'content' => $output ), false );
				break;

			// Code Block ----------------------------------------------------//

			case 'code':
				$value   = !empty( $field['code'] ) ? esc_attr( $field['code'] ) : '';
				$tooltip = __( 'Enter code for the form field.', 'wpforms' );
				$output  = $this->field_element( 'label',    $field, array( 'slug' => 'code', 'value' => __( 'Code', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output .= $this->field_element( 'textarea', $field, array( 'slug' => 'code', 'value' => $value ), false );
				$output  = $this->field_element( 'row',      $field, array( 'slug' => 'code', 'content' => $output ), false );
				break;

			// Choices -------------------------------------------------------//

			case 'choices':

				$tooltip = __( 'Add choices for the form field.', 'wpforms' );
				$dynamic = !empty( $field['dynamic_choices'] ) ? esc_html( $field['dynamic_choices'] ) : '';
				$values  = !empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
				$class   = !empty( $field['show_values'] ) && $field['show_values'] == '1' ? 'show-values' : '';
				$class  .= !empty( $dynamic ) ? ' wpforms-hidden' : '';
				
				// Field option label
				$option_label = $this->field_element( 
					'label', 
					$field, 
					array( 
						'slug'   => 'choices', 
						'value'   => __( 'Choices', 'wpforms' ), 
						'tooltip' => $tooltip 
					), 
					false
				);
				
				// Field option choices inputs
				$option_choices = sprintf( '<ul data-next-id="%s" class="%s" data-field-id="%d" data-field-type="%s">', max( array_keys( $values ) ) +1, $class, $field['id'], $this->type );
					foreach ( $values as $key => $value ) {
						$default = !empty( $value['default'] ) ? $value['default'] : '';
						$option_choices .= sprintf( '<li data-key="%d">', $key );
							$option_choices .= sprintf( '<input type="radio" name="fields[%s][choices][%s][default]" class="default" value="1" %s>', $field['id'], $key, checked( '1', $default, false ) );
							$option_choices .= '<span class="move"><i class="fa fa-bars"></i></span>';
							$option_choices .= sprintf( '<input type="text" name="fields[%s][choices][%s][label]" value="%s" class="label">', $field['id'], $key, esc_attr( $value['label'] ) );
							$option_choices .= '<a class="add" href="#"><i class="fa fa-plus-circle"></i></a>';
							$option_choices .= '<a class="remove" href="#"><i class="fa fa-minus-circle"></i></a>';
							$option_choices .= sprintf( '<input type="text" name="fields[%s][choices][%s][value]" value="%s" class="value">', $field['id'], $key, esc_attr( $value['value'] ) );
						$option_choices .= '</li>';
					}
				$option_choices .= '</ul>';

				// Field option dynamic status
				$source_name       = '';
				$type_name         = '';
				$status_visibility = !empty( $dynamic ) && !empty( $field['dynamic_' . $dynamic ] ) ? '' : 'wpforms-hidden';

				if ( 'post_type' == $dynamic && !empty( $field['dynamic_' . $dynamic ] ) ) {
					
					$type_name   = __( 'post type', 'wpforms' );
					$source      = $field['dynamic_' . $dynamic ];
					$pt          = get_post_type_object( $source );
					$source_name = $pt->labels->name;

				} elseif ( 'taxonomy' == $dynamic && !empty( $field['dynamic_' . $dynamic ] ) ) {

					$type_name   = __( 'taxonomy', 'wpforms' );
					$source      = $field['dynamic_' . $dynamic ];
					$tax         = get_taxonomy( $source );
					$source_name = $tax->labels->name;
				}
						
				$option_status = sprintf( '<div class="wpforms-alert-warning wpforms-alert %s">', $status_visibility );
					$option_status .= sprintf( __( 'Choices are dynamically populated from the <span class="dynamic-name">%s</span> <span class="dynamic-type">%s</span>', 'wpforms' ), $source_name, $type_name );
				$option_status .= '</div>';
				
				// Field option row (markup) including label and input.
				$output = $this->field_element( 
					'row', 
					$field, 
					array( 
						'slug'    => 'choices', 
						'content' => $option_label . $option_choices . $option_status,
					)
				);	
				break;

			//----------------------------------------------------------------//
			// Advanced Fields
			//----------------------------------------------------------------//

			// Default value -------------------------------------------------//

			case 'default_value':
				$value   = !empty( $field['default_value'] ) ? esc_attr( $field['default_value'] ) : '';
				$tooltip = __( 'Enter text for the default form field value.', 'wpforms' );
				$toggle  = '<a href="#" class="toggle-smart-tags"><i class="fa fa-tags"></i> <span>' . __( 'Show Smart Tags', 'wpforms' ) . '</span></a>';
				$output  = $this->field_element( 'label', $field, array( 'slug' => 'default_value', 'value' => __( 'Default Value', 'wpforms' ), 'tooltip' => $tooltip, 'after_tooltip' => $toggle ), false );
				$output .= wpforms()->smart_tags->get( 'list' );
				$output .= $this->field_element( 'text',  $field, array( 'slug' => 'default_value', 'value' => $value ), false );
				$output  = $this->field_element( 'row',   $field, array( 'slug' => 'default_value', 'content' => $output ), false );
				break;

			// Size ----------------------------------------------------------//

			case 'size':
				$value   = !empty( $field['size'] ) ? esc_attr( $field['size'] ) : 'medium';
				$tooltip = __( 'Select the default form field size.', 'wpforms' );
				$options = array(
					'small'  => __( 'Small', 'wpforms' ),
					'medium' => __( 'Medium', 'wpforms'),
					'large'  => __( 'Large', 'wpforms' ),
				);
				$output  = $this->field_element( 'label',  $field, array( 'slug' => 'size', 'value' => __( 'Field Size', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output .= $this->field_element( 'select', $field, array( 'slug' => 'size', 'value' => $value, 'options' => $options ), false );
				$output  = $this->field_element( 'row',    $field, array( 'slug' => 'size', 'content' => $output ), false );
				break;

			// Advanced Options markup ---------------------------------------//

			case 'advanced-options':
				$markup = !empty( $args['markup'] ) ? $args['markup'] : 'open';
				if ( $markup == 'open' ) {
					$override = apply_filters( 'wpforms_advanced_options_override', false );
					$override = !empty( $override ) ? 'style="display:' . $override . ';"' : '';
					$output   = sprintf( '<div class="wpforms-field-option-group wpforms-field-option-group-advanced wpforms-hide" id="wpforms-field-option-advanced-%d" %s>', $field['id'], $override );
					$output  .= sprintf( '<a href="#" class="wpforms-field-option-group-toggle">%s <i class="fa fa-angle-right"></i></a>', __( 'Advanced Options', 'wpforms' ) );
					$output  .= '<div class="wpforms-field-option-group-inner">';
				} else {
					$output   = '</div></div>';
				}
				break;

			// Placeholder ---------------------------------------------------//

			case 'placeholder':
				$value   = !empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
				$tooltip = __( 'Enter text for the form field placeholder.', 'wpforms' );
				$output  = $this->field_element( 'label', $field, array( 'slug' => 'placeholder', 'value' => __( 'Placeholder Text', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output .= $this->field_element( 'text',  $field, array( 'slug' => 'placeholder', 'value' => $value ), false );
				$output  = $this->field_element( 'row',   $field, array( 'slug' => 'placeholder', 'content' => $output ), false );
				break;

			// CSS classes ---------------------------------------------------//

			case 'css':
				$value   = !empty( $field['css'] ) ? esc_attr( $field['css'] ) : '';
				$tooltip = __( 'Enter CSS class names for the form field container. Class names should be separated with spaces.', 'wpforms' );
				// Build output
				$output  = $this->field_element( 'label', $field, array( 'slug' => 'css', 'value' => __( 'CSS Classes', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output .= $this->field_element( 'text',  $field, array( 'slug' => 'css', 'value' => $value ), false );
				$output  = $this->field_element( 'row',   $field, array( 'slug' => 'css', 'content' => $output ), false );
				break;

			// Hide Label ----------------------------------------------------//

			case 'label_hide':
				$value   = isset( $field['label_hide'] ) ? $field['label_hide'] : '0';
				$tooltip = __( 'Check this option to hide the form field label.', 'wpforms' );
				// Build output
				$output  = $this->field_element( 'checkbox', $field, array( 'slug' => 'label_hide', 'value' => $value, 'desc' => __( 'Hide Label', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output  = $this->field_element( 'row',      $field, array( 'slug' => 'label_hide', 'content' => $output ), false );
				break;

			// Hide Sub-Labels -----------------------------------------------//

			case 'sublabel_hide':
				$value   = isset( $field['sublabel_hide'] ) ? $field['sublabel_hide'] : '0';
				$tooltip = __( 'Check this option to hide the form field sub-label.', 'wpforms' );
				// Build output
				$output  = $this->field_element( 'checkbox', $field, array( 'slug' => 'sublabel_hide', 'value' => $value, 'desc' => __( 'Hide Sub-Labels', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output  = $this->field_element( 'row',      $field, array( 'slug' => 'sublabel_hide', 'content' => $output ), false );
				break;

			// Input Columns -------------------------------------------------//

			case 'input_columns':
				$value   = !empty( $field['input_columns'] ) ? esc_attr( $field['input_columns'] ) : '';
				$tooltip = __( 'Select the layout for displaying field choices.', 'wpforms' );
				$options = array(
					''  => __( 'One Column', 'wpforms' ),
					'2' => __( 'Two Columns', 'wpforms'),
					'3' => __( 'Three Columns', 'wpforms' ),
				);
				$output  = $this->field_element( 'label',  $field, array( 'slug' => 'input_columns', 'value' => __( 'Choice Layout', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output .= $this->field_element( 'select', $field, array( 'slug' => 'input_columns', 'value' => $value, 'options' => $options ), false );
				$output  = $this->field_element( 'row',    $field, array( 'slug' => 'input_columns', 'content' => $output ), false );
				break;

			// Dynamic Choices -----------------------------------------------//

			case 'dynamic_choices':
				$value   = !empty( $field['dynamic_choices'] ) ? esc_attr( $field['dynamic_choices'] ) : '';
				$tooltip = __( 'Select auto-populate method to use.', 'wpforms' );
				$options = array(
					''          => __( 'Off', 'wpforms' ),
					'post_type' => __( 'Post Type', 'wpforms'),
					'taxonomy'  => __( 'Taxonomy', 'wpforms' ),
				);
				$output  = $this->field_element( 'label',  $field, array( 'slug' => 'dynamic_choices', 'value' => __( 'Dynamic Choices', 'wpforms' ), 'tooltip' => $tooltip ), false );
				$output .= $this->field_element( 'select', $field, array( 'slug' => 'dynamic_choices', 'value' => $value, 'options' => $options ), false );
				$output  = $this->field_element( 'row',    $field, array( 'slug' => 'dynamic_choices', 'content' => $output ), false );
				break;
			
			// Dynamic Choices Source ----------------------------------------//

			case 'dynamic_choices_source':
				
				$output = '';
				$type   = !empty( $field['dynamic_choices'] ) ? esc_attr( $field['dynamic_choices'] ) : '';

				if ( !empty( $type ) ) {
					
					if ( 'post_type' == $type ) {
					
						$type_name = __( 'Post Type', 'wpforms' );
						$items     = get_post_types( array( 'public' => true ), 'objects' );
						unset( $items['attachment'] );
					
					} elseif ( 'taxonomy' == $type ) {
					
						$type_name = __( 'Taxonomy', 'wpforms' );
						$items     = get_taxonomies( array( 'public' => true ), 'objects' );
						unset( $items['post_format'] );
					}

					$tooltip = sprintf( __( 'Select %s to use for auto-populating field choices.', 'wpforms' ), $type_name );
					$label   = sprintf( __( 'Dynamic %s Source', 'wpforms' ), $type_name );
					$options = array();
					$source  = !empty( $field['dynamic_' . $type ] ) ? esc_attr( $field['dynamic_' . $type] ) : '';

					foreach ( $items as $key => $item ) {
						$options[$key] = $item->labels->name;
					}

					// Field option label
					$option_label  = $this->field_element( 
						'label',  
						$field, 
						array( 
							'slug'    => 'dynamic_' . $type,
							'value'   => $label, 
							'tooltip' => $tooltip,
						),
						false
					);

					// Field option select input
					$option_input = $this->field_element( 
						'select', 
						$field, 
						array( 
							'slug'    => 'dynamic_' . $type, 
							'options' => $options,
							'value'   => $source,
						), 
						false 
					);
					
					// Field option row (markup) including label and input.
					$output  = $this->field_element( 
						'row', 
						$field, 
						array( 
							'slug'    => 'dynamic_' . $type, 
							'content' => $option_label . $option_input
						), 
						false
					);		
				}
				break;
		}

		if ( $echo ) {

			if ( in_array( $option, array( 'basic-options', 'advanced-options' ) ) ) {

				if ( $markup == 'open')
					do_action( "wpforms_field_options_before_{$option}", $field, $this );

				echo $output;

				if ( $markup == 'close')
					do_action( "wpforms_field_options_after_{$option}", $field, $this );

			} else {
				echo $output;
			}

		} else {
			return $output;
		}
	}

	/**
	 * Helper function to create common field options that are used frequently
	 * in the field preview.
	 *
	 * @since 1.0.0
	 * @param string $option
	 * @param array $field
	 * @param array $args
	 * @param boolean $echo
	 * @return mixed echo or return string
	 */
	public function field_preview_option( $option, $field, $args = array(), $echo = true ) {

		switch ( $option ) {

			case 'label':
				$label  = isset( $field['label'] ) && !empty( $field['label'] ) ? esc_html( $field['label'] ) : '';
				$output = sprintf( '<label class="label-title"><span class="text">%s</span><span class="required">*</span></label>', $label );
				break;

			case 'description';
				$description = isset( $field['description'] ) && !empty( $field['description'] ) ? $field['description'] : '';
				$output      = sprintf( '<div class="description">%s</div>', $description );
				break;
		}

		if ( $echo ) {
			echo $output;
		} else {
			return $output;
		}
	}

	/**
	 * Create a new field in the admin AJAX editor.
	 *
	 * @since 1.0.0
	 */
	public function field_new() {

		// Run a security check
		check_ajax_referer( 'wpforms-builder', 'nonce' );

		// Check for permissions
		if ( !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) )
			die( __( 'You do no have permission.', 'wpforms' ) );

		// Check for form ID
		if ( !isset( $_POST['id'] ) || empty( $_POST['id'] ) )
			die( __( 'No form ID found', 'wpforms' ) );

		// Check for field type to add
		if ( !isset( $_POST['type'] ) || empty( $_POST['type'] ) )
			die( __( 'No field type found', 'wpforms' ) );

		// Grab field data
		$field_args     = !empty( $_POST['defaults'] ) ? (array) $_POST['defaults'] : array();
		$field_type     = esc_attr( $_POST['type'] );
		$field_id       = wpforms()->form->next_field_id( $_POST['id'] );
		$field          = array(
			'id'          => $field_id,
			'type'        => $field_type,
			'label'       => $this->name,
			'description' => '',
		);
		$field          = wp_parse_args( $field_args, $field );
		$field          = apply_filters( 'wpforms_field_new_default', $field );
		$field_required = apply_filters( 'wpforms_field_new_required', '', $field );
		$field_class    = apply_filters( 'wpforms_field_new_class', '', $field );

		// Field types that default to required
		if ( !empty( $field_required ) ) {
			$field_required = 'required';
			$field['required'] = '1';
		}

		// Build Preview
		ob_start();
		$this->field_preview( $field );
		$prev     = ob_get_clean();
		$preview  = sprintf( '<div class="wpforms-field wpforms-field-%s %s %s" id="wpforms-field-%d" data-field-id="%d" data-field-type="%s">', $field_type, $field_required, $field_class, $field['id'], $field['id'], $field_type );
		$preview .= sprintf( '<a href="#" class="wpforms-field-duplicate" title="%s"><i class="fa fa-files-o" aria-hidden="true"></i></a>', __( 'Duplicate Field', 'wpforms' ) );
		$preview .= sprintf( '<a href="#" class="wpforms-field-delete" title="%s"><i class="fa fa-times-circle"></i></a>', __( 'Delete Field', 'wpforms' ) );
		$preview .= sprintf( '<span class="wpforms-field-helper">%s</span>', __( 'Click to edit. Drag to reorder.', 'wpforms' ) );
		$preview .= $prev;
		$preview .= '</div>';

		// Build Options
		$options  = sprintf( '<div class="wpforms-field-option wpforms-field-option-%s" id="wpforms-field-option-%d" data-field-id="%d">', esc_attr( $field['type'] ), $field['id'], $field['id'] );
		$options .= sprintf( '<input type="hidden" name="fields[%d][id]" value="%d">', $field['id'], $field['id'] );
		$options .= sprintf( '<input type="hidden" name="fields[%d][type]" value="%s">', $field['id'], esc_attr( $field['type'] ) );
		ob_start();
		$this->field_options( $field );
		$options .= ob_get_clean();
		$options .= '</div>';

		// Prepare to return compiled results
		$return = array(
			'form_id' => $_POST['id'],
			'field'   => $field,
			'preview' => $preview,
			'options' => $options,
		);
		wp_send_json_success( $return );
	}

	/**
	 * Field display on the form front-end.
	 *
	 * @since 1.0.0
	 * @param array $field
	 * @param array $form_data
	 */
	public function field_display( $field, $field_atts, $form_data ) {
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

		// Basic required check - If field is marked as required, check for entry data
		if ( !empty( $form_data['fields'][$field_id]['required'] ) && empty( $field_submit ) && '0' != $field_submit ) {

			wpforms()->process->errors[$form_data['id']][$field_id] = apply_filters( 'wpforms_required_label', __( 'This field is required', 'wpforms' ) );
		}
	}

	/**
	 * Formats and sanitizes field.
	 *
	 * @since 1.0.0
	 * @param int $field_id
	 * @param array $field_submit
	 * @param array $form_data
	 */
	public function format( $field_id, $field_submit, $form_data ) {

		if ( is_array( $field_submit ) ) {
			$field_submit = array_filter( $field_submit );
			$field_submit = implode( "\r\n", $field_submit );
		}

		$name  = !empty( $form_data['fields'][$field_id]['label'] ) ? sanitize_text_field( $form_data['fields'][$field_id]['label'] ) : '';

		// Hack to keep line breaks
		$value = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $field_submit ) ) );

		wpforms()->process->fields[$field_id] = array(
			'name'  => $name,
			'value' => $value,
			'id'    => absint( $field_id ),
			'type'  => $this->type,
		);
	}
}