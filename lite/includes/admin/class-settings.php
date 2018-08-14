<?php
/**
 * Settings class.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Settings {

	/**
	 * Holds the plugin settings
	 *
	 * @since 1.0.0
	 */
	protected $options;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Maybe load settings page
		add_action( 'admin_init', array( $this, 'init' ) );

		// Plugin settings link
		add_filter( 'plugin_action_links_' . plugin_basename( WPFORMS_PLUGIN_DIR . 'wpforms.php' ), array( $this, 'settings_link' ) );
	}

	/**
	 * Get the value of a specific setting.
	 *
	 * @since 1.0.0
	 * @return mixed
	*/
	public function get( $key, $default = false, $option = 'wpforms_settings' ) {

		if ( 'wpforms_settings' == $option && !empty( $this->options ) ) {
			$options = $this->options;
		} else {
			$options = get_option( $option, false );
		}

		$value = ! empty( $options[ $key ] ) ? $options[ $key ] : $default;
		return $value;
	}

	/**
	 * Determing if the user is viewing the settings page, if so, party on.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		
		// Check what page we are on
		$page = isset( $_GET['page'] ) ? $_GET['page'] : '';

		// Only load if we are actually on the settings page
		if ( $page == 'wpforms-settings' ) {

			// Retrieve settings
			$this->options = get_option( 'wpforms_settings', array() );

			add_action( 'wpforms_tab_settings_general',    array( $this, 'settings_page_tab_general'    ) );
			add_action( 'wpforms_tab_settings_system',     array( $this, 'settings_page_tab_system'     ) );
			add_action( 'admin_enqueue_scripts',           array( $this, 'enqueues'                     ) );
			add_action( 'wpforms_admin_page',              array( $this, 'output'                       ) );

			// Hook for add-ons
			do_action( 'wpforms_settings_init' );
		}
	}

	/**
	 * Enqueue assets for the settings page.
	 *
	 * @since 1.0.0
	 */
	public function enqueues() {

		wp_enqueue_media();

		// CSS
		wp_enqueue_style( 
			'font-awesome', 
			WPFORMS_PLUGIN_URL . 'assets/css/font-awesome.min.css', 
			null, 
			'4.4.0'
		);

		wp_enqueue_style( 
			'wpforms-settings',
			WPFORMS_PLUGIN_URL . 'assets/css/admin-settings.css', 
			null,
			WPFORMS_VERSION
		);

		wp_enqueue_style( 
			'minicolors', 
			WPFORMS_PLUGIN_URL . 'assets/css/jquery.minicolors.css', 
			null, 
			'2.2.3'
		);

		// JS
		wp_enqueue_script( 
			'minicolors', 
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.minicolors.min.js', 
			array( 'jquery' ), 
			'2.2.3', 
			false
		);

		wp_enqueue_script( 
			'wpforms-settings', 
			WPFORMS_PLUGIN_URL . 'assets/js/admin-settings.js',
			array( 'jquery', 'jquery-ui-tabs' ), 
			WPFORMS_VERSION, 
			false
		);
		wp_localize_script(
			'wpforms-settings',
			'wpforms_settings',
			array(
				'ajax_url'               => admin_url( 'admin-ajax.php' ),
				'nonce'                  => wp_create_nonce( 'wpforms-settings' ),
				'saving'                 => __( 'Saving ...', 'wpforms' ),
				'provider_disconnect'    => __( 'Are you sure you want to disconnect this account?', 'wpforms' ),
				'upload_title'           => __( 'Upload or Choose Your Image', 'wpforms' ),
				'upload_button'          => __( 'Use Image', 'wpforms' ),
			)
		);
	
		// Hook for add-ons
		do_action( 'wpforms_settings_enqueue' );
	}

	/**
	 * Handles generating the appropriate tabs and setting sections.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function setting_page_tabs() {

		// Define our base tabs
		$tabs = array(
			'general'   => __( 'General', 'wpforms' ),
			'system'    => __( 'System Information', 'wpforms' ),
		);

		// Allow for addons and extensions to add additional tabs
		$tabs = apply_filters( 'wpform_settings_tabs', $tabs );

		return $tabs;
	}

	/**
	 * Build the output for General tab on the settings page and check for save.
	 *
	 * @since 1.0.0
	 */
	public function settings_page_tab_general() {

		// Check for save, if found let's dance
		if ( !empty( $_POST['wpforms-settings-general-nonce'] ) ) {

			// Do we have a valid nonce and permission?
			if ( ! wp_verify_nonce( $_POST['wpforms-settings-general-nonce'], 'wpforms-settings-general-nonce' ) || !current_user_can( apply_filters( 'wpforms_manage_cap', 'manage_options' ) ) ) {

				// No funny business
				printf( '<div class="error below-h2"><p>%s</p></div>', __( 'Settings check failed.', 'wpforms' ) );

			} else {

				// Save General Settings
				if ( isset( $_POST['submit-general'] ) ) {

					// Prep and sanatize settings for save
					$this->options['email-template']         = !empty( $_POST['email-template'] ) ? esc_attr( $_POST['email-template'] ) : 'default';
					$this->options['email-header-image']     = !empty( $_POST['email-header-image'] ) ? esc_url_raw( $_POST['email-header-image'] ) : '';
					$this->options['email-background-color'] = !empty( $_POST['email-background-color'] ) ? wpforms_sanitize_hex_color( $_POST['email-background-color'] ) : '#e9eaec';
					$this->options['disable-css']            = !empty( $_POST['disable-css'] ) ? intval( $_POST['disable-css'] ) : '1';
					$this->options['global-assets']          = !empty( $_POST['global-assets'] ) ? '1' : false;
					$this->options['recaptcha-site-key']     = !empty( $_POST['recaptcha-site-key'] ) ? esc_html( $_POST['recaptcha-site-key'] ) : '';
					$this->options['recaptcha-secret-key']   = !empty( $_POST['recaptcha-secret-key'] ) ? esc_html( $_POST['recaptcha-secret-key'] ) : '';
					$this->options = apply_filters( 'wpforms_settings_save', $this->options, $_POST, 'general' );

					// Update settings in DB
					update_option( 'wpforms_settings' , $this->options );

					printf( '<div class="updated below-h2"><p>%s</p></div>', __( 'General settings updated.', 'wpforms' ) );
				}
			}
		}
		?>

		<div id="wpforms-settings-general">
			
			<form method="post">

				<?php wp_nonce_field( 'wpforms-settings-general-nonce', 'wpforms-settings-general-nonce' ); ?>
				
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="wpforms-settings-general-css"><?php _e( 'Include Form Styling', 'wpforms' ); ?></label>
							</th>
							<td>
								<select name="disable-css" id="wpforms-settings-general-css">
									<option value="1" <?php selected( '1', $this->get( 'disable-css' ) ); ?>><?php _e( 'Base and form theme styling', 'wpforms' ); ?></option>
									<option value="2" <?php selected( '2', $this->get( 'disable-css' ) ); ?>><?php _e( 'Base styling only', 'wpforms' ); ?></option>
									<option value="3" <?php selected( '3', $this->get( 'disable-css' ) ); ?>><?php _e( 'None', 'wpforms' ); ?></option>
								</select>
								<p class="description"><?php _e( 'Determines which CSS files to load for the site.', 'wpforms' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpforms-settings-general-global-assets"><?php _e( 'Load Assets Globally', 'wpforms' ); ?></label>
							</th>
							<td>
								<input type="checkbox" name="global-assets" id="wpforms-settings-general-global-assets" value="1" <?php checked( '1', $this->get( 'global-assets' ) ); ?>>
								<label for="wpforms-settings-general-global-assets"><?php _e( 'Check this if you would like to load WPForms assets site-wide. Only check if your site is having compatibility issues or instructed to by support.', 'wpforms_paypals' ); ?></label>
							</td>
						</tr>
						<tr>
							<td class="section" colspan="2">
								<hr>
								<h4><?php _e( 'Email', 'wpforms' ); ?></h4>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpforms-settings-general-email-template"><?php _e( 'Email Template', 'wpforms' ); ?></label>
							</th>
							<td>
								<select name="email-template" id="wpforms-settings-general-email-template">
									<option value="default" <?php selected( 'default', $this->get( 'email-template' ) ); ?>><?php _e( 'Default HTML template', 'wpforms' ); ?></option>
									<option value="none" <?php selected( 'none', $this->get( 'email-template' ) ); ?>><?php _e( 'Plain Text', 'wpforms' ); ?></option>
								</select>
								<p class="description"><?php _e( 'Determines how email notifications will be formatted.', 'wpforms' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpforms-settings-general-email-header-image"><?php _e( 'Email Header Image ', 'wpforms' ); ?></label>
							</th>
							<td>
								<label for="wpforms-settings-general-email-header-image" class="wpforms-settings-upload-image-display">
									<?php 
									$email_header = $this->get( 'email-header-image' );
									if ( $email_header ) {
										echo '<img src="' . esc_url_raw( $email_header ) . '">';
									}
									?>
								</label>
								<input type="text" name="email-header-image" id="wpforms-settings-email-header-image" class="wpforms-settings-upload-image-value" value="<?php echo esc_url_raw( $this->get( 'email-header-image' ) ); ?>" />
								<a href="#" class="button button-secondary wpforms-settings-upload-image"><?php _e( 'Upload Image', 'wpforms' ); ?></a>
								<p class="description">
									<?php _e( 'Upload or choose a logo to be displayed at the top of email notifications.', 'wpforms' ); ?><br>
									<?php _e( 'Recommended size is 300x100 or smaller for best support on all devices.', 'wpforms' ); ?>
								</p>
							</td>          
						</tr>
						<tr>
							<th scope="row">
								<label for="wpforms-settings-general-email-background-color"><?php _e( 'Email Background Color', 'wpforms' ); ?></label>
							</th>
							<td>
								<input type="text" name="email-background-color" value="<?php echo esc_attr( $this->get( 'email-background-color', '#e9eaec' ) ); ?>" id="wpforms-settings-general-email-background-color" class="wpforms-color-picker">
								<p class="description"><?php _e( 'Customize the background color of the HTML email template.', 'wpforms' ); ?></p>
							</td>
						</tr>
						<tr>
							<td class="section" colspan="2">
								<hr>
								<h4><?php _e( 'reCAPTCHA', 'wpforms' ); ?></h4>
								<p><?php _e( 'reCAPTCHA is a free anti-spam service from Google. Its helps protect your website from spam and abuse while letting real people pass through with ease. <a href="http://www.google.com/recaptcha/intro/index.html" target="_blank">Visit reCAPTCHA</a> to learn more and sign up for a free account or <a href="https://wpforms.com/docs/setup-captcha-wpforms/" target="_blank">read our walk through</a> for step-by-step directions.', 'wpforms' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpforms-settings-general-recaptcha-site-key"><?php _e( 'reCAPTCHA Site key', 'wpforms' ); ?></label>
							</th>
							<td>
								<input type="text" name="recaptcha-site-key" value="<?php echo esc_attr( $this->get( 'recaptcha-site-key' ) ); ?>" id="wpforms-settings-general-recaptcha-site-key">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpforms-settings-general-recaptcha-secret-key"><?php _e( 'reCAPTCHA Secret key', 'wpforms' ); ?></label>
							</th>
							<td>
								<input type="text" name="recaptcha-secret-key" value="<?php echo esc_attr( $this->get( 'recaptcha-secret-key' ) ); ?>" id="wpforms-settings-general-recaptcha-secret-key">
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Save General Settings', 'wpforms'), 'primary', 'submit-general' ); ?>

			</form>
	
		</div>
		<?php
	}

	/**
	 * Build the output for System Info (system) tab on the settings page.
	 *
	 * @since 1.2.3
	 */
	public function settings_page_tab_system() {
		
		?>
		<div id="wpforms-settings-system">
				
			<textarea readonly="readonly" class="system-info-textarea"><?php echo $this->get_system_info(); ?></textarea>
			
		</div>
		<?php
	}

	/**
	 * Build the output for the plugin settings page.
	 *
	 * @since 1.0.0
	 */
	public function output() {

		?>
		<div id="wpforms-settings" class="wrap">

			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

			<div class="wpforms-circle-loader">
				<div class="wpforms-circle-1 wpforms-circle"></div>
				<div class="wpforms-circle-2 wpforms-circle"></div>
				<div class="wpforms-circle-3 wpforms-circle"></div>
				<div class="wpforms-circle-4 wpforms-circle"></div>
				<div class="wpforms-circle-5 wpforms-circle"></div>
				<div class="wpforms-circle-6 wpforms-circle"></div>
				<div class="wpforms-circle-7 wpforms-circle"></div>
				<div class="wpforms-circle-8 wpforms-circle"></div>
				<div class="wpforms-circle-9 wpforms-circle"></div>
				<div class="wpforms-circle-10 wpforms-circle"></div>
				<div class="wpforms-circle-11 wpforms-circle"></div>
				<div class="wpforms-circle-12 wpforms-circle"></div>
			</div>
			
			<div id="wpforms-tabs" class="wpforms-clear">

				<!-- Output tabs navigation -->
				<h2 id="wpforms-tabs-nav" class="wpforms-clear nav-tab-wrapper">
					<?php $i = 0; foreach ( (array) $this->setting_page_tabs() as $id => $title ) : $class = 0 === $i ? 'wpforms-active nav-tab-active' : ''; ?>
						<a class="nav-tab <?php echo $class; ?>" href="#wpforms-tab-<?php echo $id; ?>" title="<?php echo $title; ?>"><?php echo $title; ?></a>
					<?php $i++; endforeach; ?>
				</h2>

				<!-- Output tab sections -->
				<?php $i = 0; foreach ( (array) $this->setting_page_tabs() as $id => $title ) : $class = 0 === $i ? 'wpforms-active' : ''; ?>
				<div id="wpforms-tab-<?php echo $id; ?>" class="wpforms-tab wpforms-clear <?php echo $class; ?>">
					<?php do_action( 'wpforms_tab_settings_' . $id ); ?>
				</div>
				<?php $i++; endforeach; ?>

			</div>

		</div>
		<?php
	}

	/**
	 * Add settings link to the Plugins page.
	 *
	 * @since 1.0.0
	 * @param array $links
	 * @return array $links
	 */
	public function settings_link( $links ) {

		$setting_link = sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'page' => 'wpforms-settings' ), admin_url( 'admin.php' ) ), __( 'Settings', 'wpforms' ) );
		array_unshift( $links, $setting_link );

		return $links;
	}

	/**
	 * Get system information.
	 *
	 * Based on a function from Easy Digital Downloads by Pippin Williamson
	 *
	 * @link https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/admin/tools.php#L470
	 * @since 1.2.3
	 * @return string
	 */
	public function get_system_info() {

		global $wpdb;

		// Get theme info
		$theme_data = wp_get_theme();
		$theme      = $theme_data->Name . ' ' . $theme_data->Version;

		$return  = '### Begin System Info ###' . "\n\n";

		// WPForms info
		$activated = get_option( 'wpforms_activated', array() );
		$return .= '-- WPForms Info' . "\n\n";
		if ( !empty( $activated['pro'] ) ) {
			$date    = $activated['pro'] + ( get_option( 'gmt_offset' ) * 3600 );
			$return .= 'Pro:                      ' . date_i18n( __( 'M j, Y @ g:ia' ), $date ) . "\n";
		}
		if ( !empty( $activated['lite'] ) ) {
			$date    = $activated['lite'] + ( get_option( 'gmt_offset' ) * 3600 );
			$return .= 'Lite:                     ' . date_i18n( __( 'M j, Y @ g:ia' ), $date ) . "\n";
		}

		// Now the basics...
		$return .= '-- Site Info' . "\n\n";
		$return .= 'Site URL:                 ' . site_url() . "\n";
		$return .= 'Home URL:                 ' . home_url() . "\n";
		$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

		// WordPress configuration
		$return .= "\n" . '-- WordPress Configuration' . "\n\n";
		$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
		$return .= 'Language:                 ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "\n";
		$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
		$return .= 'Active Theme:             ' . $theme . "\n";
		$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

		// Only show page specs if frontpage is set to 'page'
		if( get_option( 'show_on_front' ) == 'page' ) {
			$front_page_id = get_option( 'page_on_front' );
			$blog_page_id = get_option( 'page_for_posts' );
			$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
			$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
		}

		$return .= 'ABSPATH:                  ' . ABSPATH . "\n";

		// Make sure wp_remote_post() is working
		/*
		$request['cmd'] = '_notify-validate';
		$params = array(
			'sslverify'     => false,
			'timeout'       => 60,
			'user-agent'    => 'WPForms/' . WPFORMS_VERSION,
			'body'          => $request
		);

		$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

		if( !is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$WP_REMOTE_POST = 'wp_remote_post() works';
		} else {
			$WP_REMOTE_POST = 'wp_remote_post() does not work';
		}
		$return .= 'Remote Post:              ' . $WP_REMOTE_POST . "\n";
		*/

		$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
		$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'WPFORMS_DEBUG:            ' . ( defined( 'WPFORMS_DEBUG' ) ? WPFORMS_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
		$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

		// @todo WPForms configuration/specific details
		$return .= "\n" . '-- WordPress Uploads/Constants' . "\n\n";
		$return .= 'WP_CONTENT_DIR:           ' . ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR ? WP_CONTENT_DIR : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'WP_CONTENT_URL:           ' . ( defined( 'WP_CONTENT_URL' ) ? WP_CONTENT_URL ? WP_CONTENT_URL : 'Disabled' : 'Not set' ) . "\n";
		$return .= 'UPLOADS:                  ' . ( defined( 'UPLOADS' ) ? UPLOADS ? UPLOADS : 'Disabled' : 'Not set' ) . "\n";
		$uploads_dir = wp_upload_dir();
		$return .= 'wp_uploads_dir() path:    ' . $uploads_dir['path']. "\n";
		$return .= 'wp_uploads_dir() url:     ' . $uploads_dir['url']. "\n";
		$return .= 'wp_uploads_dir() basedir: ' . $uploads_dir['basedir']. "\n";
		$return .= 'wp_uploads_dir() baseurl: ' . $uploads_dir['baseurl']. "\n";


		// Get plugins that have an update
		$updates = get_plugin_updates();

		// Must-use plugins
		// NOTE: MU plugins can't show updates!
		$muplugins = get_mu_plugins();
		if( count( $muplugins ) > 0 && !empty( $muplugins ) ) {
			$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

			foreach( $muplugins as $plugin => $plugin_data ) {
				$return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
			}
		}

		// WordPress active plugins
		$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

		$plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );

		foreach( $plugins as $plugin_path => $plugin ) {
			if( !in_array( $plugin_path, $active_plugins ) )
				continue;

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		// WordPress inactive plugins
		$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

		foreach( $plugins as $plugin_path => $plugin ) {
			if( in_array( $plugin_path, $active_plugins ) )
				continue;

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
		}

		if( is_multisite() ) {
			// WordPress Multisite active plugins
			$return .= "\n" . '-- Network Active Plugins' . "\n\n";

			$plugins = wp_get_active_network_plugins();
			$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

			foreach( $plugins as $plugin_path ) {
				$plugin_base = plugin_basename( $plugin_path );

				if( !array_key_exists( $plugin_base, $active_plugins ) )
					continue;

				$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
				$plugin  = get_plugin_data( $plugin_path );
				$return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
			}
		}

		// Server configuration (really just versioning)
		$return .= "\n" . '-- Webserver Configuration' . "\n\n";
		$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
		$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
		$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

		// PHP configs... now we're getting to the important stuff
		$return .= "\n" . '-- PHP Configuration' . "\n\n";
		//$return .= 'Safe Mode:                ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "\n" );
		$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
		$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
		$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
		$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
		$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
		$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

		// PHP extensions and such
		$return .= "\n" . '-- PHP Extensions' . "\n\n";
		$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
		$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
		$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

		// Session stuff
		$return .= "\n" . '-- Session Configuration' . "\n\n";
		$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

		// The rest of this is only relevant is session is enabled
		if( isset( $_SESSION ) ) {
			$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
			$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
			$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
			$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
			$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
		}

		$return .= "\n" . '### End System Info ###';

		return $return;
	}
}
new WPForms_Settings;