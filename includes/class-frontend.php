<?php
/**
 * Form front-end rendering.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Frontend {

	/**
	 * Contains form data to be referenced later.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $forms;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->forms = array();

		// Actions
		add_action( 'wpforms_frontend_output_success', array( $this, 'confirmation'   ), 10,  2 );
		add_action( 'wpforms_frontend_output',         array( $this, 'head'           ), 5,   5 );
		add_action( 'wpforms_frontend_output',         array( $this, 'page_indicator' ), 10,  5 );
		add_action( 'wpforms_frontend_output',         array( $this, 'fields'         ), 10,  5 );
		add_action( 'wpforms_frontend_output',         array( $this, 'honeypot'       ), 15,  5 );
		add_action( 'wpforms_frontend_output',         array( $this, 'recaptcha'      ), 20,  5 );
		add_action( 'wpforms_frontend_output',         array( $this, 'foot'           ), 25,  5 );
		add_action( 'wp_enqueue_scripts',              array( $this, 'assets_header'  )         );
		add_action( 'wp_footer',                       array( $this, 'assets_footer'  ), 15     );
		add_action( 'wp_footer',                       array( $this, 'footer_end'     ), 99     );

		// Register shortcode
		add_shortcode( 'wpforms', array( $this, 'shortcode' ) );
	}

	/**
	 * Primary function to render a form on the frontend.
	 *
	 * @since 1.0.0
	 * @param int $id
	 * @param boolean $title
	 * @param boolean $description
	 */
	public function output( $id, $title = false, $description = false ) {

		if ( empty( $id ) )
			return;

		// Grab the form data, if not found then we bail
		$form = wpforms()->form->get( (int) $id );

		if ( empty( $form ) )
			return;

		// Basic information
		$form_data   = wpforms_decode( $form->post_content, true );
		$form_id     = absint( $form->ID );
		$settings    = $form_data['settings'];
		$action      = esc_url_raw( remove_query_arg( 'wpforms' ) );
		$class[]     = wpforms_setting( 'disable-css', '1' ) == '1' ? 'wpforms-container-full' : '';
		$errors      = empty( wpforms()->process->errors[$form->ID] ) ? array() : wpforms()->process->errors[$form->ID];
		$success     = false;
		$title       = $title == 'false' ? false : $title;
		$description = $description == 'false' ? false : $description;

		// If the form does not contain any fields do not proceed
		if ( empty( $form_data['fields'] ) ) {
			echo '<!-- WPForms: no fields, form hidden -->';
			return;
		}

		// Before output hook
		do_action( 'wpforms_frontend_output_before', $form_data, $form );

		// Check for return hash OR error free completed form and confirmation before we continue
		if ( !empty( $_GET['wpforms_return'] ) ) {
			$success = wpforms()->process->validate_return_hash( $_GET['wpforms_return'] );
			if ( $success ) {
				$form_data = wpforms()->form->get( $success, array( 'content_only' => true ) );
			}
		} elseif ( !empty( $_POST['wpforms']['id'] ) && $form->ID == $_POST['wpforms']['id'] && empty( $errors ) ) {
			$success = true;
		}
		if ( $success && !empty( $form_data ) ) {

			do_action( 'wpforms_frontend_output_success', $form_data );

			// Debug
			wpforms_debug_data( $_POST );

			return;
		}

		// Allow filter to return early if some condition is not met.
		if ( ! apply_filters( 'wpforms_frontend_load', true, $form_data, $form ) ) {
			return;
		}

		// Prep the form action URL, allow filter
		if ( !empty( $settings['confirmation_type'] ) && 'message' == $settings['confirmation_type'] && !empty( $settings['confirmation_message_scroll'] ) ) {
			$action .= '#wpforms-' . $form_id;
		} 
		$action = apply_filters( 'wpforms_frontend_form_action', $action, $form_data, $form  );

		// Allow form container classes to be filtered
		$class = array_map( 'sanitize_html_class', apply_filters( 'wpforms_frontend_container_class', $class, $form_data ) );
		
		if ( !empty( $form_data['settings']['form_class'] ) ) {
			$class = array_merge( $class, array_map('sanitize_html_class', explode( ' ', $form_data['settings']['form_class'] ) ) );
		}

		// Begin to build the output
		echo '<div class="wpforms-container ' . implode( ' ', $class ) . '" id="wpforms-' . $form_id . '">';

			echo '<form method="post" enctype="multipart/form-data" id="wpforms-form-' . $form_id . '" action="'  . $action . '" class="wpforms-validate wpforms-form" data-formid="' . $form_id . '">';

				do_action( 'wpforms_frontend_output', $form_data, $form, $title, $description, $errors  );

			echo '</form>';

		echo '</div>';

		// After output hook
		do_action( 'wpforms_frontend_output_after', $form_data, $form );

		$this->forms[$form_id] = $form_data;

		// Debug
		wpforms_debug_data( $form_data );
	}

	/**
	 * Display form confirmation message.
	 *
	 * @since 1.0.0
	 * @param array $form_data
	 * @param mixed $title
	 * @param mixed $description
	 */
	function confirmation( $form_data ) {

		if ( !empty( $form_data['settings']['confirmation_type'] ) && 'message' == $form_data['settings']['confirmation_type'] ) {

			// Load confirmatiom specific asssets
			$this->assets_confirmation();

			$complete = !empty( $_POST['wpforms']['complete'] ) ? $_POST['wpforms']['complete'] : array();
			$entry_id = !empty( $_POST['wpforms']['entry_id'] ) ? $_POST['wpforms']['entry_id'] : 0;
			$message  = apply_filters( 'wpforms_process_smart_tags', $form_data['settings']['confirmation_message'], $form_data, $complete, $entry_id );
			$message  = apply_filters( 'wpforms_frontend_confirmation_message', $message, $form_data );

			$class = wpforms_setting( 'disable-css', '1' ) == '1' ? 'wpforms-confirmation-container-full' : 'wpforms-confirmation-container';

			echo '<div class="' . $class . '" id="wpforms-confirmation-' . absint( $form_data['id'] ) . '">';

				echo wpautop( $message );

			echo '</div>';
		}
	}

	/**
	 * Form head area.
	 *
	 * @since 1.0.0
	 * @param array $form_data
	 * @param object $form
	 * @param mixed $title
	 * @param mixed $description
	 */
	public function head( $form_data, $form, $title, $description, $errors ) {

		// Output title and/or desc
		if ( !empty( $title ) || !empty( $description ) ) {

			echo '<div class="wpforms-head-container">';

				if ( !empty( $title ) && !empty( $form->post_title ) ) {
					echo '<div class="wpforms-title">' . esc_html( $form->post_title ) . '</div>';
				}

				if ( !empty( $description ) && !empty( $form->post_excerpt ) ) {
					echo '<div class="wpforms-description">' . $form->post_excerpt . '</div>';
				}

			echo '</div>';
		}

		// Output errors if they exist
		if ( !empty( $errors['header'] ) ) {

			echo '<div class="wpforms-error-container">';
				
				$allow = array(
					'a'      => array(
						'href'  => array(),
						'title' => array()
					),
					'br'     => array(),
					'em'     => array(),
					'strong' => array(),
					'p'      => array(),
				);
				echo wp_kses( $errors['header'], $allow );

			echo '</div>';
		}
	}

	/**
	 * Page Indictor
	 *
	 * This displays if the form contains pagebreaks and is configured to show
	 * a page indicator in the top pagebreak settings.
	 *
	 * @since 1.2.1
	 * @param array $form_data
	 * @param object $form
	 * @param mixed $title
	 * @param mixed $description
	 * @param array $errors
	 */
	public function page_indicator( $form_data, $form, $title, $description, $errors ) {

		$pagebreak_top = wpforms_get_pagebreak( $form_data, 'top' );

		if ( empty( $pagebreak_top['indicator'] ) || 'none' == apply_filters( 'wpforms_frontend_indicator_theme', $pagebreak_top['indicator'], $form_data ) ) {
			return;
		}

		$pagebreak = array(
			'indicator' => sanitize_html_class( $pagebreak_top['indicator'] ),
			'color'     => wpforms_sanitize_hex_color( $pagebreak_top['indicator_color'] ),
			'pages'     => wpforms_get_pagebreak( $form_data, 'pages' ),
		);
		$p = 1;

		printf('<div class="wpforms-page-indicator %s" data-indicator="%s" data-indicator-color="%s">',
			$pagebreak['indicator'],
			$pagebreak['indicator'],
			$pagebreak['color']
		);
			
			if ( 'circles' == $pagebreak['indicator'] ) {

				// Circles theme
				foreach ( $pagebreak['pages'] as $page ) {
					$class = ( 1 === $p ) ? 'active' : '';
					$bg    = ( 1 === $p ) ? 'style="background-color:' . $pagebreak['color'] . '"' : '';
					printf( '<div class="wpforms-page-indicator-page %s wpforms-page-indicator-page-%d">', $class, $p );
						printf( '<span class="wpforms-page-indicator-page-number" %s>%d</span>', $bg, $p );
						if ( !empty( $page['title'] ) ) {
							printf( '<span class="wpforms-page-indicator-page-title">%s<span>', esc_html( $page['title'] ) );
						}
					echo '</div>';
					$p++;
				}

			} elseif ( 'connector' == $pagebreak['indicator'] ) {

				// Connector theme
				foreach ( $pagebreak['pages'] as $page ) {
					$class  = ( 1=== $p ) ? 'active ' : '';
					$bg     = ( 1=== $p ) ? 'style="background-color:' . $pagebreak['color'] . '"' : '';
					$border = ( 1=== $p ) ? 'style="border-top-color:' . $pagebreak['color'] . '"' : '';
					$width  = 100/(count($pagebreak['pages'])) . '%';
					printf( '<div class="wpforms-page-indicator-page %s wpforms-page-indicator-page-%d" style="width:%s;">', $class, $p, $width );
						printf( '<span class="wpforms-page-indicator-page-number" %s>%d<span class="wpforms-page-indicator-page-triangle" %s></span></span>', $bg, $p, $border );
						if ( !empty( $page['title'] ) ) {
							printf( '<span class="wpforms-page-indicator-page-title">%s<span>', esc_html( $page['title'] ) );
						}
					echo '</div>';
					$p++;
				}

			} elseif ( 'progress' == $pagebreak['indicator'] ) {

				// Progress theme
				$p1    = !empty( $pagebreak['pages'][0]['title'] ) ? esc_html( $pagebreak['pages'][0]['title'] ) : '';
				$sep   = empty( $p1 ) ? 'style="display:none;"' : '';
				$width = 100/(count($pagebreak['pages'])) . '%';
				$prog  = 'style="width:' . $width . ';background-color:' . $pagebreak['color'] . ';"';
				$names = '';
				$step  = __( 'Step', 'wpforms' );
				$of    = __( 'of', 'wpforms' );
				
				foreach ( $pagebreak['pages'] as $page ) {
					if ( !empty( $page['title'] ) ) {
						$names .= sprintf( 'data-page-%d-title="%s" ', $p, esc_attr( $page['title'] ) );
					}
					$p++;
				}
				printf( '<span class="wpforms-page-indicator-page-title" %s>%s</span>', $names, $p1 );
				printf( '<span class="wpforms-page-indicator-page-title-sep" %s> - </span>', $sep );				
				printf( '<span class="wpforms-page-indicator-steps">%s <span class="wpforms-page-indicator-steps-current">1</span> %s %d</span>', $step, $of, count( $pagebreak['pages'] ) );
				printf( '<div class="wpforms-page-indicator-page-progress-wrap"><div class="wpforms-page-indicator-page-progress" %s></div></div>', $prog );
			}

			do_action( 'wpforms_frontend_indicator', $pagebreak, $form_data );
	
		echo '</div>';
	}

	/**
	 * Form field area.
	 *
	 * @since 1.0.0
	 * @param array $form_data
	 * @param object $form
	 * @param mixed $title
	 * @param mixed $description
	 */
	public function fields( $form_data, $form, $title, $description, $errors ) {

		if ( empty( $form_data['fields'] ) )
			return;

		$fields    = $form_data['fields'];
		$pagebreak = wpforms_has_pagebreak( $form_data );
		$page      = 0;

		// Form fields area
		echo '<div class="wpforms-field-container">';

			// Pagebreak, begin first page
			if ( $pagebreak ) {
				$pbt     = wpforms_get_pagebreak( $form_data, 'top');
				$pbt_css = !empty( $pbt['css'] ) ? wpforms_sanitize_classes( $pbt['css'] ) : '';
				echo '<div class="wpforms-page wpforms-page-1 ' . $pbt_css . '">';
			}

			// Loop through all the fields we have
			foreach ( $fields as $field ) {

				if ( $field['type'] == 'pagebreak' ) {
					if ( !empty( $field['position'] ) && 'top' == $field['position'] ) {
						continue;
					} else {
						$page++;
						$form_data['page_total']   = $pagebreak;
						$form_data['page_current'] = $page;
					}
				}

				$field = apply_filters( 'wpforms_field_data', $field, $form_data );

				if ( ! $fields ) {
					continue;
				}

				// Basic generic attributes for easy filtering
				$field_atts = array(
					'field_class' => array(
						'wpforms-field',
						'wpforms-field-' . sanitize_html_class( $field['type'] ),
					),
					'field_id' => array(
						'wpforms-' . absint( $form_data['id'] ) . '-field_' . absint( $field['id'] ) . '-container',
					),
					'field_style' => '',
					'label_class' => array(
						'wpforms-field-label',
					),
					'label_id' => '',
					'description_class' => array(
						'wpforms-field-description'
					),
					'description_id' => array(),
					'input_id' => array(
						'wpforms-' . absint( $form_data['id'] ) . '-field_' . absint( $field['id'] ),
					),
					'input_class' => array(),
					'input_data' => array(),
				);

				// Check user defined classes
				if ( !empty( $field['css'] ) ) {
					$user_classes = explode( ' ', str_replace('.', '', $field['css'] ) );
					foreach( $user_classes as $user_class ) {
						$field_atts['field_class'][] = sanitize_html_class( $user_class );
					}
				}
				// Check input columns
				if ( !empty( $field['input_columns'] ) ) {
					if ( '2' == $field['input_columns'] ) {
						$field_atts['field_class'][] = 'wpforms-list-2-columns';
					} elseif ( '3' == $field['input_columns'] ) {
						$field_atts['field_class'][] = 'wpforms-list-3-columns';
					}
				}
				// Check size
				if ( !empty( $field['size'] ) ) {
					$field_atts['input_class'][] = 'wpforms-field-' . sanitize_html_class( $field['size'] );
				}
				// Check if required
				if ( !empty( $field['required'] ) ) {
					$field_atts['input_class'][] = 'wpforms-field-required';
				}
				// Check if there are errors
				if ( !empty( wpforms()->process->errors[$form_data['id']][$field['id']] ) ) {
					$field_atts['input_class'][] = 'wpforms-error';
				}

				$field_atts = apply_filters( 'wpforms_field_atts', $field_atts, $field, $form_data );

				echo '<div class="' . implode( ' ', $field_atts['field_class'] ) . '" id="' . implode( ' ', $field_atts['field_id'] ) . '" style="' .  esc_html( $field_atts['field_style'] ) . '">';

					// Display label if we have one
					if ( !empty( $field['label'] ) ) {

						// Check special flat that allows fields to disable labels on front-end
						if ( empty( $field['label_disable'] ) ) {

							// If user has decided to hide the label, hide it using a special
							// CSS class in an attempt to keep it readable by screen readers
							if ( !empty( $field['label_hide'] ) ) {
								$field_atts['label_class'][] = 'wpforms-label-hide';
							}

							$label_class = !empty( $field_atts['label_class'] ) ? ' class="' . implode( ' ', $field_atts['label_class'] ) . '"' : '';
							$label_id    = !empty( $field_atts['label_id'] ) ? ' id="' . $field_atts['label_id'] . '"' : '';

							echo '<label for="wpforms-' . absint( $form_data['id'] ) . '-field_' . absint( $field['id'] ) . '"' . $label_class . $label_id . '>';

								echo esc_html( $field['label'] );

								if ( !empty( $field['required'] ) ) {
									echo apply_filters( 'wpforms_field_required_label', ' <span class="wpforms-required-label">*</span>' );
								}

							echo '</label>';

						}
					}

					// Trigger the method to output this field type
					do_action( "wpforms_display_field_{$field['type']}", $field, $field_atts, $form_data );

					// Display errors if we have one
					if ( !empty( wpforms()->process->errors[$form_data['id']][$field['id']] ) ) {

						$error = wpforms()->process->errors[$form_data['id']][$field['id']];

						// For some advanced fields with multiple inputs (such
						// as address or name fields) we handle the displaying
						// the error within the field class. In these instances
						// the field error container will be an array. So below
						// we only show the error message for normal fields.
						if ( !is_array( $error ) ) {
							echo '<label id="wpforms-field_' . intval( $field['id'] ) . '-error" class="wpforms-error" for="wpforms-field_' . intval( $field['id'] ) . '">' . esc_html( wpforms()->process->errors[$form_data['id']][$field['id']] ) . '</label>';
						}
					}

					// Display description if we have one
					if ( !empty( $field['description'] ) ) {

						echo '<div class="' . implode( ' ', $field_atts['description_class'] ) . '" id="' . implode( ' ', $field_atts['description_id'] ) . '">';

							echo apply_filters( 'wpforms_process_smart_tags', $field['description'], $form_data );

						echo '</div>';
					}

				echo '</div>';

				// Pagebreak, end current page and begin the next
				if ( $field['type'] == 'pagebreak' && $page != $pagebreak ) {
					$next = $page+1;
					$last = $next == $pagebreak ? 'last' : '';
					$css  = !empty( $field['css'] ) ? wpforms_sanitize_classes( $field['css'] ) : '';
					printf ('</div><div class="wpforms-page wpforms-page-%s %s %s" style="display:none;">', $next, $last, $css );
				}
			}

			// Pagebreak, end last page
			if ( $pagebreak ) {

				// If we don't have a bottom pagebreak, the form is pre-v1.2.1
				// and this is for backwards compatibility.
				$pbb = wpforms_get_pagebreak( $form_data, 'bottom' );
				if ( ! $pbb ) {
					$prev = !empty( $form_data['settings']['pagebreak_prev'] ) ? esc_html( $form_data['settings']['pagebreak_prev'] ) : __('Previous', 'wpforms' );
					echo '<div class="wpforms-field wpforms-field-pagebreak">';
						printf(
							'<button class="wpforms-page-button wpforms-page-prev" data-action="prev" data-page="%d" data-formid="%d">%s</button>',
							$page+1,
							$form_data['id'],
							$prev
						);
					echo '</div>';
				}

				echo '</div>';
			}

		echo '</div>';
	}

	/**
	 * Anti-spam honeypot output if configured.
	 *
	 * @since 1.0.0
	 * @param array $form_data
	 * @param object $form
	 * @param mixed $title
	 * @param mixed $description
	 */
	public function honeypot( $form_data, $form, $title, $description, $errors ) {

		if ( empty( $form_data['settings']['honeypot'] ) || '1' != $form_data['settings']['honeypot'] )
			return;

		$names = array( 'Name', 'Phone', 'Comment', 'Message', 'Email', 'Website' );

		echo '<div class="wpforms-field wpforms-field-hp" id="wpform-field-hp">';

			echo '<label for="wpforms-field_hp" class="wpforms-field-label">' . esc_html( $names[ array_rand( $names ) ] ) . '</label>';

			echo '<input type="text" name="wpforms[hp]" id="wpforms-field_hp" class="wpforms-field-medium">';

		echo '</div>';
	}

	/**
	 * reCAPTCHA output if configured.
	 *
	 * @since 1.0.0
	 * @param array $form_data
	 * @param object $form
	 * @param mixed $title
	 * @param mixed $description
	 */
	public function recaptcha( $form_data, $form, $title, $description, $errors ) {

		// Check that recaptcha is configured in the settings
		$site_key   = wpforms_setting( 'recaptcha-site-key', '' );
		$secret_key = wpforms_setting( 'recaptcha-secret-key', '' );
		if ( empty( $site_key ) || empty( $secret_key ) )
			return;

		// Check that the recaptcha is configured for the specific form
		if ( !isset( $form_data['settings']['recaptcha'] ) || '1' != $form_data['settings']['recaptcha'] )
			return;

		$d    = '';
		$datas = apply_filters( 'wpforms_frontend_recaptcha', array( 'sitekey' => $site_key ), $form_data );

		echo '<div class="wpforms-recaptcha-container">';

			foreach( $datas as $key => $data ) {
				$d .= 'data-' . $key . '="' . esc_attr( $data ) . '" ';
			}

			echo '<div class="g-recaptcha" ' . $d . '></div>';

			if ( !empty( wpforms()->process->errors[$form_data['id']]['recaptcha'] ) ) {
				echo '<label id="wpforms-field_recaptcah-error" class="wpforms-error">' . esc_html( wpforms()->process->errors[$form_data['id']]['recaptcha'] ) . '</label>';
			}

		echo '</div>';
	}

	/**
	 * Form footer arera.
	 *
	 * @since 1.0.0
	 * @param array $form_data
	 * @param object $form
	 * @param mixed $title
	 * @param mixed $description
	 */
	public function foot( $form_data, $form, $title, $description, $errors ) {

		$settings       = $form_data['settings'];
		$submit         = apply_filters( 'wpforms_field_submit' , esc_html( $settings['submit_text'] ), $form_data );
		$submit_process = '';
		$submit_classes = array();
		$visible        = wpforms_has_pagebreak( $form_data ) ? 'style="display:none;"' : '';

		// Check for submit button alt-text
		if ( !empty( $settings['submit_text_processing'] ) ) {
			$submit_process = 'data-alt-text="' . esc_attr( $settings['submit_text_processing'] ) .'"';
		}

		// Check user defined submit button classes
		if ( !empty( $settings['submit_class'] ) ) {
			$user_classes = explode( ' ', str_replace('.', '', $settings['submit_class'] ) );
			foreach( $user_classes as $user_class ) {
				$submit_classes[] = sanitize_html_class( $user_class );
			}
		}

		// Output errors if they exist
		if ( !empty( $errors['footer'] ) ) {

			echo '<div class="wpforms-error-container">';

				$allow = array(
					'a'      => array(
						'href'  => array(),
						'title' => array()
					),
					'br'     => array(),
					'em'     => array(),
					'strong' => array(),
					'p'      => array(),
				);
				echo wp_kses( $errors['footer'], $allow );

			echo '</div>';
		}

		// Submit button area
		echo '<div class="wpforms-submit-container" ' . $visible . '>';

				echo '<input type="hidden" name="wpforms[id]" value="' . $form->ID . '">';

				printf( 
					'<button type="submit" name="wpforms[submit]" class="wpforms-submit %s" id="wpforms-submit-%d" value="wpforms-submit" %s>%s</button>',
					implode( ' ', $submit_classes ),
					$form->ID,
					$submit_process,
					$submit
				);

		echo '</div>';
	}

	/**
	 * Determine if we should load assets globally. If false assets will
	 * load conditionally (default).
	 *
	 * @since 1.2.4
	 * @return bool
	 */
	public function assets_global() {

		return  apply_filters( 'wpforms_global_assets', wpforms_setting( 'global-assets', false ) );
	}

	/**
	 * Load the necessary CSS for single pages/posts earlier if possible.
	 *
	 * If we are viewing a singular page, then we can check the content early
	 * to see if the shortcode was used. If not we fallback and load the assets
	 * later on during the page (widgets, archives, etc).
	 *
	 * @since 1.0.0
	 */
	public function assets_header() {

		if ( !is_singular() ) {
			return;
		}

		global $post;

		if ( has_shortcode( $post->post_content, 'wpforms' ) ) {

			$this->assets_css();
		}
	}

	/**
	 * Load the CSS assets for frontend output.
	 *
	 * @since 1.0.0
	 */
	public function assets_css() {

		do_action( 'wpforms_frontend_css', $this->forms );

		// jquery date/time library CSS
		if ( $this->assets_global() || true == wpforms_has_field_type( 'date-time', $this->forms, true ) ) :
		wp_enqueue_style(
			'wpforms-jquery-timepicker',
			WPFORMS_PLUGIN_URL . 'assets/css/jquery.timepicker.css',
			array(),
			'1.11.5'
		);
		wp_enqueue_style(
			'wpforms-flatpickr',
			WPFORMS_PLUGIN_URL . 'assets/css/flatpickr.min.css',
			array(),
			'2.0.5'
		);
		endif;

		// Load CSS per global setting
		if ( wpforms_setting( 'disable-css', '1' ) == '1' ) {
			wp_enqueue_style(
				'wpforms-full',
				WPFORMS_PLUGIN_URL . 'assets/css/wpforms-full.css',
				array(),
				WPFORMS_VERSION
			);
		} 
		if ( wpforms_setting( 'disable-css', '1' ) == '2' ) {
			wp_enqueue_style(
				'wpforms-base',
				WPFORMS_PLUGIN_URL . 'assets/css/wpforms-base.css',
				array(),
				WPFORMS_VERSION
			);
		}
	}

	/**
	 * Load the JS assets for frontend output.
	 *
	 * @since 1.0.0
	 */
	public function assets_js() {

		do_action( 'wpforms_frontend_js', $this->forms );

		// Load jquery validation library - http://jqueryvalidation.org/
		wp_enqueue_script(
			'wpforms-validation',
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.validate.min.js',
			array( 'jquery' ),
			'1.15.1',
			true
		);

		// Load jquery date/time libraries
		if ( $this->assets_global() || true == wpforms_has_field_type( 'date-time', $this->forms, true ) ) :
		wp_enqueue_script(
			'wpforms-flatpickr',
			WPFORMS_PLUGIN_URL . 'assets/js/flatpickr.min.js',
			array( 'jquery' ),
			'2.0.5',
			true
		);
		wp_enqueue_script(
			'wpforms-jquery-timepicker',
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.timepicker.min.js',
			array( 'jquery' ),
			'1.11.5',
			true
		);
		endif;

		// Load jquery input mask library - https://github.com/RobinHerbots/jquery.inputmask
		if ( $this->assets_global() || true == wpforms_has_field_type( array( 'phone', 'address' ), $this->forms, true ) ) :
		wp_enqueue_script(
			'wpforms-maskedinput',
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.inputmask.bundle.min.js',
			array( 'jquery' ),
			'3.2.8',
			true
		);
		endif;

		// Load CC payment library - https://github.com/stripe/jquery.payment/
		if ( $this->assets_global() || true == wpforms_has_field_type( 'credit-card', $this->forms, true ) ) :
		wp_enqueue_script(
			'wpforms-payment',
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.payment.min.js',
			array( 'jquery' ),
			WPFORMS_VERSION,
			true
		);
		endif;

		// Load base JS
		wp_enqueue_script(
			'wpforms',
			WPFORMS_PLUGIN_URL . 'assets/js/wpforms.js',
			array( 'jquery' ),
			WPFORMS_VERSION,
			true
		);

		// If we have payment fields then include currency details
		$payment_fields = array( 'credit-card', 'payment-single', 'payment-multiple', 'payment-total' );
		if ( ( $this->assets_global() || true == wpforms_has_field_type( $payment_fields , $this->forms, true ) ) && function_exists( 'wpforms_get_currencies' ) ) :
		$currency   = wpforms_setting( 'currency', 'USD' );
		$currencies = wpforms_get_currencies();
		wp_localize_script( 
			'wpforms', 
			'wpforms_currency',
			array(
				'code'       => $currency,
				'thousands'  => $currencies[$currency]['thousands_separator'],
				'decimal'    =>	$currencies[$currency]['decimal_separator'],
				'symbol'     => $currencies[$currency]['symbol'],
				'symbol_pos' => $currencies[$currency]['symbol_pos']
			) 
		);
		endif;

		// Load reCAPTCHA support if form supports it
		$site_key   = wpforms_setting( 'recaptcha-site-key' );
		$secret_key = wpforms_setting( 'recaptcha-secret-key' );
		if ( !empty( $site_key ) && !empty( $secret_key ) ) {
			wp_enqueue_script(
				'wpforms-recaptcha',
				'https://www.google.com/recaptcha/api.js?onload=wpformsRecaptcha&render=explicit',
				array( 'jquery' ),
				'2.0.0',
				true
			);
			$reCAPTCHA_init = 'var wpformsRecaptcha = function(){
				jQuery(".g-recaptcha").each(function(index, el) {
					grecaptcha.render(el, {sitekey : \'' . $site_key . '\'});
				});
			};';
			wp_add_inline_script( 'wpforms-recaptcha', $reCAPTCHA_init );
		}
	}

	/**
	 * Load the necessary assets for the confirmation message.
	 *
	 * @since 1.1.2
	 */
	public function assets_confirmation() {

		// Base CSS only
		if ( wpforms_setting( 'disable-css', '1' ) == '1' ) {
			wp_enqueue_style(
				'wpforms-full',
				WPFORMS_PLUGIN_URL . 'assets/css/wpforms-full.css',
				array(),
				WPFORMS_VERSION
			);
		}

		// Special confirmation JS
		wp_enqueue_script(
			'wpforms-confirmation',
			WPFORMS_PLUGIN_URL . 'assets/js/wpforms-confirmation.js',
			array( 'jquery' ),
			WPFORMS_VERSION,
			true
		);

		do_action( 'wpforms_frontend_confirmation' );
	}

	/**
	 * Load the assets in footer if needed (archives, widgets, etc).
	 *
	 * @since 1.0.0
	 */
	public function assets_footer() {

		if ( empty( $this->forms ) && ! $this->assets_global() )
			return;

		$this->assets_css();
		$this->assets_js();

		do_action( 'wpforms_wp_footer', $this->forms );
	}

	/**
	 * Hook at fires at a later priority in wp_footer
	 *
	 * @since 1.0.5
	 */
	public function footer_end() {

		if ( empty( $this->forms ) && ! $this->assets_global() )
			return;

		do_action( 'wpforms_wp_footer_end', $this->forms );
	}

	/**
	 * Shortcode wrapper for the outputting a form.
	 *
	 * @since 1.0.0
	 * @param array $atts
	 * @return array
	 */
	public function shortcode( $atts ) {

		$atts = shortcode_atts( array(
			'id'          => false,
			'title'       => false,
			'description' => false,
		), $atts, 'output' );

		ob_start();

		$this->output( $atts['id'], $atts['title'], $atts['description'] );

		$output = ob_get_clean();

		return $output;
	}
}