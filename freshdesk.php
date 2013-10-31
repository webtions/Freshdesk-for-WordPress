<?php
/*
 * Plugin Name: Freshdesk
 * Plugin URI: http://www.harishchouhan.com/wordpress-plugins/freshdesk/
 * Description: Allows you to setup single sign on capabilities between your site and Freshdesk. Create's user accounts on the fly, automatically logs in users.
 * Version: 1.2.1
 * Author: Harish Chouhan
 * Author URI: http://www.harishchouhan.com/
 * Author Email: hello@dreamsmedia.in
 * License: GPLv2

 * License:

  Copyright 2013 "Freshdesk Plugin" (hellO@dreamsmedia.in)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'DOT_Freshdesk' ) ) {

	class DOT_Freshdesk {

		public $settings = array();

		/*--------------------------------------------*
		 * Constructor
		 *--------------------------------------------*/

		/**
		 * Initializes the plugin by setting localization, filters, and administration functions.
		 */
		function __construct( ) {

			// Load text domain
			load_plugin_textdomain( 'freshdesk', false, basename( dirname( __FILE__ ) ) . '/languages' );

			add_action( 'admin_menu', array( &$this, 'dot_freshdesk_menu' ) );
			add_action( 'admin_init', array( &$this, '_admin_init' ) );

			// Register admin styles and scripts
			add_action( 'admin_print_styles', array( &$this, 'register_admin_styles' ) );

			add_filter( 'freshdesk_installed', array( &$this, '__return_true' ) );

			// Initialize
			$this->setup();

			// Let's see if we need to do a remote auth.
			$this->_do_remote_auth();

		} // end constructor



		/*
		 * Plugin Setup
		 *
		 * Load settings, set URLs, authenticate the current user.
		 *
		 */
		public function setup() {

			// Load up the settings, set the Freshdesk URL and initialize the API object.
			$this->_load_settings();

			// Load default settings if there are no settings
			if ( false === $this->settings )
				$this->_default_settings();

			// $this->_delete_settings();

			$this->freshdesk_url = 'https://' . $this->settings['account'] . '.freshdesk.com';
		}

		/**
		 * Registers and enqueues admin-specific styles.
		 */
		public function register_admin_styles() {

			// TODO change 'plugin-name' to the name of your plugin
			wp_register_style( 'freshdesk-admin-styles', plugins_url( 'freshdesk/css/admin.css' ) );
			wp_enqueue_style( 'freshdesk-admin-styles' );

		} // end register_admin_styles

		/*
		 * Load Default Settings
		 *
		 * Sets the defaults for the settings array and calls _update_settings()
		 * to write changes to the database. Generally run during plugin
		 * activation or first run.
		 *
		 */
		private function _default_settings() {
			$this->settings = $this->default_settings;

			$this->_update_settings();
		}

		/*
		 * Load Settings
		 *
		 * Private function to load current settings from the database. Sets
		 * settings to false if settings are not found (i.e. plugin is new).
		 *
		 */
		private function _load_settings() {
			$this->settings = get_option( 'dot_freshdesk_settings', false );

			$this->default_settings = array(
				'version' => 1,
				'account' => '',
				'enabled' => false,
				'token' => ''
			);

		}

		/*
		 * Delete Settings
		 *
		 * Removes all Freshdesk settings from the database, as well as flushes
		 * all the user's authentication settings. Use this during plugin
		 * deactivation.
		 *
		 */
		private function _delete_settings() {
			delete_option( 'dot_freshdesk_settings' );
		}

		/*
		 * Update Settings
		 *
		 * Use this private method after doing any changes to the settings
		 * arrays. This method writes the changes to the database.
		 *
		 */
		private function _update_settings() {
			update_option( 'dot_freshdesk_settings', $this->settings );
		}

		/*--------------------------------------------*
		 * Admin Menu
		 *--------------------------------------------*/

		function dot_freshdesk_menu() {

			add_menu_page( 'Freshdesk', 'Freshdesk', 'manage_options', 'freshdesk', array( &$this, '_admin_menu_contents' ), plugins_url( '/images/icon-16.png', __FILE__ ) );
			$settings_page = add_submenu_page( 'freshdesk', __( 'Freshdesk Settings', 'freshdesk' ), __( 'Settings', 'freshdesk' ), 'manage_options', 'freshdesk', array( &$this, '_admin_menu_contents' ) );

		}	//dot_freshdesk_menu


		/*--------------------------------------------*
		 * Settings & Settings Page
		 *--------------------------------------------*/

		public function _admin_init() {

			// General Settings
			register_setting( 'dot_freshdesk_settings', 'dot_freshdesk_settings', array(&$this, '_validate_settings') );

			// Authentication Details
			add_settings_section( 'authentication', __( 'Freshdesk Account', 'freshdesk' ), array( &$this, '_settings_section_authentication' ), 'dot_freshdesk_settings' );
			add_settings_field( 'account', __( 'Subdomain', 'freshdesk' ), array( &$this, '_settings_field_account' ), 'dot_freshdesk_settings', 'authentication' );

			// Display the rest of the settings only if a Freshdesk account has been specified.
			if ( $this->settings['account'] ) {

				// Remote Authentication Section Freshdesk
				add_settings_section( 'freshdesk', __( 'Freshdesk Configuration', 'freshdesk' ), array( &$this, '_settings_remote_auth_section_freshdesk' ), 'dot_freshdesk_settings' );
				add_settings_field( 'login_url', __( 'Remote Login URL', 'freshdesk' ), array( &$this, '_settings_field_remote_auth_login_url' ), 'dot_freshdesk_settings', 'freshdesk' );
				add_settings_field( 'logout_url', __( 'Remote Logout URL', 'freshdesk' ), array( &$this, '_settings_field_remote_auth_logout_url' ), 'dot_freshdesk_settings', 'freshdesk' );

				// Remote Authentication Section
				add_settings_section( 'general', __( 'General Settings', 'freshdesk' ), array( &$this, '_settings_remote_auth_section_general' ), 'dot_freshdesk_settings' );
				add_settings_field( 'enabled', __( 'Remote Auth Status', 'freshdesk' ), array( &$this, '_settings_field_remote_auth_enabled' ), 'dot_freshdesk_settings', 'general' );
				add_settings_field( 'token', __( 'Remote Auth Shared Token', 'freshdesk' ), array( &$this, '_settings_field_remote_auth_token' ), 'dot_freshdesk_settings', 'general' );

			}

		}	//dot_freshdesk_settings

		/*--------------------------------------------*
		 * Settings & Settings Page
		 * dot_freshdesk_admin_menu_contents
		 *--------------------------------------------*/

		public function _admin_menu_contents() {
		?>
			<div class="wrap">
				<div id="icon-freshdesk-32" class="icon32"><br></div>
				<!--<div id="icon-options-general" class="icon32"><br></div>-->
				<?php //screen_icon(); ?>
				<h2><?php _e('Freshdesk for WordPress Settings', 'freshdesk'); ?></h2>

				<?php if ( ! $this->settings['account'] ): ?>
					<div id="message" class="updated below-h2 freshdesk-info">
						<p><?php _e( "Before you access other features, please enter your Freshdesk subdomain.", 'freshdesk' ); ?></p>
					</div>
				<?php endif; ?>

				<form method="post" action="options.php">
					<?php wp_nonce_field('update-options'); ?>
					<?php settings_fields('dot_freshdesk_settings'); ?>
					<?php do_settings_sections('dot_freshdesk_settings'); ?>
					<p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'freshdesk'); ?>" />
					</p>
				</form>
			</div>

		<?php
		}	//dot_freshdesk_admin_menu_contents


		/*
		 * Settings Validation
		 *
		 * Validates all the incoming settings, generally submitted from
		 * the Freshdesk Settings admin page. Check, sanitize, strip and
		 * return. The returning array is stored in the database and then
		 * accessible through $this->settings.
		 *
		 */
		public function _validate_settings( $settings ) {

			$settings['version'] = $this->default_settings['version'];

			// Validate the Freshdesk Account
			if ( ! preg_match( '/^[a-zA-Z0-9]{0,}$/', $settings['account'] ) )
				unset( $settings['account'] );


			// Merge the submitted settings with the defaults. Second
			// argument will overwrite the first.
			if ( is_array( $this->settings ) )
				$settings = array_merge( $this->settings, $settings );
			else
				$settings = array_merge( $this->default_settings, $settings );

			$settings['enabled'] = empty( $settings['token'] ) ? false : true;

			return $settings;
		}


		/*
		 * Settings: Authentication Section
		 *
		 * Outputs the description for the authentication settings registered
		 * during admin_init, displayed underneath the section title, which
		 * is defined during section registration.
		 *
		 */

		public function _settings_section_authentication() {
			_e( "Add your Freshdesk subdomain to proceed further.", 'freshdesk' );
		}

		/*
		 * Settings: Account Field
		 *
		 * Field for $this->settings['account'] -- simply the account name,
		 * without any http or freshdesk.com prefixes and postfixes. Validated
		 * together with all the other options.
		 *
		 */
		public function _settings_field_account() {
		?>

				<strong>http://<input type="text" style="width: 120px;" class="regular-text" id="freshdesk_account" name="dot_freshdesk_settings[account]" value="<?php echo $this->settings["account"]; ?>" />.freshdesk.com</strong> <br />
				<span class="description">Please enter your freshdesk account sub domain here.</span>
		<?php
		}

		/*
		 * Settings Section: Remote Auth General
		 *
		 */
		public function _settings_remote_auth_section_general() {
			_e( 'The general remote authentication settings', 'freshdesk' );
		}

		/*
		 * Settings Remote Auth: Enabled
		 *
		 * This simply says whether remote authentication is enabled or not,
		 * used to be a checkbox, but that is now handled in the remote
		 * auth validation section.
		 *
		 */
		public function _settings_field_remote_auth_enabled() {

			$remote_auth = (bool) $this->settings['enabled'];
		?>
				<span class="description">
					<?php if ( $remote_auth ): ?>
						<strong><?php _e( 'Remote authentication is enabled', 'freshdesk' ); ?></strong>
					<?php else: ?>
						<strong><?php _e( 'Remote authentication is <strong>disabled</strong>', 'freshdesk' ); ?></strong>
				<?php endif; ?>

				<br /><?php _e( 'To activate remote authentication, ensure a shared token <br /> is entered below and click &quot;Save Changes&quot;', 'freshdesk' ); ?>
				</span>
		<?php
		}

		/*
		 * Settings Remote Auth: Shared Token
		 *
		 * Shared token is the shared secret located under the single sign-on
		 * settings on the Freshdesk Account Security page. We ask for that
		 * token right here.
		 *
		 */
		public function _settings_field_remote_auth_token() {
		?>
			<input type="text" class="regular-text" name="dot_freshdesk_settings[token]" value="<?php echo $this->settings['token']; ?>" /><br />
			<span class="description">
				<?php printf( __( 'Your shared token could be obtained on the %s in the <br /> Single Sign-On section.', 'freshdesk' ), sprintf( '<a target="_blank" href="' . trailingslashit( $this->freshdesk_url ) . 'admin/security">%s</a>', __( 'Account Security page', 'freshdesk' ) ) ); ?>
				<br /><br />
				<?php printf( __( '<strong>Remember</strong> that you can always go to: <br /> %s to use the regular login <br /> in case you get unlucky and somehow lock yourself out of Freshdesk.', 'freshdesk' ), '<a target="_blank" href="' . trailingslashit( $this->freshdesk_url ) . 'login/normal' . '">' . trailingslashit( $this->freshdesk_url ) . 'access/normal' . '</a>' ); ?>
			</span>
		<?php
		}

		/*
		 * Settings Section: Remote Auth for Freshdesk
		 *
		 */
		public function _settings_remote_auth_section_freshdesk() {
			_e( 'The settings that need to be configured in your Freshdesk account.', 'freshdesk' );
		}

		/*
		 * Settings Field: Remote Auth Login URL
		 *
		 * Displays the login URL for the Freshdesk remote auth settings.
		 *
		 */
		public function _settings_field_remote_auth_login_url() {
			echo '<code>' . wp_login_url() . '?action=freshdesk-remote-login' . '</code>';
		}

		/*
		 * Settings Field: Remote Auth Logout URL
		 *
		 * Same as above but displays the logout URL.
		 *
		 */
		public function _settings_field_remote_auth_logout_url() {
			echo '<code>' . wp_login_url() . '?action=freshdesk-remote-logout' . '</code>';
		}


		/*
		 * Remote Authentication Process
		 *
		 * This is fired during plugin setup, i.e. during the init WordPress
		 * action, thus we have control over any redirects before the request
		 * is ever processed by the WordPress interpreter.
		 *
		 * Remote Auth is described here: http://www.freshdesk.com/api/remote-authentication
		 *
		 * This method does both login and logout requests.
		 *
		 */
		public function _do_remote_auth() {
			// This is a login request.
			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'freshdesk-remote-login' ) {

				// Don't waste time if remote auth is turned off.
				if ( ! isset( $this->settings['enabled'] ) || ! $this->settings['enabled'] ) {
					_e( 'Remote authentication is not configured yet.', 'freshdesk' );
					die();
				}

				// Filter freshdesk_return_to
				$return_to = apply_filters( 'freshdesk_return_to', $_REQUEST['return_to'] ) ;

				global $current_user;
				wp_get_current_user();

				// If the current user is logged in
				if ( 0 != $current_user->ID ) {

					// Pick the most appropriate name for the current user.
					if ( $current_user->user_firstname != '' && $current_user->user_lastname != '' )
						$name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
					else
						$name = $current_user->display_name;

					// Gather more info from the user, incl. external ID
					$email = $current_user->user_email;

					// The token is the remote "Shared Secret" under Admin - Security - Enable Single Sign On
					$token = $this->settings['token'];

					// Generate the hash as per http://www.freshdesk.com/api/remote-authentication
					$hash = md5( $name . $email . $token );

					// Create the SSO redirect URL and fire the redirect.
					$sso_url = trailingslashit( $this->freshdesk_url ) . 'login/sso/?action=freshdesk-remote-login&return_to=' . urlencode( $return_to ) . '&name=' . urlencode( $name ) . '&email=' . urlencode( $email ) . '&hash=' . urlencode( $hash );

					//Hook before redirecting logged in user.
					do_action( 'freshdesk_logged_in_redirect_before' );

					wp_redirect( $sso_url );

					// No further output.
					die();
				} else {

					//Hook before redirecting user to login form
					do_action( 'freshdesk_logged_in_redirect_before' );

					// If the current user is not logged in we ask him to visit the login form
					// first, authenticate and specify the current URL again as the return
					// to address. Hopefully WordPress will understand this.
					wp_redirect( wp_login_url( wp_login_url() . '?action=freshdesk-remote-login&&return_to=' . urlencode( $return_to ) ) );
					die();
				}
			}

			// Is this a logout request? Errors from Freshdesk are handled here too.
			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'freshdesk-remote-logout' ) {

				// Don't waste time if remote auth is turned off.
				if ( ! isset( $this->settings['enabled'] ) || ! $this->settings['enabled'] ) {
					_e( 'Remote authentication is not configured yet.', 'freshdesk' );
					die();
				}


				// Error processing and info messages are done here.
				$kind = isset( $_REQUEST['kind'] ) ? $_REQUEST['kind'] : 'info';
				$message = isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : 'nothing';

				// Depending on the message kind
				if ( $kind == 'info' ) {

					// When the kind is an info, it probably means that the logout
					// was successful, thus, logout of WordPress too.
					wp_redirect( htmlspecialchars_decode( wp_logout_url() ) );
					die();

				} elseif ( $kind == 'error' ) {
					// If there was an error...
				?>
					<p><?php _e( 'Remote authentication failed: ', 'freshdesk' ); ?><?php echo $message; ?>.</p>
					<ul>
						<li><a href="<?php echo $this->freshdesk_url; ?>"><?php _e( 'Try again', 'freshdesk' ); ?></a></li>
						<li><a href="<?php echo wp_logout_url(); ?>"><?php printf( __( 'Log out of %s', 'freshdesk' ), get_bloginfo( 'name' ) ); ?></a></li>
						<li><a href="<?php echo admin_url(); ?>"><?php printf( __( 'Return to %s dashboard', 'freshdesk' ), get_bloginfo( 'name' ) ); ?></a></li>
					</ul>
				<?php
				}

				// No further output.
				die();
			}
		}


	} // end class

	// Initiation call of plugin
	//$dot_freshdesk = new DOT_Freshdesk(__FILE__);
};

add_action( 'init', create_function( '', 'global $dot_freshdesk; $dot_freshdesk = new DOT_Freshdesk();' ) );