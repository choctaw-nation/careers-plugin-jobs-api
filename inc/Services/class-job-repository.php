<?php
/**
 * Job Repository Class
 * Interaction layer with WP for fetching and manipulating job posts.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\Services;

use ChoctawNation\Jobs_API\Data\Job_Item;
use ChoctawNation\Jobs_API\Jobs\Job_Description;
use Error;

/**
 * Job Repository Class
 */
class Job_Repository {
	/**
	 * The post type for job posts.
	 *
	 * @var string $post_type
	 */
	private string $post_type;

	/**
	 * The transient key for storing the local fetched job IDs in the database.
	 *
	 * @var string $transient_key
	 */
	private string $transient_key;

	/**
	 * The meta key for storing the requisition ID in post meta.
	 *
	 * @var string $req_id_meta_key
	 */
	private string $req_id_meta_key;

	/**
	 * The Job Description instance for building job post content.
	 *
	 * @var Job_Description $job_description_builder
	 */
	private Job_Description $job_description_builder;

	/**
	 * Constructor
	 *
	 * @param string          $post_type The post type for job posts.
	 * @param string          $transient_key The transient key for storing local fetched job IDs.
	 * @param Job_Description $job_description_builder The Job Description instance for building job post content.
	 */
	public function __construct( string $post_type, string $transient_key, Job_Description $job_description_builder ) {
		$this->post_type               = $post_type;
		$this->transient_key           = $transient_key;
		$this->req_id_meta_key         = 'requisitionId';
		$this->job_description_builder = $job_description_builder;
	}

	/**
	 * Stores fetched job IDs in a transient for later comparison when deleting old jobs.
	 *
	 * @param int[] $job_ids The array of fetched job post IDs from the API.
	 * @throws Error When there is an error storing the job IDs in the transient.
	 */
	public function store_job_ids( array $job_ids ) {
		$stored = set_transient( $this->transient_key, $job_ids, DAY_IN_SECONDS );
		if ( false === $stored ) {
			throw new Error( 'Storing job IDs in transient returned false. This means either the transient could not be set or there was no change.' );
		}
	}

	/**
	 * Gets stored job IDs from the transient.
	 */
	public function get_stored_job_ids(): array {
		return get_transient( $this->transient_key ) ?: array(); // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
	}

	/**
	 * Fetches all job posts from WordPress.
	 *
	 * @param bool   $id_only Whether to fetch only post IDs or full WP_Post objects. Default is true (fetch IDs only).
	 * @param string $status The post status to filter by (e.g., 'publish', 'draft', 'any'). Default is 'any'.
	 * @return int[]|\WP_Post[] Array of WP_Post objects or post IDs representing job posts.
	 */
	public function fetch_all_jobs( bool $id_only = true, string $status = 'any' ): array {
		return get_posts(
			array(
				'post_type'      => $this->post_type,
				'posts_per_page' => -1,
				'fields'         => $id_only ? 'ids' : 'all',
				'post_status'    => $status,
			)
		);
	}

	/**
	 * Upserts job posts in WordPress based on the provided job data. If a job post with the same requisition ID exists, it will be updated; otherwise, a new post will be created.
	 *
	 * @param Job_Item[] $jobs An array of Job_Item objects representing the job data to be upserted.
	 * @throws Error When there is an error inserting or updating a job post.
	 */
	public function upsert_jobs( array $jobs ) {
		$existing_jobs = array();
		foreach ( $this->fetch_all_jobs() as $existing_job ) {
			$req_id                   = (int) get_post_meta( $existing_job, $this->req_id_meta_key, true );
			$existing_jobs[ $req_id ] = $existing_job;
		}
		foreach ( $jobs as $job_post ) {
			$post_arr = array(
				'post_title'    => sanitize_text_field( $job_post->title ),
				'post_type'     => $this->post_type,
				'post_status'   => 'publish',
				'post_date_gmt' => $job_post->publish_job_start_date,
				'post_content'  => $this->job_description_builder->build_post_content( $job_post ),
				'post_excerpt'  => $this->job_description_builder->build_excerpt( $job_post->description_str ),
				'meta_input'    => array(
					'requisitionId' => absint( $job_post->requisition_id ),
				),
			);
			if ( array_key_exists( $job_post->requisition_id, $existing_jobs ) ) {
				$post_arr['ID'] = $existing_jobs[ $job_post->requisition_id ];
			}

			$post_id = wp_insert_post( $post_arr, true );
			if ( is_wp_error( $post_id ) ) {
				throw new Error( esc_textarea( $post_id->get_error_message() ) );
			}
			$this->add_post_taxonomies( $post_id, $job_post );
		}
	}

	/**
	 * Deletes job posts that are not in the provided list of current job IDs.
	 */
	public function delete_stale_jobs() {
		$existing_jobs    = $this->fetch_all_jobs( true, 'publish' );
		$existing_job_ids = array_map(
			function ( $job_id ) {
				$req_id = get_post_meta( $job_id, $this->req_id_meta_key, true );
				return absint( $req_id );
			},
			$existing_jobs
		);
		$current_job_ids  = $this->get_stored_job_ids();
		$stale_job_ids    = array_diff( $existing_job_ids, $current_job_ids );

		foreach ( $stale_job_ids as $job_id ) {
			wp_delete_post( $job_id, true );
		}
	}

	/**
	 * Adds the post taxonomies
	 *
	 * @param int      $post_id The post ID.
	 * @param Job_Item $job_post The job post.
	 * @return void
	 * @throws Error If there is an error inserting the term.
	 */
	private function add_post_taxonomies( int $post_id, Job_Item $job_post ): void {
		$taxonomies      = array(
			'category'      => $job_post->job_family_name,
			'location'      => $job_post->primary_location_flat_name,
			'job-details'   => array(),
			'work-location' => $job_post->primary_work_location_name,
		);
		$job_details_map = array(
			'R'         => 'Regular',
			'T'         => 'Temporary',
			'Part time' => 'Part Time',
			'Full time' => 'Full Time',
		);
		if ( ! empty( $job_post->full_part_time_name ) ) {
			if ( in_array( $job_post->full_part_time_name, array_keys( $job_details_map ), true ) ) {
				$taxonomies['job-details'][] = $job_details_map[ $job_post->full_part_time_name ];
			} else {
				$taxonomies['job-details'][] = $job_post->full_part_time_name;
			}
		}
		foreach ( $taxonomies as $taxonomy => $terms ) {
			if ( ! is_array( $terms ) ) {
				$terms = array( $terms );
			}
			foreach ( $terms as $term ) {
				if ( is_null( $term ) || 'NULL' === $term ) {
					continue;
				}
				$term_obj = term_exists( $term, $taxonomy );
				if ( ! $term_obj ) {
					$term_obj = wp_insert_term( $term, $taxonomy );
					if ( is_wp_error( $term_obj ) ) {
						throw new Error( esc_textarea( $term_obj->get_error_message() ) );
					}
				}
				$term_id = is_array( $term_obj ) ? $term_obj['term_id'] : $term_obj;
				if ( 'category' !== $taxonomy ) {
					$term_ids = wp_set_post_terms( $post_id, $term, $taxonomy, true );
				} else {
					$term_ids = wp_set_post_terms( $post_id, $term_id, $taxonomy );
				}
				if ( is_wp_error( $term_ids ) ) {
					throw new Error( esc_textarea( $term_ids->get_error_message() ) );
				}
			}
		}
	}
}
