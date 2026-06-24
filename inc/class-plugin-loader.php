<?php
/**
 * Plugin Loader
 *
 * @package ChoctawNation
 * @subpackage PluginStarter
 */

namespace ChoctawNation\Jobs_API;

use ChoctawNation\Jobs_API\Services\Job_Repository;
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
	 * The Job Repository instance for interacting with WordPress job posts.
	 *
	 * @var Job_Repository $job_repository
	 */
	private Job_Repository $job_repository;

	/**
	 * The Plugin Settings instance for managing plugin options and settings.
	 *
	 * @var Plugin_Settings $plugin_settings
	 */
	private Plugin_Settings $plugin_settings;

	/**
	 * Constructor
	 *
	 * @param string $dir_path The directory path of the plugin
	 */
	public function __construct( string $dir_path ) {
		$post_type               = 'post';
		$this->dir_path          = $dir_path;
		$this->plugin_settings   = new Plugin_Settings();
		$this->post_type_handler = new Post_Type_Handler( $post_type );
		$job_description_builder = new Jobs\Job_Description();
		$this->job_repository    = new Job_Repository( $post_type, self::TRANSIENT_KEY, $job_description_builder );
		// 2.) alter post type and register taxonomies
		$this->post_type_handler->maybe_register_cpt();
		$this->post_type_handler->maybe_register_cpt();
		add_action( 'init', array( $this->post_type_handler, 'alter_post_labels' ) );
		add_action( 'init', array( $this->post_type_handler, 'register_taxonomies' ) );
	}

	/**
	 * Initializes the Plugin
	 *
	 * @return void
	 */
	public function activate(): void {
		// 1.) init options [endpoint url, secret token, client id, api username, api password, site]
		$this->plugin_settings->initialize_defaults();
		flush_rewrite_rules();
	}

	/**
	 * Handles Plugin Deactivation
	 * (this is a callback function for the `register_deactivation_hook` function)
	 *
	 * @return void
	 */
	public function deactivate(): void {
		delete_option( Plugin_Settings::OPTION_KEY );
		flush_rewrite_rules();
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
		add_action( 'admin_init', array( $this->plugin_settings, 'register' ) );

		// add admin rest api routes
		$settings_router = new Rest_Router( $this->plugin_settings );
		add_action( 'rest_api_init', array( $settings_router, 'register_routes' ) );

		// add admin screen
		$admin_screen = new Admin_Screen( $this->plugin_settings, $this->dir_path );
		add_action( 'admin_menu', array( $admin_screen, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $admin_screen, 'load_required_assets' ) );

		// schedule cron job
		$settings  = $this->plugin_settings->get_settings();
		$scheduler = new WP\Scheduler( $settings['syncInterval'] ?? 'daily', $settings['syncTime'] ?? '00:00' );
		add_filter( 'cron_schedules', array( $scheduler, 'add_custom_cron_schedules' ) );
		$scheduler->schedule_cron_jobs();
		$this->wire_the_cron_jobs( $settings );
	}

	/**
	 * Wires the Cron Jobs to their callbacks with all dependencies injected. This is separated from the `load_plugin` method to keep it organized and focused on just the cron job wiring logic.
	 *
	 * @param array $settings The plugin settings array, which may contain necessary configuration for the services used in the cron job callbacks.
	 */
	private function wire_the_cron_jobs( array $settings ) {
		$notifier          = new WP\Notifier( array( 'kroelke@choctawnation.com', 'bperkins@choctawnation.com' ) );
		$api_client        = new Http\Api_Client( $settings, $notifier );
		$sync_jobs_service = new Services\Sync_Jobs( $notifier, $this->job_repository, $api_client, new Jobs\Last_Load_Transient() );
		add_action( WP\Scheduler::FETCH_CRON_KEY, array( $sync_jobs_service, 'fetch_jobs' ) );
		add_action( WP\Scheduler::DELETE_CRON_KEY, array( $sync_jobs_service, 'delete_jobs' ) );
	}
}