<?php
/**
 * Tests for Sync_Jobs
 *
 * @package ChoctawNation\Tests
 */

namespace ChoctawNation\Tests\Jobs_API;

use ChoctawNation\Jobs_API\Services\Sync_Jobs;
use WP_UnitTestCase;

/**
 * Bootstrap tests for the Sync_Jobs class.
 */
class Test_Sync_Jobs extends WP_UnitTestCase {
	/**
	 * Sync_Jobs instance under test.
	 *
	 * @var Sync_Jobs
	 */
	private Sync_Jobs $sync_jobs;

	/**
	 * Notifier mock.
	 *
	 * @var \ChoctawNation\Jobs_API\WP\Notifier|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $notifier;

	/**
	 * Job_Repository mock.
	 *
	 * @var \ChoctawNation\Jobs_API\Services\Job_Repository|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $job_repository;

	/**
	 * Api_Client mock.
	 *
	 * @var \ChoctawNation\Jobs_API\Http\Api_Client|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $api_client;

	/**
	 * Last_Load_Transient mock.
	 *
	 * @var \ChoctawNation\Jobs_API\Jobs\Last_Load_Transient|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $transient_helper;

	/**
	 * Sets up the test environment before each test method is run.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->notifier                                 = $this->createMock( \ChoctawNation\Jobs_API\WP\Notifier::class );
		$this->job_repository                           = $this->createMock( \ChoctawNation\Jobs_API\Services\Job_Repository::class );
		$this->api_client                               = $this->createMock( \ChoctawNation\Jobs_API\Http\Api_Client::class );
		$this->transient_helper                         = $this->createMock( \ChoctawNation\Jobs_API\Jobs\Last_Load_Transient::class );
		$this->transient_helper->timezone               = new \DateTimeZone( 'America/Chicago' );
		$this->transient_helper->datetime_format        = 'Y-m-d\TH:i:s.u';
		$this->transient_helper->last_load_refresh_rate = 2 * HOUR_IN_SECONDS; // 2 hours in seconds
		$this->transient_helper->transient_key          = 'jobs_last_load_timestamp';

		$this->sync_jobs = new Sync_Jobs( $this->notifier, $this->job_repository, $this->api_client, $this->transient_helper );
	}

	/**
	 * Test that the fetch_jobs method sends an email notification when an error occurs during the API fetch operation.
	 */
	public function test_fetch_jobs_sends_an_email_on_error() {
		$error_message = 'api_error: API fetch failed';
		$this->api_client->method( 'fetch_jobs' )->willThrowException( new \Error( $error_message, 500 ) );

		$this->notifier->expects( $this->once() )->method( 'send_notification' )->with(
			'Careers ORC Job Sync Failed',
			$error_message
		);

		$this->sync_jobs->fetch_jobs();
	}

	/**
	 * Test that sync_jobs does not run when last load transient is recent.
	 */
	public function test_sync_jobs_will_not_run_if_last_load_transient_is_recent() {
		// Set the last load transient to a recent time (e.g., 5 minutes ago)
		$recent_time = new \DateTimeImmutable( '-5 minutes' );
		$this->transient_helper->method( 'get_transient' )->willReturn( $recent_time );

		// Expect that fetch_jobs is never called since the last load is recent
		$this->api_client->expects( $this->never() )->method( 'fetch_jobs' );

		$this->sync_jobs->fetch_jobs();
	}

	/**
	 * Test that fetched job IDs are refreshed even if lastDataLoad is unchanged.
	 */
	public function test_fetch_jobs_stores_ids_when_last_data_load_is_unchanged() {
		$existing_last_load = new \DateTimeImmutable( '2026-07-08 00:00:00', $this->transient_helper->timezone );

		$this->transient_helper->method( 'get_transient' )->willReturn( $existing_last_load );
		$this->transient_helper->method( 'normalize_timestamp' )->willReturn( $existing_last_load->format( $this->transient_helper->datetime_format ) );

		$this->api_client->method( 'fetch_jobs' )->willReturn(
			array(
				'count'        => '0',
				'lastDataLoad' => $existing_last_load->format( $this->transient_helper->datetime_format ),
				'items'        => array(),
			)
		);

		$this->job_repository->expects( $this->once() )->method( 'store_job_ids' )->with( array() );
		$this->job_repository->expects( $this->once() )->method( 'upsert_jobs' )->with( array() );
		$this->transient_helper->expects( $this->never() )->method( 'set_transient' );

		$this->sync_jobs->fetch_jobs();
	}
}
