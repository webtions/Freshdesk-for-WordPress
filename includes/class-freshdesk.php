<?php

class Themeist_Freshdesk {

	protected $plugin_slug;

	protected $version;

	public $plugin_file;

	public function __construct( $plugin_file ) {

		$this->plugin_file = $plugin_file;

		if ( defined( 'THEMEIST_FRESHDESK_VERSION' ) ) {
			$this->version = THEMEIST_FRESHDESK_VERSION;
		} else {
			$this->version = '1.2.2';
		}

		$this->plugin_slug = 'freshdesk';
	}

	public function add_hooks() {

		add_action('init', array($this, 'load_localisation'), 0);
		
	}

	public function load_localisation() {
		load_plugin_textdomain('freshdesk', false, dirname(plugin_basename($this->plugin_file)) . '/languages/');
	}

}