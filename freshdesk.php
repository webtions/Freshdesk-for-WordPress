<?php
/*
 * Plugin Name:       Freshdesk
 * Plugin URI:        https://themeist.com/plugins/wordpress/freshdesk/#utm_source=wp-plugin&utm_medium=i-recommend-this&utm_campaign=plugins-page
 * Description:       Allows you to setup single sign on capabilities between your site and Freshdesk. Create's user accounts on the fly, automatically logs in users.
 * Version:           1.2.1
 * Author:            Harish Chouhan, Themeist
 * Author URI:        https://themeist.com/
 * Author Email:      support@themeist.com
 * Text Domain:       themeist_freshdesk
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// do nothing if class is already defined
if( class_exists( 'Themeist_Freshdesk' ) ) {
	return;
}

define( 'THEMEIST_FRESHDESK_VERSION', '1.2.2' );

// require includes
require_once dirname( __FILE__ ) . '/includes/class-freshdesk.php';
require_once dirname( __FILE__ ) . '/admin/class-freshdesk-admin.php';

// create instance of plugin class
global $themeist_freshdesk;
$themeist_freshdesk = new Themeist_Freshdesk( __FILE__ );
$themeist_freshdesk->add_hooks();

// create instance of admin class
global $themeist_freshdesk_admin;
$themeist_freshdesk_admin = new Themeist_Freshdesk_Admin( __FILE__ );
$themeist_freshdesk_admin->add_admin_hooks();