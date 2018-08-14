<?php
/**
 * WPForms Lite. Load Lite specific features/functionality.
 *
 * @since 1.2.0
 * @package WPForms
 */
class WPForms_Lite {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.2.x
	 */
	public function __construct() {

		$this->includes();

		add_action( 'wpforms_form_settings_notifications', array( $this, 'form_settings_notifications' ),  8, 1 );
		add_action( 'wpforms_setup_panel_after',           array( $this, 'form_templates'              )        );
		add_filter( 'wpforms_builder_fields_buttons',      array( $this, 'form_fields'                 ),    20 );
		add_action( 'wpforms_builder_panel_buttons',       array( $this, 'form_panels'                 ),    20 );
		add_action( 'wpforms_builder_enqueues_before',     array( $this, 'builder_enqueues'            )        );
		add_action( 'wpforms_admin_page',                  array( $this, 'entries_page'                )        );
		add_action( 'admin_enqueue_scripts',               array( $this, 'addon_page_enqueues'         )        );
		add_action( 'wpforms_admin_page',                  array( $this, 'addons_page'                 )        );
	}

	/**
	 * Include files.
	 *
	 * @since 1.0.0
	 */
	private function includes() {

		if ( is_admin() ) {
			require_once WPFORMS_PLUGIN_DIR . 'lite/includes/admin/class-settings.php';
		}
	}

	/**
	 * Form notification settings, supports multiple notifications.
	 *
	 * @since 1.2.3
	 * @param object $settings
	 */
	public function form_settings_notifications( $settings ) {

		// Fetch next ID and handle backwards compatibility
		if ( empty( $settings->form_data['settings']['notifications'] ) ) {
			$settings->form_data['settings']['notifications'][1]['email']          = !empty( $settings->form_data['settings']['notification_email'] ) ? $settings->form_data['settings']['notification_email'] : '{admin_email}';
			$settings->form_data['settings']['notifications'][1]['subject']        = !empty( $settings->form_data['settings']['notification_subject'] ) ? $settings->form_data['settings']['notification_subject'] : sprintf( __( 'New %s Entry', 'wpforms ' ), $settings->form->post_title );
			$settings->form_data['settings']['notifications'][1]['sender_name']    = !empty( $settings->form_data['settings']['notification_fromname'] ) ? $settings->form_data['settings']['notification_fromname'] : get_bloginfo( 'name' );
			$settings->form_data['settings']['notifications'][1]['sender_address'] = !empty( $settings->form_data['settings']['notification_fromaddress'] ) ? $settings->form_data['settings']['notification_fromaddress'] : '{admin_email}';
			$settings->form_data['settings']['notifications'][1]['replyto']        = !empty( $settings->form_data['settings']['notification_replyto'] ) ? $settings->form_data['settings']['notification_replyto'] : '';
		}
		$id = 1;

		echo '<div class="wpforms-panel-content-section-title">';
			_e( 'Notifications', 'wpforms' );
		echo '</div>';

		echo '<p class="wpforms-alert wpforms-alert-info">Want multiple notifications with smart conditional logic?<br><a href="' . $this->upgrade_link() . 'target="_blank"><strong>Upgrade to PRO</strong></a> to unlock it and more awesome features.</p>';
		
		wpforms_panel_field(
			'select',
			'settings',
			'notification_enable',
			$settings->form_data,
			__( 'Notifications', 'wpforms' ),
			array(
				'default' => '1',
				'options' => array(
					'1' => __( 'On', 'wpforms' ),
					'0' => __( 'Off', 'wpforms' ),
				),
			)
		);

		echo '<div class="wpforms-notification">';

			echo '<div class="wpforms-notification-header">';
				echo '<span>' . __( 'Default Notification', 'wpforms' ) . '</span>';
			echo '</div>';

			wpforms_panel_field(
				'text',
				'notifications',
				'email',
				$settings->form_data,
				__( 'Send To Email Address', 'wpforms' ),
				array( 
					'default'    => '{admin_email}',
					'tooltip'    => __( 'Enter the email address to receive form entry notifications. For multiple notifications, separate email addresses with a comma.', 'wpforms' ),
					'smarttags'  => array(
						'type'   => 'fields',
						'fields' => 'name,email,text',
					),
					'parent'     => 'settings',
					'subsection' => $id,
					'class'      => 'email-recipient',
				)
			);
			wpforms_panel_field(
				'text',
				'notifications',
				'subject',
				$settings->form_data,
				__( 'Email Subject', 'wpforms' ),
				array( 
					'default'    => __( 'New Entry: ' , 'wpforms' ) . $settings->form->post_title,
					'smarttags'  => array(
						'type'   => 'fields',
						'fields' => 'name,email,text',
					),
					'parent'     => 'settings',
					'subsection' => $id
				)
			);
			wpforms_panel_field(
				'text',
				'notifications',
				'sender_name',
				$settings->form_data,
				__( 'From Name', 'wpforms' ),
				array( 
					'default'    => sanitize_text_field( get_option( 'blogname' ) ),
					'smarttags'  => array(
						'type'   => 'fields',
						'fields' => 'name,email,text',
					),
					'parent'     => 'settings',
					'subsection' => $id
				)
			);
			wpforms_panel_field(
				'text',
				'notifications',
				'sender_address',
				$settings->form_data,
				__( 'From Email', 'wpforms' ),
				array( 
					'default'    => '{admin_email}',
					'smarttags'  => array(
						'type'   => 'fields',
						'fields' => 'name,email,text',
					),
					'parent'     => 'settings',
					'subsection' => $id
				)
			);
			wpforms_panel_field(
				'text',
				'notifications',
				'replyto',
				$settings->form_data,
				__( 'Reply-To', 'wpforms' ),
				array( 
					'smarttags'  => array(
						'type'   => 'fields',
						'fields' => 'name,email,text',
					),
					'parent'     => 'settings',
					'subsection' => $id
				)
			);
			wpforms_panel_field(
				'textarea',
				'notifications',
				'message',
				$settings->form_data,
				__( 'Message', 'wpforms' ),
				array(
					'rows'       => 6,
					'default'    => '{all_fields}',
					'smarttags'  => array(
						'type'   => 'all'
					),
					'parent'     => 'settings',
					'subsection' => $id,
					'class'      => 'email-msg',
					'after'      => '<p class="note">' . __( 'To display all form fields, use the <code>{all_fields}</code> Smart Tag.', 'wpforms' ) . '</p>'
				)
			);

		echo '</div>';
	}

