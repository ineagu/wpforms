<?php
/**
 * Form builder that contains magic.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Builder {

	/**
	 * Current view (panel)
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $view;

	/**
	 * Available panels.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $panels;

	/**
	 * Current form.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	public $form;

	/**
	 * Current template information.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $template;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Maybe load form builder
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	/**
	 * Determing if the user is viewing the builder, if so, party on.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Check what page we are on
		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		// Only load if we are actually on the builder
		if ( $page == 'wpforms-builder' ) {

			// Load form if found
			$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

			if ( $form_id ) {
				// Default view for with an existing form is fields panel
				$this->view = isset( $_GET['view'] ) ? $_GET['view'] : 'fields';
			} else {
				// Defualt view for new field is the setup panel
				$this->view = isset( $_GET['view'] ) ? $_GET['view'] : 'setup';
			}

			// Preview page check
			wpforms()->preview->form_preview_check();

			// Fetch form
			$this->form = wpforms()->form->get( $form_id );

			// Fetch template information
			$this->template = apply_filters( 'wpforms_builder_template_active', array(), $this->form );

			// Load builder panels
			$this->load_panels();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
			add_action( 'wpforms_admin_page',    array( $this, 'output'   ) );
			
			// Provide hook for add-ons
			do_action( 'wpforms_builder_init', $this->view );

			add_filter( 'teeny_mce_plugins', array( $this, 'tinymce_buttons' ) );
		}
	}

	/**
	 * Define TinyMCE buttons to use with our fancy editor instances.
	 *
	 * @since 1.0.3
	 * @param array $buttons
	 * @return array
	 */
	public function tinymce_buttons( $buttons ) {

		$buttons = array( 'colorpicker', 'lists', 'wordpress', 'wpeditimage', 'wplink' );
		return $buttons;
	}

	/**
	 * Load panels.
	 *
	 * @since 1.0.0
	 */
	public function load_panels() {

		// Base class and functions
		require_once WPFORMS_PLUGIN_DIR . 'includes/admin/builder/panels/class-base.php';

		$this->panels = apply_filters( 'wpforms_builder_panels', array(
			'setup',
			'fields',
			'settings',
			'providers',
			'payments',
		) );

		foreach ( $this->panels as $panel ) {

			if ( file_exists( WPFORMS_PLUGIN_DIR . 'includes/admin/builder/panels/class-' . $panel . '.php' ) ) {
				require_once WPFORMS_PLUGIN_DIR . 'includes/admin/builder/panels/class-' . $panel . '.php';
			} elseif ( file_exists( WPFORMS_PLUGIN_DIR . 'pro/includes/admin/builder/panels/class-' . $panel . '.php' ) ) {
				require_once WPFORMS_PLUGIN_DIR . 'pro/includes/admin/builder/panels/class-' . $panel . '.php';
			}
		}
	}

	/**
	 * Enqueue assets for the builder.
	 *
	 * @since 1.0.0
	 */
	public function enqueues() {

		do_action( 'wpforms_builder_enqueues_before', $this->view );

		// CSS
		wp_enqueue_style( 
			'wpforms-font-awesome', 
			WPFORMS_PLUGIN_URL . 'assets/css/font-awesome.min.css', 
			null, 
			'4.4.0'
		);

		wp_enqueue_style( 
			'tooltipster', 
			WPFORMS_PLUGIN_URL . 'assets/css/tooltipster.css', 
			null, 
			'3.3.0'
		);

		wp_enqueue_style( 
			'jquery-confirm', 
			WPFORMS_PLUGIN_URL . 'assets/css/jquery-confirm.min.css', 
			null, 
			'2.0.0'
		);

		wp_enqueue_style( 
			'minicolors', 
			WPFORMS_PLUGIN_URL . 'assets/css/jquery.minicolors.css', 
			null, 
			'2.2.3'
		);

		wp_enqueue_style( 
			'wpforms-builder', 
			WPFORMS_PLUGIN_URL . 'assets/css/admin-builder.css', 
			null, 
			WPFORMS_VERSION
		);

		// JS
		
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-draggable' );

		wp_enqueue_script( 
			'serialize-object', 
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.serialize-object.min.js', 
			array( 'jquery' ), 
			'2.5.0', 
			false
		);

		wp_enqueue_script( 
			'tooltipster', 
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.tooltipster.min.js', 
			array( 'jquery' ), 
			'3.3.0', 
			false
		);

		wp_enqueue_script( 
			'jquery-confirm', 
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.jquery-confirm.min.js', 
			array(), 
			'2.0.0', 
			false
		);

		wp_enqueue_script( 
			'matchheight', 
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.matchHeight-min.js', 
			array( 'jquery' ), 
			'0.7.0', 
			false
		);

		wp_enqueue_script( 
			'insert-at-caret', 
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.insert-at-caret.min.js', 
			array( 'jquery' ), 
			'1.1.4', 
			false
		);

		wp_enqueue_script( 
			'minicolors', 
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.minicolors.min.js', 
			array( 'jquery' ), 
			'2.2.3', 
			false
		);

		wp_enqueue_script( 
			'wpforms-utils', 
			WPFORMS_PLUGIN_URL . 'assets/js/admin-utils.js', 
			array( 'jquery', 'serialize-object' ), 
			WPFORMS_VERSION, 
			false
		);

		wp_enqueue_script( 
			'wpforms-builder', 
			WPFORMS_PLUGIN_URL . 'assets/js/admin-builder.js', 
			array( 'jquery', 'serialize-object', 'tooltipster' ), 
			WPFORMS_VERSION, 
			false
		);

		$strings = array(
			'ajax_url'               => admin_url( 'admin-ajax.php' ),
			'date_select_day'        => __( 'DD', 'wpforms' ),
			'date_select_month'      => __( 'MM', 'wpforms' ),
			'dynamic_choice_limit'   => __( 'The {source} {type} contains over {limit} items ({total}). This may make the field difficult for your vistors to use and/or cause the form to be slow.', 'wpforms' ),
			'cancel'                 => __( 'Cancel', 'wpforms' ),
			'ok'                     => __( 'OK', 'wpforms' ),
			'close'                  => __( 'Close', 'wpforms' ),
			'conditionals_change'    => __( 'Due to form changes, conditional logic rules have been removed or updated:', 'wpforms' ),
			'field'                  => __( 'Field', 'wpforms' ),
			'field_locked'           => __( 'Field Locked', 'wpforms' ),
			'field_locked_msg'       => __( 'This field cannot be deleted or duplicated.', 'wpforms' ),
			'fields_available'       => __( 'Available Fields', 'wpforms' ),
			'fields_unavailable'     => __( 'No fields available', 'wpforms' ),
			'heads_up'               => __( 'Heads up!', 'wpforms' ),
			'nonce'                  => wp_create_nonce( 'wpforms-builder' ),
			'no_email_fields'        => __( 'No email fields', 'wpforms' ),
			'notification_delete'    => __( 'Are you sure you want to delete this notification?', 'wpforms' ),
			'notification_prompt'    => __( 'Enter a notification name', 'wpforms' ),
			'notification_ph'        => __( 'Eg: User Confirmation', 'wpforms' ),
			'notification_error'     => __( 'You must provide a notification name', 'wpforms' ),
			'notification_error2'    => __( 'Form must contain one notification. To disable all notifications use the setting Notifications dropdown setting.', 'wpforms' ),
			'saving'                 => __( 'Saving ...', 'wpforms' ),
			'saved'                  => __( 'Saved!', 'wpforms' ),
			'save_exit'              => __( 'Save and Exit', 'wpforms' ),
			'loading'                => __( 'Loading', 'wpforms' ),
			'template_name'          => !empty( $this->template['name'] ) ? $this->template['name'] : '',
			'template_slug'          => !empty( $this->template['slug'] ) ? $this->template['slug'] : '',
			'template_modal_title'   => !empty( $this->template['modal']['title'] ) ? $this->template['modal']['title'] : '',
			'template_modal_msg'     => !empty( $this->template['modal']['message'] ) ? $this->template['modal']['message'] : '',
			'template_modal_display' => !empty( $this->template['modal_display'] ) ? $this->template['modal_display'] : '',
			'template_select'        => __( 'Use Template', 'wpforms' ),
			'template_confirm'       => __( 'Changing templates on an existing form will DELETE existing form fields. Are you sure you want apply the new template?', 'wpforms' ),
			'embed_modal'            => __( 'You are almost done. To embed this form on your site, please paste the following shortcode inside a post or page.', 'wpforms' ),
			'embed_modal_2'          => __( 'Or you can follow the instructions in this video.', 'wpforms' ),
			'exit'                   => __( 'Exit', 'wpforms' ),
			'exit_url'               => admin_url( 'admin.php?page=wpforms-overview' ),
			'exit_confirm'           => __( 'If you exit without saving, your changes will be lost.', 'wpforms' ),
			'delete_confirm'         => __( 'Are you sure you want to delete this field?', 'wpforms' ),
			'duplicate_confirm'      => __( 'Are you sure you want to duplicate this field?', 'wpforms' ),
			'duplicate_copy'         => __( '(copy)', 'wpforms'),
			'error_title'            => __( 'Please enter a form title.', 'wpforms' ),
			'error_choice'           => __( 'This item must contain at least one choice.', 'wpforms' ),
			'off'                    => __( 'Off', 'wpforms' ),
			'on'                     => __( 'On', 'wpforms' ),
			'other'                  => __( 'Other', 'wpforms' ),
			'previous'               => __( 'Previous', 'wpforms' ),
			'saved_state'            => '',
			'smart_tags'             => wpforms()->smart_tags->get(),
			'smart_tags_show'        => __( 'Show Smart Tags', 'wpforms' ),
			'smart_tags_hide'        => __( 'Hide Smart Tags', 'wpforms' ),
			'select_field'           => __( '-- Select Field --', 'wpforms' ),
			'select_choice'          => __( '-- Select Choice --', 'wpforms' ),
		);
		$strings = apply_filters( 'wpforms_builder_strings', $strings, $this->form );

		if ( !empty( $_GET['form_id'] ) ) {
			$strings['preview_url'] = add_query_arg( array( 'new_window' => 1 ), wpforms()->preview->form_preview_url( $_GET['form_id'] ) );
		}

		wp_localize_script(
			'wpforms-builder',
			'wpforms_builder',
			$strings
		);
	
		// Hook for add-ons
		do_action( 'wpforms_builder_enqueues', $this->view );
	}

	/**
	 * Load the appropriate files to build the page.
	 *
	 * @since 1.0.0
	 */
	public function output() {
		
		$form_id   = $this->form ? absint( $this->form->ID ): '';
		$form_data = $this->form ? wpforms_decode( $this->form->post_content ) : false;
		?>
		<div id="wpforms-builder">

			<div id="wpforms-builder-overlay">

				<div class="wpforms-builder-overlay-content">
					
					<i class="fa fa-cog fa-spin"></i>
					
					<span class="msg"><?php _e( 'Loading', 'wpforms'); ?></span>
				</div>

			</div>

			<form name="wpforms-builder" id="wpforms-builder-form" method="post" data-id="<?php echo $form_id; ?>">

				<input type="hidden" name="id" value="<?php echo $form_id; ?>">
				<input type="hidden" value="<?php echo absint( $form_data['field_id'] ); ?>" name="field_id" id="wpforms-field-id">

				<!-- Toolbar -->
				<div class="wpforms-toolbar">

					<div class="wpforms-left">
						
						<img src="<?php echo WPFORMS_PLUGIN_URL; ?>/assets/images/logo-builder.png" alt="Sullie WPForms mascot">

					</div>

					<div class="wpforms-center">
						
						<?php if ( $this->form ) : ?>

							<?php _e( 'Now editing', 'wpforms' ); ?> <span class="wpforms-center-form-name wpforms-form-name"><?php echo esc_html( $this->form->post_title ); ?></span>

						<?php endif; ?>

					</div>

					<div class="wpforms-right">
						
						<?php if ( $this->form ) : ?>

						<!--<a href="<?php echo esc_url( wpforms()->preview->form_preview_url( $form_id ) ); ?>" id="wpforms-preview" title="<?php _e( 'Preview Form', 'wpforms' ); ?>">
							<i class="fa fa-eye"></i> 
							<span class="text"><?php _e( 'Preview', 'wpforms' ); ?></span>
						</a>-->
						
						<a href="#" id="wpforms-embed" title="<?php _e( 'Embed Form', 'wpforms' ); ?>">
							<i class="fa fa-code"></i> 
							<span class="text"><?php _e( 'Embed', 'wpforms' ); ?></span>
						</a>
						
						<a href="#" id="wpforms-save" title="<?php _e( 'Save Form', 'wpforms' ); ?>">
							<i class="fa fa-check"></i> 
							<span class="text"><?php _e( 'Save', 'wpforms' ); ?></span>
						</a>

						<?php endif; ?>

						<a href="#" id="wpforms-exit" title="<?php _e( 'Exit', 'wpforms' ); ?>">
							<i class="fa fa-times"></i>
						</a>

					</div>

				</div>

				<!-- Panel toggle buttons -->
				<div class="wpforms-panels-toggle" id="wpforms-panels-toggle">
					
					<?php do_action( 'wpforms_builder_panel_buttons', $this->form, $this->view ); ?>

				</div>

				<div class="wpforms-panels">
					
					<?php do_action( 'wpforms_builder_panels', $this->form, $this->view ); ?>

				</div>

			</form>

		</div>
		<?php
	}
}
new WPForms_Builder;