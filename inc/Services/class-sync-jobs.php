<?php
/**
 * Sync Job Class
 * Orchestrates sync between WordPress and ORC databases
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\Services;

/**
 * Handles the synchronization of job data between the external API and the local WordPress database. This includes fetching jobs from the API, comparing them with local jobs, updating/creating as needed, and deleting local jobs that are no longer present in the API.
 */
class Sync_Jobs {
	/**
	 * The transient key for storing the local fetched job IDs in the database.
	 *
	 * @var string $transient_key
	 */
	private string $transient_key;

	/**
	 * Constructor
	 *
	 * @param string $transient_key The transient key for storing the local fetched job IDs in the database.
	 */
	public function __construct( string $transient_key ) {
		$this->transient_key = $transient_key;
	}

	/**
	 * Fetches jobs from the external API, compares with local jobs, and updates/creates as needed. Also stores fetched job IDs in transient for later comparison when deleting.
	 */
	public function fetch_jobs() {
		// 1.) fetch jobs from external API
		// 2.) compare with local jobs and update/create as needed
		// 3.) store fetched job IDs in transient for later comparison when deleting
	}

	/**
	 * Deletes local jobs that are no longer present in the external API based on comparison with transient stored IDs.
	 */
	public function delete_jobs() {
		// 1.) get local jobs and compare with transient stored IDs
		// 2.) delete local jobs that are not in the transient stored IDs
	}
}