	/**
	 * Provide upgrade URL.
	 *
	 * @since 1.2.0
	 */
	public function upgrade_link() {

		// Check if there's a constant.
		$shareasale_id = '';
		if ( defined( 'WPFORMS_SHAREASALE_ID' ) ) {
			$shareasale_id = WPFORMS_SHAREASALE_ID;
		}

		// If there's no constant, check if there's an option.
		if ( empty( $shareasale_id ) ) {
			$shareasale_id = get_option( 'wpforms_shareasale_id', '' );
		}

		// Whether we have an ID or not, filter the ID.
		$shareasale_id = apply_filters( 'wpforms_shareasale_id', $shareasale_id );

		// If at this point we still don't have an ID, we really don't have one!
		// Just return the standard upgrade URL.
		if ( empty( $shareasale_id ) ) {
			return 'https://wpforms.com/lite-upgrade/?utm_source=WordPress&amp;utm_medium=link&amp;utm_campaign=liteplugin';
		}

		// If here, we have a ShareASale ID
		// Return ShareASale URL with redirect.
		return 'http://www.shareasale.com/r.cfm?B=837827&U=' . $shareasale_id . '&M=64312&urllink=';
	}

	/**
	 * Display/register additional templates available in the Pro version.
	 *
	 * @since 1.0.6
	 */
	public function form_templates() {

		$templates = array(
			array(
				'name'        => 'Request A Quote Form',
				'slug'        => 'request-quote',
				'description' => 'Start collecting leads with this pre-made Request a quote form. You can add and remove fields as needed.',
			),
			array(
				'name'        => 'Donation Form',
				'slug'        => 'donation',
				'description' => 'Start collecting donation payments on your website with this ready-made Donation form. You can add and remove fields as needed.',
			),
			array(
				'name'        => 'Billing / Order Form',
				'slug'        => 'order',
				'description' => 'Collect payments for product and service orders with this ready-made form template. You can add and remove fields as needed.',
			),
			array(
				'name'        => 'Newsletter Sign Up Form',
				'slug'        => 'subscribe',
				'description' => 'Add subscribers and grow your email list with this newsletter signup form. You can add and remove fields as needed.',
			)
		);
		?>
		<div class="wpforms-setup-title">Unlock Pre-Made Form Templates <a href="<?php echo $this->upgrade_link(); ?>" target="_blank" class="btn-green" style="text-transform:uppercase;font-size:13px;font-weight:700;padding:5px 10px;vertical-align:text-bottom;">Upgrade</a></div>
		<p class="wpforms-setup-desc">While WPForms Lite allows you to create any type of form, you can speed up the process by unlocking our other pre-built form templates among other features, so you never have to start from scratch again...</p>
		<div class="wpforms-setup-templates wpforms-clear" style="opacity:0.5;">
			<?php
			$x = 0;
			foreach ( $templates as $template ) {
				$class =  0 == $x % 3 ? 'first ' : '';
				?>
				<div class="wpforms-template upgrade-modal <?php echo $class; ?>" id="wpforms-template-<?php echo sanitize_html_class( $template['slug'] ); ?>">
					<div class="wpforms-template-name wpforms-clear">
						<?php echo esc_html( $template['name'] ); ?>
					</div>
					<div class="wpforms-template-details">
						<p class="desc"><?php echo esc_html( $template['description'] ); ?></p>
					</div>
				</div>
				<?php
				$x++;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Display/register additional fields available in the Pro version.
	 *
	 * @since 1.0.0
	 * @param array $fields
	 * @return array
	 */
	public function form_fields( $fields ) {

		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-link',
			'name'  => 'Website / URL',
			'type'  => 'url',
			'order' => '1',
			'class' => 'upgrade-modal',
		);
		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-map-marker',
			'name'  => 'Address',
			'type'  => 'address',
			'order' => '2',
			'class' => 'upgrade-modal',
		);
		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-phone',
			'name'  => 'Phone',
			'type'  => 'phone',
			'order' => '3',
			'class' => 'upgrade-modal',
		);
		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-lock',
			'name'  => 'Password',
			'type'  => 'password',
			'order' => '4',
			'class' => 'upgrade-modal',
		);
		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-calendar-o',
			'name'  => 'Date / Time',
			'type'  => 'date-time',
			'order' => '5',
			'class' => 'upgrade-modal',
		);
		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-eye-slash',
			'name'  => 'Hidden Field',
			'type'  => 'hidden',
			'order' => '6',
			'class' => 'upgrade-modal',
		);
		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-upload',
			'name'  => 'File Upload',
			'type'  => 'file-upload',
			'order' => '7',
			'class' => 'upgrade-modal',
		);
		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-code',
			'name'  => 'HTML',
			'type'  => 'html',
			'order' => '8',
			'class' => 'upgrade-modal',
		);
		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-files-o',
			'name'  => 'Page Break',
			'type'  => 'pagebreak',
			'order' => '9',
			'class' => 'upgrade-modal',
		);
		$fields['fancy']['fields'][] = array( 
			'icon'  => 'fa-arrows-h',
			'name'  => 'Divider',
			'type'  => 'Divider',
			'order' => '10',
			'class' => 'upgrade-modal',
		);
		$fields['payment']['fields'][] = array( 
			'icon'  => 'fa-file-o',
			'name'  => 'Single Item',
			'type'  => 'payment-single',
			'order' => '1',
			'class' => 'upgrade-modal',
		);
		$fields['payment']['fields'][] = array( 
			'icon'  => 'fa-list-ul',
			'name'  => 'Multiple Items',
			'type'  => 'payment-multiple',
			'order' => '2',
			'class' => 'upgrade-modal',
		);
		$fields['payment']['fields'][] = array( 
			'icon'  => 'fa-money',
			'name'  => 'Total',
			'type'  => 'payment-total',
			'order' => '3',
			'class' => 'upgrade-modal',
		);
		return $fields;
	}

	/**
	 * Display/register additional panels available in the Pro version.
	 *
	 * @since 1.0.0
	 */
	public function form_panels() {

		?>
		<button class="wpforms-panel-providers-button upgrade-modal" data-panel="providers">
			<i class="fa fa-bullhorn"></i><span>Marketing</span>
		</button>
		<button class="wpforms-panel-payments-button upgrade-modal" data-panel="payments">
			<i class="fa fa-usd"></i><span>Payments</span>
		</button>
		<?php
	}

	/**
	 * Load assets for lite version with the admin builder.
	 *
	 * @since 1.0.0
	 */
	public function builder_enqueues() {

		wp_enqueue_script( 
			'wpforms-builder-lite', 
			WPFORMS_PLUGIN_URL . 'lite/assets/js/admin-builder-lite.js', 
			array( 'jquery', 'jquery-confirm' ), 
			WPFORMS_VERSION, 
			false
		);

		wp_localize_script(
			'wpforms-builder-lite',
			'wpforms_builder_lite',
			array(
				'upgrade_title'     => __( 'is a PRO Feature', 'wpforms' ),
				'upgrade_message'   => __( 'We\'re sorry, %name% is not available on your plan.<br><br>Please upgrade to the PRO plan to unlock all these awesome features.', 'wpforms' ),
				'upgrade_button'    => __( 'Upgrade to PRO', 'wpforms' ),
				'upgrade_url'       => $this->upgrade_link()
			)
		);
	}


	/**
	 * Notify user that entries is a pro feature.
	 *
	 * @since 1.0.0
	 */
	public function entries_page() {

		if ( !isset( $_GET['page'] ) || 'wpforms-entries' != $_GET['page']  ) {
			return;
		}
		?>

		<div id="wpforms-entries" class="wrap">
			<h1 class="page-title">
				Entries
			</h1>
			<div class="notice notice-info below-h2">
				<p><strong>Entry management and storage is a PRO feature.</strong></p>
				<p>Please upgrade to the PRO plan to unlock it and more awesome features.</p>
				<p><a href="<?php echo $this->upgrade_link(); ?>" class="button button-primary" target="_blank">Upgrade Now</a></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Add appropriate styling to addons page.
	 *
	 * @since 1.0.4
	 */
	public function addon_page_enqueues() {

		if ( !isset( $_GET['page'] ) || $_GET['page'] != 'wpforms-addons' )
			return;

		// CSS
		wp_enqueue_style( 
			'font-awesome', 
			WPFORMS_PLUGIN_URL . 'assets/css/font-awesome.min.css', 
			null, 
			'4.4.0'
		);
		wp_enqueue_style( 
			'wpforms-addons', 
			WPFORMS_PLUGIN_URL . 'assets/css/admin-addons.css', 
			null, 
			WPFORMS_VERSION
		);
	}

	/**
	 * Notify user that addons are a pro feature.
	 *
	 * @since 1.0.0
	 */
	public function addons_page() {

		if ( !isset( $_GET['page'] ) || 'wpforms-addons' != $_GET['page']  ) {
			return;
		}

		$upgrade = $this->upgrade_link();
		$addons  = array(
			array(
				'name' => 'Aweber',
				'desc' => 'WPForms AWeber addon allows you to create AWeber newsletter signup forms in WordPress, so you can grow your email list.',
				'icon' => 'addon-icon-aweber.png'
			),
			array(
				'name' => 'Campaign Monitor',
				'desc' => 'WPForms Campaign Monitor addon allows you to create Campaign Monitor newsletter signup forms in WordPress, so you can grow your email list.',
				'icon' => 'addon-icon-campaign-monitor.png'
			),
			array(
				'name' => 'Conditional Logic',
				'desc' => 'WPForms\' smart conditional logic addon allows you to show or hide fields, sections, and subscribe to newsletters based on user selections, so you can collect the most relevant information.',
				'icon' => 'addon-icon-conditional-logic.png'
			),
			array(
				'name' => 'Custom Captcha',
				'desc' => 'WPForms custom captcha addon allows you to define custom questions or use random math questions as captcha to combat spam form submissions.',
				'icon' => 'addon-icon-captcha.png'
			),
			array(
				'name' => 'Geolocation',
				'desc' => 'WPForms geolocation addon allows you to collect and store your website visitors geolocation data along with their form submission.',
				'icon' => 'addon-icon-geolocation.png'
			),
			array(
				'name' => 'GetResponse',
				'desc' => 'WPForms GetResponse addon allows you to create GetResponse newsletter signup forms in WordPress, so you can grow your email list.',
				'icon' => 'addon-icon-getresponse.png'
			),
			array(
				'name' => 'MailChimp',
				'desc' => 'WPForms MailChimp addon allows you to create MailChimp newsletter signup forms in WordPress, so you can grow your email list.',
				'icon' => 'addon-icon-mailchimp.png'
			),
			array(
				'name' => 'PayPal Standard',
				'desc' => 'WPForms\' PayPal addon allows you to connect your WordPress site with PayPal to easily collect payments, donations, and online orders.',
				'icon' => 'addon-icon-paypal.png'
			),
			array(
				'name' => 'Post Submissions',
				'desc' => 'WPForms Post Submissions addon makes it easy to have user-submitted content in WordPress. This front-end post submission form allow your users to submit blog posts without logging into the admin area.',
				'icon' => 'addon-icon-post-submissions.png'
			),
			array(
				'name' => 'Stripe',
				'desc' => 'WPForms\' Stripe addon allows you to connect your WordPress site with Stripe to easily collect payments, donations, and online orders.',
				'icon' => 'addon-icon-stripe.png'
			),
			array(
				'name' => 'User Registration',
				'desc' => 'WPForms\' Stripe addon allows you to connect your WordPress site with Stripe to easily collect payments, donations, and online orders.',
				'icon' => 'addon-icon-user-registration.png'
			),
			array(
				'name' => 'Zapier',
				'desc' => 'WPForms\' Zapier addon allows you to connect your WordPress forms with over 500+ web apps. The integration possibilities here are just endless..',
				'icon' => 'addon-icon-zapier.png'
			),
		)
		?>
		<div id="wpforms-addons" class="wrap">
			<h1 class="page-title">Addons</h1>
			<div class="notice notice-info below-h2">
				<p><strong>Form Addons are a PRO feature.</strong></p>
				<p>Please upgrade to the PRO plan to unlock them and more awesome features.</p>
				<p><a href="<?php echo $upgrade; ?>" class="button button-primary">Upgrade Now</a></p>
			</div>
			<?php foreach( $addons as $addon ) : ?>
			 <div class="wpforms-addon-item wpforms-addon-status-upgrade">
				<div class="wpforms-addon-image"><img src="https://wpforms.com/images/<?php echo $addon['icon']; ?>"></div>
				<div class="wpforms-addon-text">
					<h4><?php echo $addon['name']; ?> Addon</h4>
					<p class="desc"><?php echo $addon['desc']; ?></p>
				</div>
				<div class="wpforms-addon-action"><a href="<?php echo $upgrade; ?>" target="_blank">Upgrade Now</a></div>
			</div>
			<?php endforeach; ?>
			<div style="clear:both"></div>
		</div>
		<?php
	}
}
new WPForms_Lite;