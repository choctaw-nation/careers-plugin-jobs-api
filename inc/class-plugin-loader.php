<?php
/**
 * Plugin Loader
 *
 * @package ChoctawNation
 * @subpackage PluginStarter
 */

namespace ChoctawNation\Jobs_API;

use ChoctawNation\Jobs_API\WP\AdminScreen\Admin_Screen;
use ChoctawNation\Jobs_API\WP\AdminScreen\Rest_Router;
use ChoctawNation\Jobs_API\WP\Plugin_Settings;

/** Inits the Plugin */
class Plugin_Loader {
	/**
	 * The directory path of the plugin
	 *
	 * @var string $dir_path
	 */
	private string $dir_path;

	/**
	 * The transient key for storing the local fetched job IDs in the database.
	 *
	 * @var string $transient_key
	 */
	private const TRANSIENT_KEY = 'cno_jobs_ids';

	/**
	 * Constructor
	 *
	 * @param string $dir_path The directory path of the plugin
	 */
	public function __construct( string $dir_path ) {
		$this->dir_path = $dir_path;
	}

	/**
	 * Initializes the Plugin
	 *
	 * @return void
	 */
	public function activate(): void {
		$plugin_settings = new Plugin_Settings();
		$plugin_settings->initialize_defaults();
		// 1.) init options [endpoint url, secret token, client id, api username, api password, site]

		// 2.) init cron schedule if needed?
	}

	/**
	 * Handles Plugin Deactivation
	 * (this is a callback function for the `register_deactivation_hook` function)
	 *
	 * @return void
	 */
	public function deactivate(): void {
		delete_option( Plugin_Settings::OPTION_KEY );
		// disable cron
		// delete transients
	}

	/**
	 * Handles Plugin Uninstallation
	 * (this is a callback function for the `register_uninstall_hook` function)
	 */
	public static function uninstall(): void {
		// delete cron
	}

	/**
	 * Loads the Plugin
	 */
	public function load_plugin(): void {
		$plugin_settings = new Plugin_Settings();
		add_action( 'admin_init', array( $plugin_settings, 'register' ) );

		// add rest api routes
		$settings_router = new Rest_Router( $plugin_settings );
		add_action( 'rest_api_init', array( $settings_router, 'register_routes' ) );

		$admin_screen = new Admin_Screen( $plugin_settings, $this->dir_path );
		// add admin menu
		add_action( 'admin_menu', array( $admin_screen, 'register_menus' ) );
		// add admin page assets (jsx, css)
		add_action( 'admin_enqueue_scripts', array( $admin_screen, 'load_required_assets' ) );
		// schedule cron job
	}
}