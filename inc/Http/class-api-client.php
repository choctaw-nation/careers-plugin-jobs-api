<?php
/**
 * API Client
 * Class that actually fetches the data
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\Http;

use ChoctawNation\Jobs_API\WP\Notifier;
use Error;

/**
 * API Client class responsible for making HTTP requests to the external API and fetching job data. This class should handle authentication, error handling, and return the decoded response for further processing.
 */
class Api_Client {
	/**
	 * Notifier instance for sending notifications about API operations.
	 *
	 * @var Notifier $notifier
	 */
	private Notifier $notifier;

	/**
	 * The API endpoint URL
	 *
	 * @var string $endpoint_base
	 */
	private string $endpoint_base;

	/**
	 * The client ID for API authentication
	 *
	 * @var string $client_id
	 */
	private string $client_id;

	/**
	 * The client secret for API authentication
	 *
	 * @var string $client_secret
	 */
	private string $client_secret;

	/**
	 * Constructor
	 *
	 * @param array<string, mixed> $settings An array of settings required for the API client, such as API endpoint, client ID, and client secret.
	 * @param Notifier             $notifier An instance of the Notifier class for sending notifications about API operations.
	 */
	public function __construct( array $settings, Notifier $notifier ) {
		$this->endpoint_base = untrailingslashit( $settings['jobsEndpoint'] );
		$this->client_id     = $settings['clientId'];
		$this->client_secret = $settings['clientSecret'];
		$this->notifier      = $notifier;
	}

	/**
	 * Gets the access token for authenticated endpoint use
	 *
	 * @throws Error If the request fails.
	 */
	private function get_auth(): string {
		try {
			$response = wp_remote_post(
				$this->endpoint_base . '/oauth/token',
				array(
					'body' => array(
						'client_id'     => $this->client_id,
						'client_secret' => $this->client_secret,
						'grant_type'    => 'client_credentials',
						'scope'         => 'testing/jobs:read',
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				throw new Error( esc_textarea( $response->get_error_message() ) );
			}
			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				throw new Error( 'API authentication request failed with response code ' . wp_remote_retrieve_response_code( $response ) . ' and message: ' . wp_remote_retrieve_response_message( $response ) );
			}
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );
			if ( ! is_array( $body ) ) {
				throw new Error( "Couldn't decode API authentication response" );
			}
			if ( empty( $body['access_token'] ) || empty( $body['token_type'] ) ) {
				throw new Error( 'Body access_token or token_type missing!' );
			}
			return $body['token_type'] . ' ' . $body['access_token'];
		} catch ( Error $e ) {
			throw new Error( esc_textarea( 'Careers ORC API GET Authentication Token Failure: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Fetches the jobs from the API and returns the decoded response.
	 *
	 * @return ?array<string, mixed> Decoded API response.
	 * @throws Error If the request fails.
	 */
	public function fetch_jobs(): ?array {
		try {
			$token    = $this->get_auth();
			$response = wp_remote_get( $this->endpoint_base . '/fusion/job-reqs', array( 'headers' => array( 'Authorization' => $token ) ) );
			if ( is_wp_error( $response ) ) {
				throw new Error( esc_textarea( $response->get_error_message() ) );
			}
			if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
				throw new Error( 'API request failed with response code ' . wp_remote_retrieve_response_code( $response ) . ' and message: ' . wp_remote_retrieve_response_message( $response ) );
			}
			$body = wp_remote_retrieve_body( $response );
			if ( empty( $body ) ) {
				throw new Error( 'Empty response body from API' );
			}
			return json_decode( $body, true, 512, JSON_THROW_ON_ERROR );
		} catch ( Error $e ) {
			$this->notifier->send_notification( 'Careers ORC API GET Request Failed', $e->getMessage() );
			return null;
		}
	}
}
