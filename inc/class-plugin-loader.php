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
	 * The Post Type Handler instance for managing the custom post type and taxonomies.
	 *
	 * @var Post_Type_Handler $post_type_handler
	 */
	private Post_Type_Handler $post_type_handler;

	/**
	 * Constructor
	 *
	 * @param string $dir_path The directory path of the plugin
	 */
	public function __construct( string $dir_path ) {
		$this->dir_path          = $dir_path;
		$this->post_type_handler = new Post_Type_Handler( 'post' );
	}

	/**
	 * Initializes the Plugin
	 *
	 * @return void
	 */
	public function activate(): void {
		// 1.) init options [endpoint url, secret token, client id, api username, api password, site]
		$plugin_settings = new Plugin_Settings();
		$plugin_settings->initialize_defaults();

		// 2.) alter post type and register taxonomies
		$this->post_type_handler->maybe_register_cpt();
		$this->post_type_handler->maybe_register_cpt();
		add_action( 'init', array( $this->post_type_handler, 'alter_post_labels' ) );
		$this->post_type_handler->handle_taxonomies();
	}

	/**
	 * Handles Plugin Deactivation
	 * (this is a callback function for the `register_deactivation_hook` function)
	 *
	 * @return void
	 */
	public function deactivate(): void {
		delete_option( Plugin_Settings::OPTION_KEY );
		$this->post_type_handler->maybe_unregister_cpt();
		// disable cron
		// delete transients
	}

	/**
	 * Handles Plugin Uninstallation
	 * (this is a callback function for the `register_uninstall_hook` function)
	 */
	public static function uninstall(): void {
		// delete cron
		// maybe delete posts?
		// maybe delete taxonomies?
	}

	/**
	 * Loads the Plugin
	 */
	public function load_plugin(): void {
		$plugin_settings = new Plugin_Settings();
		add_action( 'admin_init', array( $plugin_settings, 'register' ) );

		// add admin rest api routes
		$settings_router = new Rest_Router( $plugin_settings );
		add_action( 'rest_api_init', array( $settings_router, 'register_routes' ) );

		// add admin screen
		$admin_screen = new Admin_Screen( $plugin_settings, $this->dir_path );
		add_action( 'admin_menu', array( $admin_screen, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $admin_screen, 'load_required_assets' ) );

		// schedule cron job
		$settings  = $plugin_settings->get_settings();
		$scheduler = new WP\Scheduler( $settings['syncInterval'] ?? 'daily', $settings['syncTime'] ?? '00:00' );
		$scheduler->schedule_cron_jobs();

		// attach cron job callbacks
		$sync_jobs_service = new Services\Sync_Jobs( self::TRANSIENT_KEY );
		add_action( WP\Scheduler::FETCH_CRON_KEY, array( $sync_jobs_service, 'fetch_jobs' ) );
		add_action( WP\Scheduler::DELETE_CRON_KEY, array( $sync_jobs_service, 'delete_jobs' ) );
	}
}