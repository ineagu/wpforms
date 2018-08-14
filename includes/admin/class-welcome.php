<?php
/**
 * Welcome page class.
 *
 * This page is shown when the plugin is activated.
 *
 * @package    WPForms
 * @author     WPForms
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2016, WPForms LLC
*/
class WPForms_Welcome {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'admin_menu',             array( $this, 'register_pages'   )       );
		add_action( 'admin_head',             array( $this, 'hide_menu_items'  )       );
		add_action( 'admin_init',             array( $this, 'welcome_redirect' ), 9999 );
		add_action( 'admin_enqueue_scripts',  array( $this, 'welcome_enqueues' )       );
	}

	/**
	 * Register the pages to be used for the Welcome screen (and tabs).
	 *
	 * These pages will be removed from the Dashboard menu, so they will
	 * not actually show. Sneaky, sneaky.
	 *
	 * @since 1.0.0
	 */
	public function register_pages() {

		// Getting started - shows after installation
		add_dashboard_page(
			__( 'Getting started with WPForms', 'wpforms' ),
			__( 'Getting started with WPForms', 'wpforms' ),
			apply_filters( 'wpforms_welcome_cap', 'manage_options' ),
			'wpforms-getting-started',
			array( $this, 'welcome_getting_started' )
		);

		// What's New - shows after upgrade
		/*
		add_dashboard_page(
			__( 'What is new in WPForms', 'wpforms' ),
			__( 'What is new in WPForms', 'wpforms' ),
			apply_filters( 'wpforms_welcome_cap', 'manage_options' ),
			'wpforms-whats-new',
			array( $this, 'welcome_whats_new' )
		);
		*/
	}

	/**
	 * Removed the dashboard pages from the admin menu.
	 *
	 * This means the pages are still available to us, but hidden.
	 *
	 * @since 1.0.0
	 */
	public function hide_menu_items() {

		remove_submenu_page( 'index.php', 'wpforms-getting-started' );
		//remove_submenu_page( 'index.php', 'wpforms-whats-new'       );
	}

	/**
	 * Welcome screen header area.
	 *
	 * Consists of the plugin title, desciption, badge, and navigation tabs.
	 *
	 * @since 1.0.0
	 */
	public function welcome_head() {
		
		$selected = isset( $_GET['page'] ) ? $_GET['page'] : 'wpforms-getting-started';
		?>
		<h1><?php _e( 'Welcome to WPForms', 'wpforms' ); ?></h1>
		<div class="about-text">
			<?php _e( 'Thank you for choosing WPForms - the most beginner friendly WordPress contact form plugin. Here\'s how to get started.', 'wpforms' ); ?>
		</div>
		<div class="wpforms-badge">
			<img src="<?php echo WPFORMS_PLUGIN_URL; ?>assets/images/sullie.png" alt="Sullie WPForms mascot">
			<span class="version">Version <?php echo WPFORMS_VERSION; ?></span>
		</div>
		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php echo $selected == 'wpforms-getting-started' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpforms-getting-started' ), 'index.php' ) ) ); ?>">
				<?php _e( 'Getting Started', 'wpforms' ); ?>
			</a>
			<!--<a class="nav-tab <?php echo $selected == 'wpforms-whats-new' ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wpforms-whats-new' ), 'index.php' ) ) ); ?>">
				<?php _e( "What's New", 'wpforms' ); ?>
			</a> -->
		</h2>
		<?php
	}

	/**
	 * Getting Started screen. Shows after first install.
	 *
	 * @since 1.0.0
	 */
	public function welcome_getting_started() {

		?>
		<div class="wrap about-wrap">
			
			<?php $this->welcome_head(); ?>
			
			<p class="about-description">
				<?php _e( 'Use the tips below to get started using WPForms. You will be up and running in no time.', 'wpforms' ); ?>
			</p>

			<div class="feature-section two-col">	
				<div class="col">
					<h3><?php _e( 'Creating Your First Form' , 'wpforms' ); ?></h3>
					<p><?php printf( __( 'WPForms make it easy to create forms in WordPress. You can follow the video tutorial on the right or read our how to <a href="%s" target="_blank">create your first form guide</a>.', 'wpforms' ), ' https://wpforms.com/docs/creating-first-form/ ' ); ?>
					<p><?php printf( __( 'But in reality, the process is so intuitive that you can just start by going to <a href="%s">WPForms - > Add New</a>.', 'wpforms' ), admin_url( 'admin.php?page=wpforms-builder' ) ); ?>
				</div>
				<div class="col">
					<div class="feature-video">
						<iframe width="495" height="278" src="https://www.youtube-nocookie.com/embed/yDyvSGV7tP4?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>
					</div>
				</div>
			</div>

			<div class="feature-section two-col">	
				<div class="col">
					<h3><?php _e( 'See all WPForms Features', 'wpforms' ); ?></h3>
					<p><?php _e( 'WPForms is both easy to use and extremely powerful. We have tons of helpful features that allows us to give you everything you need from a form builder.', 'wpforms' ); ?></p>
					<p><a href="https://wpforms.com/features/" target="_blank" class="wpforms-features-button button button-primary"><?php _e( 'See all Features', 'wpforms' ); ?></a></p>
				</div>
				<div class="col">
					<img src="<?php echo WPFORMS_PLUGIN_URL; ?>assets/images/welcome-features.png">
				</div>
			</div>
				
		</div>
		<script type="text/javascript">
			jQuery(function($){
				$('.feature-video').fitVids();
			});
		</script>
		<?php
	}

	/**
	 * What's New screen. Shows after updates.
	 *
	 * @since 1.0.0
	 */
	public function welcome_whats_new() {

		?>
		<div class="wrap about-wrap">
			<?php $this->welcome_head(); ?>
		</div>
		<?php
	}

	/**
	 * Welcome screen redirect.
	 *
	 * This function checks if a new install or update has just occured. If so,
	 * then we redirect the user to the appropriate page.
	 *
	 * @since 1.0.0
	 */
	public function welcome_redirect() {

		// Check if we should consider redirection
		if ( !get_transient( 'wpforms_activation_redirect' ) ) {
			return;
		}

		// If we are redirecting, clear the transient so it only happens once
		delete_transient( 'wpforms_activation_redirect' );

		// Only do this for single site installs
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Check if this is an update or first install
		$upgrade = get_option( 'wpforms_version_upgraded_from' );

		if ( ! $upgrade ) {
			// Initial install
			wp_safe_redirect( admin_url( 'index.php?page=wpforms-getting-started' ) );
			exit;
		}
	}

	/**
	 * Load our required assets on the Welcome page(s).
	 *
	 * @since 1.0.0
	 */
	public function welcome_enqueues() {

		if ( !isset( $_GET['page'] ) || 'wpforms-getting-started' != $_GET['page'] )
			return;

		wp_enqueue_style( 
			'wpforms-welcome', 
			WPFORMS_PLUGIN_URL . 'assets/css/admin-welcome.css', 
			null, 
			WPFORMS_VERSION
		);

		wp_enqueue_script( 
			'fitvids', 
			WPFORMS_PLUGIN_URL . 'assets/js/jquery.fitvids.js', 
			array( 'jquery' ), 
			'1.1.0',
			false
		);
	}
}
new WPForms_Welcome;