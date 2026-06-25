<?php
/**
 * Tests for Api_Client
 *
 * @package ChoctawNation\Tests
 */

namespace ChoctawNation\Tests\Jobs_API;

use ChoctawNation\Jobs_API\Http\Api_Client;
use WP_UnitTestCase;

/**
 * Bootstrap tests for the Api_Client class.
 */
class Test_Api_Client extends WP_UnitTestCase {
	private Api_Client $api_client;

	public function set_up() {
		parent::set_up();
		$settings         = array(
			'jobsEndpoint' => 'https://api.example.com/v1',
			'clientId'     => 'test-client-id',
			'clientSecret' => 'test-client-secret',
		);
		$notifier         = $this->createMock( \ChoctawNation\Jobs_API\WP\Notifier::class );
		$this->api_client = new Api_Client( $settings, $notifier );
	}

	public function test_auth_failure_is_caught() {

		$this->api_client->fetch_jobs()
	}
}
