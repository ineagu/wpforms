<?php
/**
 * Setup panel.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Builder_Panel_Setup extends WPForms_Builder_Panel {

	/**
	 * All systems go.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		// Define panel information
		$this->name  = __( 'Setup', 'wpforms' );
		$this->slug  = 'setup';
		$this->icon  = 'fa-cog';
		$this->order = 5;
	}

	/**
	 * Enqueue assets for the builder.
	 *
	 * @since 1.0.0
	 */
	public function enqueues() {

		// CSS
		wp_enqueue_style( 
			'wpforms-builder-setup', 
			WPFORMS_PLUGIN_URL . 'assets/css/admin-builder-setup.css', 
			null, 
			WPFORMS_VERSION
		);
	}

	/**
	 * Outputs the Settings panel primary content.
	 *
	 * @since 1.0.0
	 */
	public function panel_content() {
		
		?>

		<div id="wpforms-setup-form-name">

			<span><?php _e( 'Form Name', 'wpforms' ); ?></span>

			<input type="text" id="wpforms-setup-name" placeholder="<?php _e( 'Enter your form name here&hellip;', 'wpforms' ); ?>">

		</div>

		<div class="wpforms-setup-title">
			<?php _e( 'Select a Template', 'wpforms' ); ?>
		</div>

		<p class="wpforms-setup-desc">
			<?php _e( 'To speed up the process, you can select from one of our pre-made templates or start with a <strong><a href="#" class="wpforms-trigger-blank">blank form.</a></strong>', 'wpforms' ); ?>
		</p>

		<div class="wpforms-setup-templates wpforms-clear">

			<?php 
			$templates = apply_filters( 'wpforms_form_templates', array() );

			if ( !empty( $templates ) ) {
				$x = 0;
				foreach ( $templates as $template ) {
					$selected = false;
					$class    =  0 == $x % 3 ? 'first ' : '';
					$class   .= !empty( $template['class'] ) ? sanitize_html_class( $template['class'] ) . ' ' : '';
					if ( !empty( $this->form_data['meta']['template'] ) && $this->form_data['meta']['template'] == $template['slug'] ) {
						$class .= 'selected ';
						$selected = true;
					}
					?>

					<div class="wpforms-template <?php echo $class; ?>" id="wpforms-template-<?php echo sanitize_html_class( $template['slug'] ); ?>">

						<div class="wpforms-template-name wpforms-clear">
							<?php echo esc_html( $template['name'] ); ?>
							<?php echo $selected ? '<span class="selected">' . __( 'Selected', 'wpforms' ) . '</span>' : ''; ?>
						</div>
						
						<div class="wpforms-template-details">
							<p class="desc"><?php echo esc_html( $template['description'] ); ?></p>
						</div>
						
						<div class="wpforms-template-overlay">
							<a href="#" class="wpforms-template-select" data-template-name-raw="<?php echo esc_attr( $template['name'] ); ?>" data-template-name="<?php echo esc_attr( $template['name'] ); ?> <?php _e( 'template', 'wpforms' ); ?>" data-template="<?php echo esc_attr( $template['slug'] ); ?>"><?php printf( __('Create a %s', 'wpforms' ), esc_html( $template['name'] ) ); ?></a>
						</div>
						
					</div>
					<?php
					$x++;
				}
			}
			?>
		</div>
		<?php
		do_action( 'wpforms_setup_panel_after' );
	}
}
new WPForms_Builder_Panel_Setup;