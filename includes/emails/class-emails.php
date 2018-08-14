<?php
/**
 * Emails.
 *
 * This class handles all (notification) emails sent by WPForms.
 *
 * Heavily influceded by the great AffiliateWP plugin by Pippin Williamson.
 * https://github.com/AffiliateWP/AffiliateWP/blob/master/includes/emails/class-affwp-emails.php
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.1.3
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_WP_Emails {

	/**
	 * Holds the from address.
	 *
	 * @since 1.1.3
	 */
	private $from_address;

	/**
	 * Holds the from name.
	 *
	 * @since 1.1.3
	 */
	private $from_name;

	/**
	 * Holds the reply-to address.
	 *
	 * @since 1.1.3
	 */
	private $reply_to = false;

	/**
	 * Holds the email content type.
	 *
	 * @since 1.1.3
	 */
	private $content_type;

	/**
	 * Holds the email headers.
	 *
	 * @since 1.1.3
	 */
	private $headers;

	/**
	 * Whether to send email in HTML.
	 *
	 * @since 1.1.3
	 */
	private $html = true;

	/**
	 * The email template to use.
	 *
	 * @since 1.1.3
	 */
	private $template;

	/**
	 * Form data.
	 *
	 * @since 1.1.3
	 */
	private $form_data = '';

	/**
	 * Fields, formatted, and sanitized.
	 *
	 * @since 1.1.3
	 */
	private $fields = '';

	/**
	 * Entry ID.
	 *
	 * @since 1.2.3
	 */
	public $entry_id = '';

	/**
	 * Get things going.
	 *
	 * @since 1.1.3
	 * @return void
	 */
	public function __construct() {

		if ( 'none' === $this->get_template() ) {
			$this->html = false;
		}

		add_action( 'wpforms_email_send_before', array( $this, 'send_before' ) );
		add_action( 'wpforms_email_send_after',  array( $this, 'send_after'  ) );
	}

	/**
	 * Set a property.
	 *
	 * @since 1.1.3
	 * @return void
	 */
	public function __set( $key, $value ) {

		$this->$key = $value;
	}

	/**
	 * Get the email from name.
	 *
	 * @since 1.1.3
	 * @return string The email from name
	 */
	public function get_from_name() {

		if ( !empty( $this->from_name ) ) {	
			$this->from_name =  $this->process_tag( $this->from_name );
		} else {
			$this->from_name =  get_bloginfo( 'name' );
		}

		return apply_filters( 'wpforms_email_from_name', wp_specialchars_decode( $this->from_name ), $this );
	}

	/**
	 * Get the email from address.
	 *
	 * @since 1.1.3
	 * @return string The email from address
	 */
	public function get_from_address() {

		if ( !empty( $this->from_address ) ) {
			$this->from_address = $this->process_tag( $this->from_address );
		} else {
			$this->from_address = get_option( 'admin_email' );
		}

		return apply_filters( 'wpforms_email_from_address', $this->from_address, $this );
	}

	/**
	 * Get the email reply-to.
	 *
	 * @since 1.1.3
	 * @return string The email reply-to address
	 */
	public function get_reply_to() {

		if ( !empty( $this->reply_to ) ) {
			$this->reply_to = $this->process_tag( $this->reply_to );
		}

		return apply_filters( 'wpforms_email_reply_to', $this->reply_to, $this );
	}

	/**
	 * Get the email content type.
	 *
	 * @since 1.1.3
	 * @return string The email content type
	 */
	public function get_content_type() {

		if ( ! $this->content_type && $this->html ) {
			$this->content_type = apply_filters( 'wpforms_email_default_content_type', 'text/html', $this );
		} elseif ( ! $this->html ) {
			$this->content_type = 'text/plain';
		}

		return apply_filters( 'wpforms_email_content_type', $this->content_type, $this );
	}

	/**
	 * Get the email headers.
	 *
	 * @since 1.1.3
	 * @return string The email headers
	 */
	public function get_headers() {

		if ( ! $this->headers ) {
			$this->headers  = "From: {$this->get_from_name()} <{$this->get_from_address()}>\r\n";
			if ( $this->get_reply_to() ) {
				$this->headers .= "Reply-To: {$this->get_reply_to()}\r\n";
			}
			$this->headers .= "Content-Type: {$this->get_content_type()}; charset=utf-8\r\n";
		}

		return apply_filters( 'wpforms_email_headers', $this->headers, $this );
	}

	/**
	 * Build the email
	 *
	 * @since 1.1.3
	 * @param string $message The email message
	 * @return string
	 */
	public function build_email( $message ) {

		if ( false === $this->html ) {
			$message = $this->process_tag( $message );
			$message = str_replace( '{all_fields}', $this->process_all_fields( false ), $message );
			return apply_filters( 'wpforms_email_message', wp_strip_all_tags( $message ), $this );
		}

		ob_start();

		$this->get_template_part( 'header', $this->get_template(), true );

		// Hooks into the email header
		do_action( 'wpforms_email_header', $this );

		$this->get_template_part( 'body', $this->get_template(), true );

		// Hooks into the email body
		do_action( 'wpforms_email_body', $this );

		$this->get_template_part( 'footer', $this->get_template(), true );

		// Hooks into the email footer
		do_action( 'wpforms_email_footer', $this );

		$message = $this->process_tag( $message, false );
		$message = nl2br( $message );

		$body	 = ob_get_clean();
		$message = str_replace( '{email}', $message, $body );
		$message = str_replace( '{all_fields}', $this->process_all_fields( true ), $message );

		return apply_filters( 'wpforms_email_message', $message, $this );
	}

	/**
	 * Send the email
	 *
	 * @since 1.1.3
	 * @param string $to The To address
	 * @param string $subject The subject line of the email
	 * @param string $message The body of the email
	 * @param string|array $attachments Attachments to the email
	 */
	public function send( $to, $subject, $message, $attachments = array() ) {

		if ( ! did_action( 'init' ) && ! did_action( 'admin_init' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'You cannot send emails with WPForms_WP_Emails until init/admin_init has been reached', 'wpforms' ), null );
			return false;
		}

		// Don't send anything if emails have been disabled
		if ( $this->is_email_disabled() ) {
			return false;
		}

		// Don't send if email address is invalid
		if ( ! is_email( $to ) ) {
			return false;
		}

		// Hooks before email is sent
		do_action( 'wpforms_email_send_before', $this );

		$message     = $this->build_email( $message );
		$attachments = apply_filters( 'wpforms_email_attachments', $attachments, $this );
		$subject     = wp_specialchars_decode( $this->process_tag( $subject ) );

		// Let's do this
		$sent = wp_mail( $to, $subject, $message, $this->get_headers(), $attachments );

		// Hooks after the email is sent
		do_action( 'wpforms_email_send_after', $this );

		return $sent;
	}

	/**
	 * Add filters/actions before the email is sent.
	 * 
	 * @since 1.1.3
	 */
	public function send_before() {

		add_filter( 'wp_mail_from',         array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name',    array( $this, 'get_from_name'    ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

	/**
	 * Remove filters/actions after the email is sent.
	 *
	 * @since 1.1.3
	 */
	public function send_after() {

		remove_filter( 'wp_mail_from',         array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name',    array( $this, 'get_from_name'    ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

	/**
	 * Converts text formatted HTML. This is primarily for turning line breaks 
	 * into <p> and <br/> tags.
	 *
	 * @since 1.1.3
	 * @param string $message
	 * @return string
	 */
	public function text_to_html( $message ) {

		if ( 'text/html' === $this->content_type || true === $this->html ) {
			$message = wpautop( $message );
		}

		return $message;
	}

	/**
	 * Processes a smart tag.
	 *
	 * @since 1.1.3
	 * @param string $string
	 * @param bool $sanitize
	 * @return string
	 */
	function process_tag( $string = '', $santiize = true ) {

		$tag = apply_filters( 'wpforms_process_smart_tags', $string, $this->form_data, $this->fields, $this->entry_id );

		$tag = stripslashes( wp_specialchars_decode( $tag ) );

		if ( $santiize ) {
			$tag = sanitize_text_field( $tag );
		}

		return $tag;
	}

	/**
	 * Process the all fields smart tag if pressent.
	 *
	 * @since 1.1.3
	 */
	function process_all_fields( $html = true ) {

		if ( empty( $this->fields ) )
			return;

		$message = '';

		if ( $html ) {

			// HTML emails ---------------------------------------------------//
			ob_start();

			// Hooks into the email field
			do_action( 'wpforms_email_field', $this );

			$this->get_template_part( 'field', $this->get_template(), true );

			$field_template = ob_get_clean();

			$x = 1;
			foreach( $this->fields as $field ) {

				if ( !empty( $field['value'] ) && false !== $field['value'] ) {

					$field_name = !empty( $field['name'] ) ? $field['name'] : __( 'Field ID #', 'wpforms' ) . absint( $field['id'] );
					
					$field_item = $field_template;
					if ( $x === 1 ) {
						$field_item = str_replace( 'border-top:1px solid #dddddd;', '', $field_item );
					}
					$field_item  = str_replace( '{field_name}', $field_name, $field_item );
					$field_value = apply_filters( 'wpforms_html_field_value', stripslashes( wp_specialchars_decode( $field['value'] ) ), $field, $this->form_data );
					$field_item  = str_replace( '{field_value}', $field_value, $field_item );
					
					$message .= wpautop( $field_item );
					$x++;
				}
			}
			
		} else {

			// Plain Text emails ---------------------------------------------//
			foreach ( $this->fields as $field ) {

				if ( !empty( $field['value'] ) && false !== $field['value'] ) {

					$field_name = !empty( $field['name'] ) ? $field['name'] : __( 'Field ID #', 'wpforms' ) . absint( $field['id'] );

					$message .= "--- " . wp_specialchars_decode( $field_name ) . " ---\r\n";
					$message .= stripslashes( wp_specialchars_decode( $field['value'] ) ) . "\r\n\r\n";
				}
			}
		}

		return $message;
	}

	/**
	 * Email kill switch if needed.
	 *
	 * @since 1.1.3
	 * @return bool
	 */
	public function is_email_disabled() {

		$disabled = (bool) apply_filters( 'wpforms_disable_all_emails', false, $this );

		return $disabled;
	}

	/**
	 * Get the enabled email template
	 *
	 * @since 1.1.3
	 * @return string|null
	 */
	public function get_template() {

		if ( ! $this->template ) {
			$this->template = wpforms_setting( 'email-template', 'default' );
		}

		return apply_filters( 'wpforms_email_template', $this->template );
	}

	/**
	 * Retrieves a template part. Taken from bbPress.
	 *
	 * @since 1.1.3
	 * @param string $slug
	 * @param string $name Optional. Default null
	 * @param bool $load
	 * @return string
	 */
	public function get_template_part( $slug, $name = null, $load = true ) {

		// Setup possible parts
		$templates = array();
		if ( isset( $name ) )
			$templates[] = $slug . '-' . $name . '.php';
		$templates[] = $slug . '.php';

		// Return the part that is found
		return $this->locate_template( $templates, $load, false );
	}

	/**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
	 * inherit from a parent theme can just overload one file. If the template is
	 * not found in either of those, it looks in the theme-compat folder last.
	 *
	 * Taken from bbPress
	 *
	 * @since 1.1.3
	 * @param string|array $template_names Template file(s) to search for, in order.
	 * @param bool $load If true the template file will be loaded if it is found.
	 * @param bool $require_once Whether to require_once or require. Default true.
	 *   Has no effect if $load is false.
	 * @return string The template filename if one is located.
	 */
	public function locate_template( $template_names, $load = false, $require_once = true ) {

		// No file found yet
		$located = false;

		// Try to find a template file
		foreach ( (array) $template_names as $template_name ) {

			// Continue if template is empty
			if ( empty( $template_name ) )
				continue;

			// Trim off any slashes from the template name
			$template_name = ltrim( $template_name, '/' );

			// try locating this template file by looping through the template paths
			foreach( $this->get_theme_template_paths() as $template_path ) {
				if( file_exists( $template_path . $template_name ) ) {
					$located = $template_path . $template_name;
					break;
				}
			}
		}

		if ( ( true == $load ) && ! empty( $located ) )
			load_template( $located, $require_once );

		return $located;
	}

	/**
	 * Returns a list of paths to check for template locations
	 *
	 * @since 1.1.3
	 * @return mixed|void
	 */
	public function get_theme_template_paths() {

		$template_dir = 'wpforms-email';

		$file_paths = array(
			1   => trailingslashit( get_stylesheet_directory() ) . $template_dir,
			10  => trailingslashit( get_template_directory() ) . $template_dir,
			100 => WPFORMS_PLUGIN_DIR . 'includes/emails/templates'
		);

		$file_paths = apply_filters( 'wpforms_email_template_paths', $file_paths );

		// sort the file paths based on priority
		ksort( $file_paths, SORT_NUMERIC );

		return array_map( 'trailingslashit', $file_paths );
	}
}