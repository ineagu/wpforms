<?php
/**
 * Generates the table on the plugin overview page.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Overview_Table extends WP_List_Table {

	/**
	 * Numbe of forms to show per page.
	 *
	 * @since 1.0.0
	 */
	public $per_page;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Bring globals into scope for parent.
		global $status, $page;

		// Utilize the parent constructor to build the main class properties.
		parent::__construct(
			array(
				'singular' => 'form',
				'plural'   => 'forms',
				'ajax'     => false,
			)
		);

		// Default number of forms to show per page
		$this->per_page = apply_filters( 'wpforms_overview_per_page', 20 );
	}

	/**
	 * Retrieve the table columns
	 *
	 * @since 1.0.0
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {

		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'form_name'  => __( 'Name', 'wpforms' ),
			'shortcode'  => __( 'Shortcode', 'wpforms' ),
			'created'    => __( 'Created', 'wpforms' ),
			// 'id'         => __( 'ID', 'wpforms' ),
			//'modified'   => __( 'Modified', 'wpforms' ),
			//'author'     => __( 'Author', 'wpforms' ),
			//'php'        => __( 'PHP Code', 'wpforms' ),
		);

		return apply_filters( 'wpforms_overview_table_columns', $columns );
	}

	/**
	 * Render the checkbox column.
	 *
	 * @since 1.0.0
	 * @param array
	 * @return string
	 */
	public function column_cb( $form ) {

		return '<input type="checkbox" name="form_id[]" value="' . absint( $form->ID ) . '" />';
	}

	/**
	 * Renders the columns.
	 *
	 * @since 1.0.0
	 * @param array $form 
	 * @param string $column_name
	 * @return string
	 */
	public function column_default( $form, $column_name ) {

		  switch( $column_name ) { 

				case 'id':
					$value = $form->ID;
					break;
				
				case 'shortcode':
					$value = '[wpforms id="' . $form->ID . '"]';
					break;
				
				case 'created':
					$value = get_the_date( get_option( 'date_format' ), $form );
					break;
				
				case 'modified':
					$value = get_post_modified_time( get_option( 'date_format' ), false, $form );
					break;
				
				case 'author':
					$author = get_userdata( $form->post_author );
					$value = $author->display_name;
					break;
				
				case 'php':
					$value = '<code style="display:block;font-size:11px;">if( function_exists( \'wpforms_get\' ) ){ wpforms_get( ' . $form->ID . ' ); }</code>';
					break;
				
				default:
					$value = '';
			}

		return apply_filters( 'wpforms_overview_table_column_value', $value, $form, $column_name );
	}

	/**
	 * Render the form name column with action links.
	 *
	 * @since 1.0.0
	 * @param array $optin
	 * @return string
	 */
	public function column_form_name( $form ) {

		// Prepare variables.
		$name = ! empty( $form->post_title ) ? $form->post_title : $form->post_name;
		$name = sprintf( '<a class="row-title" href="%s" title="%s"><strong>%s</strong></a>',
			add_query_arg( array( 'view' => 'fields', 'form_id' => $form->ID ), admin_url( 'admin.php?page=wpforms-builder' ) ),
			__( 'Edit this form', 'wpforms' ), 
			$name
		);
		
		// Build all of the row action links.
		$row_actions = array();

		// Edit
		$row_actions['edit'] = sprintf( '<a href="%s" title="%s">%s</a>',
			add_query_arg( array( 'view' => 'fields', 'form_id' => $form->ID ), admin_url( 'admin.php?page=wpforms-builder' ) ),
			__( 'Edit this form', 'wpforms' ),
			__( 'Edit', 'wpforms' ) 
		);

		// Entries
		$row_actions['entries'] = sprintf( '<a href="%s" title="%s">%s</a>',
			add_query_arg( array( 'view' => 'list', 'form_id' => $form->ID ), admin_url( 'admin.php?page=wpforms-entries' ) ),
			__( 'View entries', 'wpforms' ),
			__( 'Entries', 'wpforms' )
		);

		// Preview
		$row_actions['preview_'] = sprintf( '<a href="%s" title="%s" target="_blank">%s</a>',
			esc_url( wpforms()->preview->form_preview_url( $form->ID ) ),
			__( 'View preview', 'wpforms' ),
			__( 'Preview', 'wpforms' )
		);

		// Duplicate
		$row_actions['duplicate']  = sprintf( '<a href="%s" title="%s">%s</a>',
			wp_nonce_url( add_query_arg( array( 'action' => 'duplicate', 'form_id' => $form->ID ), admin_url( 'admin.php?page=wpforms-overview' ) ) , 'wpforms_duplicate_form_nonce' ),
			__( 'Duplicate this form', 'wpforms' ),
			__( 'Duplicate', 'wpforms' )
		);

		// Delete
		$row_actions['delete']  = sprintf( '<a href="%s" title="%s">%s</a>',
			wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'form_id' => $form->ID ), admin_url( 'admin.php?page=wpforms-overview' ) ) , 'wpforms_delete_form_nonce' ),
			__( 'Delete this form', 'wpforms' ),
			__( 'Delete', 'wpforms' )
		);

		// Build the row action links and return the value.
		$value = $name . $this->row_actions( $row_actions );
		
		return apply_filters( 'wpforms_overview_row_actions', $value, $form );
	}

	/**
	 * Define bulk actions available for our table listing
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_bulk_actions() {

		$actions = array(
			'delete' => __('Delete', 'wpforms' ),
		);
		return $actions;
	}

	/**
	 * Process the bulk actions
	 *
	 * @since 1.0.0
	 */
	public function process_bulk_actions() {

		$ids = isset( $_GET['form_id'] ) ? $_GET['form_id'] : array();

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids    = array_map( 'absint', $ids );
		$action = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;

		if ( empty( $ids ) || empty( $action ) ) {
			return;
		}

		// Delete one or multiple forms - both delete links and bulk actions	
		if ( 'delete' === $this->current_action() ) {

			if ( wp_verify_nonce( $_GET['_wpnonce'], 'bulk-forms' ) || wp_verify_nonce( $_GET['_wpnonce'], 'wpforms_delete_form_nonce' ) ) {
				foreach ( $ids as $id ) {
					wpforms()->form->delete( $id );
				}
				printf( '<div class="updated"><p>%s</p></div>', _n( 'Form successfully deleted.', 'Forms successfully deleted', count( $ids ), 'wpforms' ) );
			} else {
				printf( '<div class="error"><p>%s</p></div>', __( 'Security check failed', 'wpforms' ) );
			}
		}

		// Duplicate form - currently just delete links (no bulk action at the moment)
		if ( 'duplicate' === $this->current_action() ) {

			if ( wp_verify_nonce( $_GET['_wpnonce'], 'wpforms_duplicate_form_nonce' ) ) {
				foreach ( $ids as $id ) {
					$duplicate = wpforms()->form->duplicate( $id );
				}
				printf( '<div class="updated"><p>%s</p></div>', _n( 'Form successfully duplicated.', 'Forms successfully duplicated', count( $ids ), 'wpforms' ) );
			} else {
				printf( '<div class="error"><p>%s</p></div>', __( 'Security check failed', 'wpforms' ) );
			}
		}
	}

	/**
	 * Message to be displayed when there are no forms
	 *
	 * @since 1.0.0
	 */
	public function no_items() {

		printf( __( 'Whoops, you haven\'t created a form yet. Want to <a href="%s">give it a go</a>?', 'wpforms' ), admin_url( 'admin.php?page=wpforms-builder' ) );
	}

	/**
	 * Fetch and setup the final data for the table
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {

		// Process bulk actions if found
		$this->process_bulk_actions();

		// Setup the columns
		$columns  = $this->get_columns();

		// Hidden columns (none)
		$hidden = array();

		// Define which columns can be sorted - form name, date
		$sortable = array( 
			'form_name' => array( 'title', false ), 
			'created'   => array( 'date', false ) 
		);

		// Set column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Get forms
		$total    = wp_count_posts( 'wpforms' )->publish;
		$page     = $this->get_pagenum();
		$order    = isset( $_GET['order'] ) ? $_GET['order'] : 'DESC';
		$orderby  = isset( $_GET['orderby'] ) ? $_GET['orderby'] : 'ID';
		$per_page = $this->get_items_per_page( 'wpforms_forms_per_page', $this->per_page );
		$data     = wpforms()->form->get( '', array( 
			'orderby'        => $orderby, 
			'order'          => $order,
			'nopaging'       => false,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'no_found_rows'  => false,
		) );

		// Giddy up
		$this->items = $data;

		// Finalize pagination
		$this->set_pagination_args( 
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total / $per_page ),
			)
		);
	}

}