<?php
/**
 * Sync Job Class
 * Orchestrates sync between WordPress and ORC databases
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\Services;

use ChoctawNation\Jobs_API\Data\Job_Data;
use ChoctawNation\Jobs_API\Data\Job_Item;
use ChoctawNation\Jobs_API\Http\Api_Client;
use ChoctawNation\Jobs_API\Jobs\Last_Load_Transient;
use ChoctawNation\Jobs_API\WP\Notifier;
use DateTimeImmutable;
use Error;
use Throwable;

/**
 * Handles the synchronization of job data between the external API and the local WordPress database. This includes fetching jobs from the API, comparing them with local jobs, updating/creating as needed, and deleting local jobs that are no longer present in the API.
 */
class Sync_Jobs {
	/**
	 * Notifier instance for sending notifications about sync operations.
	 *
	 * @var Notifier $notifier
	 */
	private Notifier $notifier;

	/**
	 * Job Repository instance for interacting with WordPress job posts.
	 *
	 * @var Job_Repository $job_repository
	 */
	private Job_Repository $job_repository;

	/**
	 * API Client instance for fetching jobs from the external API.
	 *
	 * @var Api_Client $api
	 */
	private Api_Client $api;

	/**
	 * Last Load Transient instance for managing the transient that stores the timestamp of the last successful data load.
	 *
	 * @var Last_Load_Transient $transient_helper
	 */
	private Last_Load_Transient $transient_helper;

	/**
	 * Constructor
	 *
	 * @param Notifier            $notifier The Notifier instance for sending notifications about sync operations.
	 * @param Job_Repository      $job_repository The Job Repository instance for interacting with WordPress job posts.
	 * @param Api_Client          $api_client The API Client instance for fetching jobs from the external API
	 * @param Last_Load_Transient $transient_helper The Last Load Transient instance for managing the transient that stores the timestamp of the last successful data load.
	 */
	public function __construct( Notifier $notifier, Job_Repository $job_repository, Api_Client $api_client, Last_Load_Transient $transient_helper ) {
		$this->notifier         = $notifier;
		$this->job_repository   = $job_repository;
		$this->api              = $api_client;
		$this->transient_helper = $transient_helper;
	}

	/**
	 * Fetches jobs from the external API, compares with local jobs, and updates/creates as needed.
	 * Also stores fetched job IDs in transient for later comparison when deleting.
	 *
	 * @throws Error When the API fetch fails or returns an error.
	 */
	public function fetch_jobs() {
		$last_load = $this->transient_helper->get_transient();
		$now       = new DateTimeImmutable( 'now', $this->transient_helper->timezone );
		if ( $last_load && ( $now->getTimestamp() - $last_load->getTimestamp() < $this->transient_helper->last_load_refresh_rate ) ) {
			// Data is still fresh, no need to fetch again.
			return;
		}
		try {
			$job_posts = $this->api->fetch_jobs();
			if ( is_wp_error( $job_posts ) ) {
				throw new Error( esc_textarea( $job_posts->get_error_message() ) );
			}
			$job_data = Job_Data::from_array( $job_posts );
			if ( ! $last_load || $this->transient_helper->normalize_timestamp( $job_data->last_data_load ) !== $last_load->format( $this->transient_helper->datetime_format ) ) {
				// Achukkowa server has timestamp (and thus probably new data).
				// Update transient
				$this->transient_helper->set_transient( $job_data->last_data_load );
			}
			// Refresh fetched job IDs after every successful API read so delete logic
			// does not operate on expired transient data.
			$this->store_job_ids( $job_data->items );
			$this->job_repository->upsert_jobs( $job_data->items );
		} catch ( Error $e ) {
			$this->notifier->send_notification( 'Careers ORC Job Sync Failed', $e->getMessage() );
			return;
		}
	}

	/**
	 * Gets the IDs of the fetched job posts from the API and stores them in a transient for later comparison when deleting old jobs.
	 *
	 * @param Job_Item[] $jobs The array of fetched job items from the API.
	 */
	private function store_job_ids( array $jobs ) {
		$fetched_ids = array_map(
			fn( Job_Item $job ) => (int) $job->requisition_number,
			$jobs
		);
		try {
			$this->job_repository->store_job_ids( $fetched_ids );
		} catch ( Throwable $e ) {
			$this->notifier->send_notification( 'Careers ORC Job Sync Failed', 'Failed to store job IDs in transient: ' . $e->getMessage() );
		}
	}

	/**
	 * Deletes local jobs that are no longer present in the external API based on comparison with transient stored IDs.
	 */
	public function delete_jobs() {
		$this->job_repository->delete_stale_jobs();
	}
}
