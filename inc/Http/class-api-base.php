<?php
/**
 * API Class
 *
 * @package ChoctawNation
 * @subpackage Jobs
 */

namespace ChoctawNation\Jobs_API;

use Error;

/**
 * API Class
 */
class API_Base {
	/**
	 * The access token for the API
	 *
	 * @var string $token
	 */
	protected string $token;

	/**
	 * Constructor
	 */
	public function __construct() {
		$secrets = array(
			'CLIENT_ID',
			'CLIENT_SECRET',
			'JOBS_ENDPOINT',
		);
		foreach ( $secrets as $secret ) {
			if ( ! defined( $secret ) || empty( constant( $secret ) ) ) {
				return;
			}
		}
	}

	/**
	 * Gets the access token for authenticated endpoint use
	 *
	 * @throws Error If the request fails.
	 */
	private function get_auth() {
		$response = wp_remote_post(
			JOBS_ENDPOINT . '/oauth/token',
			array(
				'body' => array(
					'client_id'     => CLIENT_ID,
					'client_secret' => CLIENT_SECRET,
					'grant_type'    => 'client_credentials',
					'scope'         => 'testing/jobs:read',
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new Error( esc_textarea( $response->get_error_message() ) );
		}
		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );
		if ( ! is_array( $body ) ) {
			throw new Error( 'Invalid API authentication response' );
		}
		if ( empty( $body['access_token'] ) || empty( $body['token_type'] ) ) {
			throw new Error( 'Invalid API authentication response' );
		}
		return $body['token_type'] . ' ' . $body['access_token'];
	}

	/**
	 * Performs a GET request to the API with the appropriate headers to a given endpoint
	 *
	 * @param string $endpoint The endpoint to request
	 * @return array
	 * @throws Error If the request fails.
	 */
	protected function get_data( string $endpoint ): array {
		$token    = $this->get_auth();
		$response = wp_remote_get( JOBS_ENDPOINT . $endpoint, array( 'headers' => array( 'Authorization' => $token ) ) );
		if ( is_wp_error( $response ) ) {
			throw new Error( esc_textarea( $response->get_error_message() ) );
		}
		return json_decode( wp_remote_retrieve_body( $response ), true );
	}
}
