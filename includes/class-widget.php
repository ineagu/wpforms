<?php
/**
 * WPForms widget.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.2
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Widget extends WP_Widget {

	/**
	 * Holds widget settings defaults, populated in constructor.
	 *
	 * @since 1.0.2
	 * @var array
	 */
	protected $defaults;

	/**
	 * Constructor
	 *
	 * @since 1.0.2
	 */
	function __construct() {

		// widget defaults
		$this->defaults = array(
			'title'      => '',
			'form_id'    => '',
			'show_title' => false,
			'show_desc'  => false,
		);
		
		// Widget Slug
		$widget_slug = 'wpforms-widget';

		// widget basics
		$widget_ops = array(
			'classname'   => $widget_slug,
			'description' => 'Display a form.'
		);

		// widget controls
		$control_ops = array(
			'id_base' => $widget_slug,
		);

		// load widget
		parent::__construct( $widget_slug, 'WPForms', $widget_ops, $control_ops );		
	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @since 1.0.2
	 * @param array $args An array of standard parameters for widgets in this theme 
	 * @param array $instance An array of settings for this widget instance 
	 */
	function widget( $args, $instance ) {

		extract( $args );

		// Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		echo $before_widget;

			// Title
			if ( !empty( $instance['title'] ) ) { 
				echo $before_title . apply_filters( 'widget_title' , $instance['title'] ) . $after_title;
			}

			// Form
			if ( !empty( $instance['form_id'] ) ) {
				wpforms()->frontend->output( absint( $instance['form_id'] ), $instance['show_title'], $instance['show_desc'] );
			}

		echo $after_widget;
	}

	/**
	 * Deals with the settings when they are saved by the admin. Here is
	 * where any validation should be dealt with.
	 *
	 * @since 1.0.2
	 * @param array $new_instance An array of new settings as submitted by the admin
	 * @param array $old_instance An array of the previous settings 
	 * @return array The validated and (if necessary) amended settings
	 */
	function update( $new_instance, $old_instance ) {

		$new_instance['title']      = strip_tags( $new_instance['title'] );
		$new_instance['form_id']    = absint( $new_instance['form_id'] );
		$new_instance['show_title'] = isset( $new_instance['show_title'] ) ? '1' : false;
		$new_instance['show_desc']  = isset( $new_instance['show_desc'] ) ? '1' : false;

		return $new_instance;
	}

	/**
	 * Displays the form for this widget on the Widgets page of the WP Admin area.
	 *
	 * @since 1.0.2
	 * @param array $instance An array of the current settings for this widget
	 */
	function form( $instance ) {

		// Merge with defaults
		$instance = wp_parse_args( (array) $instance, $this->defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'form_id' ); ?>">Form:</label>
			<select id="<?php echo $this->get_field_id( 'form_id' ); ?>" name="<?php echo $this->get_field_name( 'form_id' ); ?>" class="widefat">
				<?php
				$forms = wpforms()->form->get();
				if ( !empty( $forms ) ) {
					foreach ( $forms as $form ) {
						printf( '<option value="%d" %s>%s</option>', $form->ID, selected( $instance['form_id'], $form->ID, false ), esc_html( $form->post_title ) );
					}		
				} else {
					printf( '<option value="">%s</option>', __( 'No forms', 'wpforms' ) );
				}
				?>
			</select>
		</p>
		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_title' ); ?>" name="<?php echo $this->get_field_name( 'show_title' ); ?>" <?php checked( '1', $instance['show_title'] ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_title' ); ?>"><?php _e( 'Display form title', 'wpforms' ); ?></label>
			<br>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_desc' ); ?>" name="<?php echo $this->get_field_name( 'show_desc' ); ?>" <?php checked( '1', $instance['show_desc'] ); ?>>
			<label for="<?php echo $this->get_field_id( 'show_desc' ); ?>"><?php _e( 'Display form description', 'wpforms' ); ?></label>
		</p>
		<?php
	}
}
add_action( 'widgets_init', create_function( '', "register_widget('WPForms_Widget');" ) );