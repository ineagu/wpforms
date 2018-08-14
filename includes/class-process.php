<?php
/**
 * Process and vaidate form entries.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Process {

	/**
	 * Holds errors.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $errors;

	/**
	 * Holds formatted fields.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $fields;

	/**
	 * Holds the ID of a successful entry.
	 *
	 * @since 1.2.3
	 * @var int
	 */
	public $entry_id = 0;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'wp', array( $this, 'listen' ) );
	}

	/**
	 * Listen to see if this is a return callback or a posted form entry.
	 *
	 * @since 1.0.0
	 */
	public function listen() {

		if ( !empty( $_GET['wpforms_return'] ) ) {
			$this->entry_confirmation_redirect( '', $_GET['wpforms_return'] );
		}

		if ( !empty( $_POST['wpforms']['id'] ) ) {
			$this->process( stripslashes_deep( $_POST['wpforms'] ) );
		}
	}

	/**
	 * Process the form entry.
	 *
	 * @since 1.0.0
	 * @param array $form $_POST object
	 */
	public function process( $entry ) {

		$this->errors = array();
		$this->fields = array();
		$form_id      = absint( $entry['id'] );
		$form         = wpforms()->form->get( $form_id );
		$honeypot     = false;

		// Validate form is real and active (published)
		if ( !$form || 'publish' != $form->post_status ) {
			$this->errors[$form_id]['header'] = __( 'Invalid form.', 'wpforms' );
			return;
		}

		// Formatted form data for hooks
		$form_data = apply_filters( 'wpforms_process_before_form_data', wpforms_decode( $form->post_content ), $entry );

		// Pre-process/validate hooks and filter. Data is not validated or
		// cleaned yet so use with caution.
		$entry = apply_filters( 'wpforms_process_before_filter', $entry, $form_data );

		do_action( 'wpforms_process_before', $entry, $form_data );
		do_action( "wpforms_process_before_{$form_id}", $entry, $form_data );

		// Validate fields
		foreach ( $form_data['fields'] as $field ) {

			$field_id     = $field['id'];
			$field_type   = $field['type'];
			$field_submit = isset( $entry['fields'][$field_id] ) ? $entry['fields'][$field_id] : '';

			do_action( "wpforms_process_validate_{$field_type}", $field_id, $field_submit, $form_data );
		}

		// reCAPTCHA check
		$site_key   = wpforms_setting( 'recaptcha-site-key', '' );
		$secret_key = wpforms_setting( 'recaptcha-secret-key', '' );
		if ( !empty( $site_key ) || !empty( $secret_key ) ) {
			if ( isset( $form_data['settings']['recaptcha'] ) && '1' == $form_data['settings']['recaptcha'] ) {
				// We should have a reCAPTCHA so let's process
				$response = $_POST['g-recaptcha-response'];
				$secret   = wpforms_setting( 'recaptcha-secret-key' );
				$data     = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $response );
				$data     = json_decode( wp_remote_retrieve_body( $data ) );
				if ( empty( $data->success ) ) {
					$this->errors[$form_id]['recaptcha'] = __( 'Incorrect reCAPTCHA, please try again.', 'wpforms' );
				}
			}
		}

		// One last error check - don't proceed if there are any errors
		if ( !empty( $this->errors[$form_id] ) ) {
			$this->errors[$form_id]['header'] = __( 'Form has not been submitted, please see the errors below.', 'wpforms' );
			return;
		}

		// Validate honeypot
		if ( !empty( $form_data['settings']['honeypot'] ) && '1' == $form_data['settings']['honeypot'] ) {
			if ( isset( $entry['hp'] ) && !empty( $entry['hp'] ) ) {
				$honeypot = __( 'WPForms honeypot field triggered.', 'wpforms' );
			}
		}

		$honeypot = apply_filters( 'wpforms_process_honeypot', $honeypot, $this->fields, $entry, $form_data );

		// Only trigger the processing (saving/sending entries, etc) if the entry
		// is not spam.
		if ( !$honeypot ) {

			// Pass the form created date into the form data
			$form_data['created'] = $form->post_date;

			// Format fields
			foreach ( $form_data['fields'] as $field ) {

				$field_id     = $field['id'];
				$field_type   = $field['type'];
				$field_submit = isset( $entry['fields'][$field_id] ) ? $entry['fields'][$field_id] : '';

				do_action( "wpforms_process_format_{$field_type}", $field_id, $field_submit, $form_data );
			}

			// Process hooks/filter - this is where most add-ons should hook
			// because at this point we have completed all field validation and
			// formatted the data.
			$this->fields = apply_filters( 'wpforms_process_filter', $this->fields, $entry, $form_data );

			do_action( 'wpforms_process', $this->fields, $entry, $form_data );
			do_action( "wpforms_process_{$form_id}", $this->fields, $entry, $form_data );

			$this->fields = apply_filters( 'wpforms_process_after_filter', $this->fields, $entry, $form_data );

			// One last error check - don't proceed if there are any errors
			if ( ! empty( $this->errors[$form_id] ) ) {
				if ( empty( $this->errors[$form_id]['header'] ) ) {
					$this->errors[$form_id]['header'] = __( 'Form has not been submitted, please see the errors below.', 'wpforms' );
				}
				return;
			}

			// Success - add entry to database
			$entry_id = $this->entry_save( $this->fields, $entry, $form_data['id'], $form_data );

			// Success - send email notification
			$this->entry_email( $this->fields, $entry, $form_data, $entry_id );

			// Pass completed and formatted fields in POST
			$_POST['wpforms']['complete'] = $this->fields;

			// Pass entry ID in POST
			$_POST['wpforms']['entry_id'] = $entry_id;

			// Logs entry depending on log levels set
			wpforms_log( 
				'Entry', 
				$this->fields, 
				array( 
					'type'    => array( 'entry' ), 
					'parent'  => $entry_id, 
					'form_id' => $form_data['id'],
				)
			);

			// Post-process hooks
			do_action( 'wpforms_process_complete', $this->fields, $entry, $form_data, $entry_id );
			do_action( "wpforms_process_complete_{$form_id}", $this->fields, $entry, $form_data, $entry_id );
		
		} else {

			// Logs spam entry depending on log levels set
			wpforms_log( 
				'Spam Entry', 
				array( $honeypot, $entry ), 
				array( 
					'type'    => array( 'spam' ), 
					'form_id' => $form_data['id'],
				)
			);
		}

		$this->entry_confirmation_redirect( $form_data );
	}

	/**
	 * Validate the form return hash.
	 *
	 * @since 1.0.0
	 * @param string $hash
	 * @return mixed false for invalid or form id
	 */
	public function validate_return_hash( $hash = '' ) {

		$query_args = base64_decode( $hash );
		parse_str( $query_args );

		// Verify hash matches
		if ( $hash != wp_hash( $form_id . ',' . $entry_id ) ) {
			return false;
		}

		// Get lead and verify it is attached to the form we received with it
		$entry = wpforms()->entry->get( $entry_id );
		if ( $form_id != $entry->form_id ) {
			return false;
		}

		return $form_id;
	}

	/**
	 * Redirects user to a page or URL specified in the form confirmation settings.
	 *
	 * @since 1.0.0
	 * @param array $form_data
	 * @param string $hash
	 */
	public function entry_confirmation_redirect( $form_data = '', $hash = '' ) {

		$url = '';

		if ( !empty( $hash ) ) {

			$form_id = $this->validate_return_hash( $hash );

			if ( !$form_id )
				return;

			// Get form
			$form_data = wpforms()->form->get( $form_id, array( 'content_only' => true ) );
		}

		// Redirect if needed, to either a page or URL, after form processing
		if ( !empty( $form_data['settings']['confirmation_type'] ) && $form_data['settings']['confirmation_type'] != 'message' ) {

			if ( $form_data['settings']['confirmation_type'] == 'redirect' ) {
				$url = apply_filters( 'wpforms_process_smart_tags', $form_data['settings']['confirmation_redirect'], $form_data, $this->fields, $this->entry_id );
			}

			if ( $form_data['settings']['confirmation_type'] == 'page' ) {
				$url = get_permalink( (int) $form_data['settings']['confirmation_page'] );
			}
		}

		if ( !empty( $form_data['id'] ) ) {
			$form_id = $form_data['id'];
		} else {
			return;
		}

		if ( !empty( $url ) ) {
			$url = apply_filters( 'wpforms_process_redirect_url', $url, $form_id, $this->fields );
			wp_redirect( esc_url_raw( $url ) );
			do_action( 'wpforms_process_redirect', $form_id );
			do_action( "wpforms_process_redirect_{$form_id}", $form_id );
			exit;
		}
	}

	/**
	 * Sends entry email notifications.
	 *
	 * @since 1.0.0
	 * @param array $fields
	 * @param array $entry
	 * @param array $form_data
	 */
	public function entry_email( $fields, $entry, $form_data ) {

		// Check that the form was configured for email notifcations
		if ( empty( $form_data['settings']['notification_enable'] ) || '1' != $form_data['settings']['notification_enable'] ) {
			return;
		}

		// Provide the opportunity to override via a filter
		if ( ! apply_filters( 'wpforms_entry_email', true, $fields, $entry, $form_data ) ) {
			return;
		}

		$fields = apply_filters( 'wpforms_entry_email_data', $fields, $entry, $form_data );

		// Backwards compatibility for notifications before v1.2.3
		if ( empty( $form_data['settings']['notifications'] ) ) {
			$notifications[1] = array(
				'email'          => $form_data['settings']['notification_email'],
				'subject'        => $form_data['settings']['notification_subject'],
				'sender_name'    => $form_data['settings']['notification_fromname'],
				'sender_address' => $form_data['settings']['notification_fromaddress'],
				'replyto'        => $form_data['settings']['notification_replyto'],
				'message'        => '{all_fields}',
			);
		} else {
			$notifications = $form_data['settings']['notifications'];
		}

		foreach( $notifications as $notification_id => $notification ) {

			if ( empty( $notification['email'] ) ) {
				continue;
			}

			$process_email = apply_filters( 'wpforms_entry_email_process', true, $fields, $form_data, $notification_id ); 

			if ( ! $process_email ) {
				continue;
			}

			$email  = array();

			// Setup email properties
			$email['address']        = explode( ',', apply_filters( 'wpforms_process_smart_tags', $notification['email'], $form_data, $fields, $this->entry_id ) );
			$email['address']        = array_map( 'sanitize_email', $email['address'] );
			$email['subject']        = !empty( $notification['subject'] ) ? $notification['subject'] : sprintf( __( 'New %s Entry', 'wpforms ' ), $form_data['settings']['form_title'] );
			$email['sender_address'] = !empty( $notification['sender_address'] ) ? $notification['sender_address'] : get_option( 'admin_email' );
			$email['sender_name']    = !empty( $notification['sender_name'] ) ? $notification['sender_name'] : get_bloginfo( 'name' );
			$email['replyto']        = !empty( $notification['replyto'] ) ? $notification['replyto'] : false;
			$email['message']        = !empty( $notification['message'] ) ? $notification['message'] : '{all_fields}';
			$email                   = apply_filters( 'wpforms_entry_email_atts', $email, $fields, $entry, $form_data, $notification_id );

			// Create new email
			$emails = new WPForms_WP_Emails;
			$emails->__set( 'form_data',    $form_data               );
			$emails->__set( 'fields',       $fields                  );
			$emails->__set( 'entry_id',     $this->entry_id          );
			$emails->__set( 'from_name',    $email['sender_name']    );
			$emails->__set( 'from_address', $email['sender_address'] );
			$emails->__set( 'reply_to',     $email['replyto']        );

			// Go
			foreach( $email['address'] as $address ) {
				$emails->send( trim( $address ), $email['subject'], $email['message'] );
			}	
		}		
	}

	/**
	 * Saves entry to database.
	 *
	 * @since 1.0.0
	 * @param array $fields
	 * @param array $entry
	 * @param array $form_data
	 */
	public function entry_save( $fields, $entry, $form_id, $form_data = '' ) {

		do_action( 'wpforms_process_entry_save', $fields, $entry, $form_id, $form_data );

		return $this->entry_id;
	}
}