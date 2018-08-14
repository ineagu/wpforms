<?php
/**
 * Primary overview page inside the admin which lists all forms.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Overview {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Maybe load overview page
		add_action( 'admin_init', array( $this, 'init' ) );

		// Setup screen options
		add_action( 'load-toplevel_page_wpforms-overview', array( $this, 'screen_options' ) );
		add_filter( 'set-screen-option', array( $this, 'screen_options_set' ), 10, 3 );
	}

	/**
	 * Determing if the user is viewing the overview page, if so, party on.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		
		// Check what page we are on
		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		// Only load if we are actually on the overview page
		if ( $page == 'wpforms-overview' ) {

			// The overview page leverages WP_List_Table so we must load it
			if ( ! class_exists( 'WP_List_Table' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
			}

			// Load the class that builds the overview table
			require_once WPFORMS_PLUGIN_DIR . 'includes/admin/overview/class-overview-table.php';

			// Preview page check
			wpforms()->preview->form_preview_check();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
			add_action( 'wpforms_admin_page',    array( $this, 'output'   ) );

			// Provide hook for add-ons
			do_action( 'wpforms_overview_init' );
		}
	}

	/**
	 * Add per-page screen option to the Forms table.
	 *
	 * @since 1.0.0
	 */
	function screen_options() {

		$screen = get_current_screen();

		if ( $screen->id !== 'toplevel_page_wpforms-overview' ) {
			return;
		}

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Number of forms per page:', 'wpforms' ),
				'option'  => 'wpforms_forms_per_page',
				'default' => apply_filters( 'wpforms_overview_per_page', 20 ),
			)
		);
	}

	/**
	 * Forms table per-page screen option value
	 *
	 * @since 1.0.0
	 * @param mixed $status
	 * @param string $option
	 * @param mixed $value
	 * @return mixed
	 */
	function screen_options_set( $status, $option, $value ) {

		if ( 'wpforms_forms_per_page' === $option ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Enqueue assets for the overview page.
	 *
	 * @since 1.0.0
	 */
	public function enqueues() {

		wp_enqueue_style( 
			'wpforms-overview', 
			WPFORMS_PLUGIN_URL . 'assets/css/admin-overview.css', 
			null, 
			WPFORMS_VERSION 
		);

		wp_enqueue_script( 
			'wpforms-overview', 
			WPFORMS_PLUGIN_URL . 'assets/js/admin-overview.js', 
			array( 'jquery' ), 
			WPFORMS_VERSION, 
			false
		);

		wp_localize_script(
			'wpforms-overview',
			'wpforms_overview',
			array(
				'delete_confirm'    => __( 'Are you sure you want to delete this form?', 'wpforms' ),
				'duplicate_confirm' => __( 'Are you sure you want to duplicate this form?', 'wpforms' ),
			)
		);
		
		// Hook for add-ons
		do_action( 'wpforms_overview_enqueue' );
	}

	/**
	 * Build the output for the overview page.
	 *
	 * @since 1.0.0
	 */
	public function output() {

		?>
		<div id="wpforms-overview" class="wrap">

			<h1 class="page-title">
				<?php _e( 'Forms Overview', 'wpforms' ); ?>
				<a href="<?php echo admin_url( 'admin.php?page=wpforms-builder&view=setup' ); ?>" class="add-new-h2"><?php _e( 'Add New', 'wpforms' ); ?></a>
			</h1>

			<?php
			$overview_table = new WPForms_Overview_Table; 
			$overview_table->prepare_items();
			?>
			
			<form id="wpforms-overview-table" method="get" action="<?php echo admin_url( 'admin.php?page=wpforms-overview' ); ?>">
				
				<input type="hidden" name="post_type" value="wpforms" />
				
				<input type="hidden" name="page" value="wpforms-overview" />
				
				<?php $overview_table->views(); ?>
				<?php $overview_table->display(); ?>
				
			</form>

		</div>
		<?php
	}
}
new WPForms_Overview;