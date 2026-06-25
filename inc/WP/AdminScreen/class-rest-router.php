<?php
/**
 * REST Controller for Jobs API Settings.
 *
 * Provides GET and POST endpoints at `cno-jobs-api/v1/settings` so that
 * the React admin app can read and persist plugin credentials.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\WP\AdminScreen;

use ChoctawNation\Jobs_API\WP\Plugin_Settings;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles REST API endpoints for plugin settings management.
 */
class Rest_Router extends WP_REST_Controller {

	/**
	 * The Plugin_Settings instance.
	 *
	 * @var Plugin_Settings $plugin_settings
	 */
	private Plugin_Settings $plugin_settings;

	/**
	 * Constructor.
	 *
	 * @param Plugin_Settings $plugin_settings The settings manager instance.
	 */
	public function __construct( Plugin_Settings $plugin_settings ) {
		$this->namespace       = 'cno-jobs-api/v1';
		$this->plugin_settings = $plugin_settings;
	}

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * Verify the current user has the `manage_options` capability.
	 *
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function permissions_check(): bool|WP_Error {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				'You do not have permission to manage these settings.',
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Return the current settings with sensitive field values masked.
	 *
	 * Sensitive fields are returned as the masked placeholder when they
	 * contain a saved value, so actual credentials are never exposed.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response
	 */
	public function get_settings( WP_REST_Request $request ): WP_REST_Response {
		$settings             = $this->plugin_settings->get_settings();
		$time                 = $settings['syncTime'];
		$rest_time            = array(
			'hours'   => (int) gmdate( 'H', strtotime( $time ) ),
			'minutes' => (int) gmdate( 'i', strtotime( $time ) ),
		);
		$settings['syncTime'] = $rest_time;
		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Persist updated settings.
	 *
	 * Incoming sensitive fields that carry the masked placeholder are left
	 * unchanged in storage (the sanitize callback handles this).
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$body = $request->get_json_params();

		if ( ! is_array( $body ) || empty( $body ) ) {
			return new WP_Error(
				'invalid_request',
				'Request body is empty or not valid JSON.',
				array( 'status' => 400 )
			);
		}

		$sanitized = $this->plugin_settings->sanitize( $body );
		$updated   = update_option( $this->plugin_settings->get_option_key(), $sanitized );

		// update_option returns false both on DB error AND when the value has
		// not changed; treat an unchanged value as success.
		if ( false === $updated ) {
			$current = $this->plugin_settings->get_settings();
			if ( $current !== $sanitized ) {
				return new WP_Error(
					'update_failed',
					'Failed to update settings.',
					array( 'status' => 500 )
				);
			}
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'message'  => 'Settings updated successfully.',
				'settings' => $sanitized,
			),
			200
		);
	}
}
