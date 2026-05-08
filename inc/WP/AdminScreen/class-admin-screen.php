<?php
/**
 * Admin screen and settings registration for Jobs API.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\WP\AdminScreen;

use ChoctawNation\Jobs_API\WP\Plugin_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Screen
 *
 * Handles the admin menu and settings for the Jobs API plugin.
 */
class Admin_Screen {
	/**
	 * The Plugin_Settings instance.
	 *
	 * @var Plugin_Settings $plugin_settings
	 */
	private readonly Plugin_Settings $plugin_settings;

	/**
	 * The directory path of the plugin, used for loading assets.
	 *
	 * @var string $plugin_dir_path
	 */
	private string $plugin_dir_path;

	/**
	 * Constructor to initialize option and transient keys.
	 *
	 * @param Plugin_Settings $plugin_settings The plugin settings instance.
	 * @param string          $plugin_dir_path The directory path of the plugin (used for asset loading).
	 */
	public function __construct( Plugin_Settings $plugin_settings, string $plugin_dir_path ) {
		$this->plugin_settings = $plugin_settings;
		$this->plugin_dir_path = $plugin_dir_path;
	}

	/**
	 * Register admin menu and submenu pages.
	 */
	public function register_menus() {
		$cap = 'manage_options';
		add_menu_page(
			'Jobs API',
			'Jobs API',
			$cap,
			'cno-jobs-api',
			array( $this, 'render_overview' ),
			'dashicons-update-alt',
			75
		);
		add_submenu_page(
			'cno-jobs-api',
			'Settings',
			'Settings',
			$cap,
			'cno-jobs-api-settings',
			array( $this, 'render_settings_page' )
		);

		// Remove the automatically added parent duplicate submenu so the menu
		// reads "Jobs API -> Settings" with a single child entry.
		remove_submenu_page( 'cno-jobs-api', 'cno-jobs-api' );
	}

	/**
	 * Enqueue admin screen assets, but only on our plugin's settings page.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function load_required_assets( string $hook_suffix ) {
		if ( 'jobs-api_page_cno-jobs-api-settings' !== $hook_suffix ) {
			return;
		}

		$asset_file        = require $this->plugin_dir_path . '/build/index.asset.php';
		$plugin_assets_url = plugin_dir_url( $this->plugin_dir_path . '/careers-plugin-jobs-api.php' );
		$asset_name        = 'cno-jobs-api-admin';
		wp_enqueue_script(
			$asset_name,
			$plugin_assets_url . 'build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			array( 'strategy' => 'defer' )
		);
		wp_add_inline_script(
			$asset_name,
			'const cnoJobsApiSettings = ' . wp_json_encode(
				array(
					'environment' => wp_get_environment_type(),
					'restBase'    => rest_url( 'cno-jobs-api/v1' ),
					'nonce'       => wp_create_nonce( 'wp_rest' ),
				)
			),
			'before'
		);
	}

	/**
	 * Render the overview page content.
	 */
	public function render_overview() {
		echo '<div class="wrap"><h1>Jobs API</h1><p>Welcome to the Jobs API plugin! Use the menu on the left to navigate to the settings page.</p></div>';
	}


	/**
	 * Render the settings page content.
	 */
	public function render_settings_page() {
		ob_start();
		require_once __DIR__ . '/settings-page-render-callback.php';
		echo ob_get_clean();
	}
}
