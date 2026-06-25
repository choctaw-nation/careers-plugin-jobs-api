<?php
/**
 * Tests for Job_Repository
 *
 * @package ChoctawNation\Tests
 */

namespace ChoctawNation\Tests\Jobs_API;

use ChoctawNation\Jobs_API\Services\Job_Repository;
use WP_UnitTestCase;

/**
 * Bootstrap tests for the Job_Repository class.
 */
class Test_Job_Repository extends WP_UnitTestCase {
	/**
	 * Job_Repository instance under test.
	 *
	 * @var Job_Repository
	 */
	private Job_Repository $job_repository;

	/**
	 * Set up the test environment.
	 */
	public function set_up(): void {
		parent::set_up();
		$this->job_repository = new Job_Repository( 'post', 'choctaw_jobs_fetched_ids', $this->createMock( \ChoctawNation\Jobs_API\Jobs\Job_Description::class ) );
	}

	/**
	 * Tear down the test environment.
	 */
	public function tear_down(): void {
		delete_transient( $this->job_repository->transient_key );
		parent::tear_down();
	}

	/**
	 * Test that stale jobs are deleted from the repository.
	 */
	public function test_stale_jobs_are_deleted() {
		// Create a job post to simulate an existing job.
		$jobs_to_create      = 10; // Total jobs to create.
		$existing_job_ids    = $this->factory->post->create_many(
			$jobs_to_create,
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Existing Job',
			)
		);
		$existing_job_req_id = $jobs_to_create + 1; // Start the requisition ID after the created jobs.
		foreach ( $existing_job_ids as $job_id ) {
			update_post_meta( $job_id, $this->job_repository->req_id_meta_key, $existing_job_req_id );
			++$existing_job_req_id;
		}

		$n_jobs_to_remove = 5; // Number of jobs to remove.
		$this->prep_n_jobs_to_delete( $n_jobs_to_remove, $existing_job_ids );

		// Call the method to delete stale jobs.
		$this->job_repository->delete_stale_jobs();

		// Assert that the existing job post has been deleted.
		$remaining_posts = get_posts(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'fields'      => 'ids',
			)
		);
		$diff            = $jobs_to_create - $n_jobs_to_remove;
		$this->assertEquals( $diff, count( $remaining_posts ), 'The number of remaining job posts does not match the expected count after deletion.' );
	}

	/**
	 * Prepare N jobs to be flagged as deleted and store their requisition IDs.
	 *
	 * @param int   $n       Number of jobs to select for deletion.
	 * @param array $job_ids Array of job post IDs.
	 *
	 * @return array List of job IDs selected for deletion.
	 */
	private function prep_n_jobs_to_delete( int $n, array $job_ids ): array {
		$random_keys    = array_rand( $job_ids, $n );
		$jobs_to_delete = array_map( fn( $key ) => $job_ids[ $key ], $random_keys );
		$req_ids        = array_map(
			function ( $job_id ) {
				$req_id = get_post_meta( $job_id, $this->job_repository->req_id_meta_key, true );
				if ( ! is_numeric( $req_id ) ) {
					throw new \Exception( 'Requisition ID for job ID ' . (int) $job_id . ' is not numeric.' );
				}
				return (int) $req_id;
			},
			$jobs_to_delete
		);
		$this->job_repository->store_job_ids( $req_ids ); // Store the remaining job IDs in the transient.
		return $jobs_to_delete;
	}
}
