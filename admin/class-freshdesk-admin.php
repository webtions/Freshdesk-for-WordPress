<?php

class Themeist_Freshdesk_Admin {

	/**
	 * @param string $plugin_file
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	public function add_admin_hooks() {
		global $pagenow;

		add_filter( 'admin_footer_text', array( $this, 'footer_text' ) );

		// Hooks for Plugins overview page
		if( $pagenow === 'plugins.php' ) {
			add_filter( 'plugin_action_links', array( $this, 'add_plugin_settings_link' ), 10, 2 );
			add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links'), 10, 2 );
		}

		add_action('admin_menu', array($this, 'themeist_freshdesk_menu'));
		add_action('admin_init', array($this, 'themeist_freshdesk_settings'));
	}

	public function themeist_freshdesk_menu() {
		$page_title = __('Freshdesk', 'freshdesk');
		$menu_title = __('Freshdesk', 'freshdesk');
		$capability = 'manage_options';
		$menu_slug = 'themeist-freshdesk';
		$function = array(&$this, 'themeist_freshdesk_settings_page');
		add_options_page($page_title, $menu_title, $capability, $menu_slug, $function);
	}

	public function add_plugin_settings_link( $links, $file ) {
		if ( $file == plugin_basename($this->plugin_file) ) {

			$settings_link = '<a href="' . admin_url( 'options-general.php?page=themeist-freshdesk' ) . '">'. __( 'Settings', 'freshdesk' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	public function add_plugin_meta_links( $links, $file ) {
		if ( strpos( $file, 'freshdesk.php' ) !== false ) {
			$new_links = array(
					'donate' => '<a href="https://www.paypal.me/harishchouhan" target="_blank">Donate</a>',
					'Documentation' => '<a href="https://themeist.com/docs/#utm_source=wp-plugin&utm_medium=freshdesk&utm_campaign=plugins-page" target="_blank">Documentation</a>'
				);

			$links = array_merge( $links, $new_links );
		}
		return $links;
	}

	public function footer_text( $text ) {
		if(! empty( $_GET['page'] ) && strpos( $_GET['page'], 'themeist-freshdesk' ) === 0 ) {
			$text = sprintf( 'If you enjoy using <strong>Freshdesk</strong> for WordPress Plugin, please <a href="%s" target="_blank">leave us a ★★★★★ rating</a>. A <strong style="text-decoration: underline;">huge</strong> thank you in advance!', 'https://wordpress.org/support/view/plugin-reviews/freshdesk?rate=5#postform' );
		}
		return $text;
	}

	public function themeist_freshdesk_settings() {

		// General Settings
		register_setting( 'dot_freshdesk_settings', 'dot_freshdesk_settings', array(&$this, '_validate_settings') );
		// Authentication Details
		add_settings_section( 'authentication', __( 'Freshdesk Account', 'freshdesk' ), array( &$this, '_settings_section_authentication' ), 'dot_freshdesk_settings' );
		add_settings_field( 'account', __( 'Subdomain', 'freshdesk' ), array( &$this, '_settings_field_account' ), 'dot_freshdesk_settings', 'authentication' );


		register_setting('themeist-freshdesk', 'themeist_freshdesk_settings', array(&$this, 'settings_validate'));
		add_settings_section('themeist-freshdesk', '', array(&$this, '_settings_section_authentication'), 'themeist-freshdesk');
		add_settings_field( 'account', __( 'Subdomain', 'freshdesk' ), array( &$this, '_settings_field_account' ), 'themeist-freshdesk', 'authentication' );


			// Authentication Details
			//add_settings_section( 'authentication', __( 'Freshdesk Account', 'freshdesk' ), array( &$this, '_settings_section_authentication' ), 'themeist_freshdesk_settings' );
			add_settings_field( 'account', __( 'Subdomain', 'freshdesk' ), array( &$this, '_settings_field_account' ), 'themeist_freshdesk_settings', 'authentication' );

	}

	public function _settings_section_authentication() {
		?>

		<p><?php _e( "Add your Freshdesk subdomain to proceed further.", 'freshdesk' ); ?></p>
		<?php
	}

	public function _settings_field_account() {
	?>

			<strong>http://<input type="text" style="width: 120px;" class="regular-text" id="freshdesk_account" name="dot_freshdesk_settings[account]" value="<?php echo $this->settings["account"]; ?>" />.freshdesk.com</strong> <br />
			<span class="description">Please enter your freshdesk account sub domain here.</span>
	<?php
	}

	public function settings_validate($input) {

		return $input;
	}

	public function themeist_freshdesk_settings_page() {
		?>
		<div id="irecommendthis-settings" class="wrap irecommendthis-settings">
			<h1><?php _e('Freshdesk for WordPress Settings', 'freshdesk'); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields('themeist-freshdesk'); ?>
				<?php do_settings_sections('themeist-freshdesk'); ?>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes', 'freshdesk'); ?>"/></p>
			</form>
		</div>
		<?php
	}

	public function section_intro() {
		?>

		<p><?php _e('This plugin allows your visitors to simply recommend or like your posts instead of commment it.', 'i-recommend-this'); ?></p>
		<?php
	}

}