<?php
/**
 * Jobs API Handler
 *
 * @package ChoctawNation
 * @subpackage Jobs
 */

namespace ChoctawNation\Jobs_API;

use Error;
use WP_Error;
use WP_Filesystem_Base;

/**
 * API Class
 */
class Jobs_API extends API_Base {
	/**
	 * All Current Jobs
	 *
	 * @var \WP_Post[]|int[] $jobs
	 */
	private array $jobs;

	/**
	 * WP Filesystem handler
	 *
	 * @var WP_Filesystem_Base $fs
	 */
	private WP_Filesystem_Base $fs;

	/**
	 * The WP_Handler instance
	 *
	 * @var WP_Handler $wp
	 */
	private WP_Handler $wp;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->wp = new WP_Handler();
	}

	/**
	 * Gets the jobs from the Achukkowa DB and creates job posts
	 *
	 * @throws Error If there is an error fetching the jobs or inserting the posts.
	 */
	public function sync_job_posts() {
		try {
			$this->jobs = get_posts(
				array(
					'post_type'      => 'post',
					'posts_per_page' => -1,
				)
			);
			$this->init_wp_filesystem();
			$job_posts = $this->fetch_jobs_from_achukkowa();
			if ( is_wp_error( $job_posts ) ) {
				throw new Error( esc_textarea( $job_posts->get_error_message() ) );
			}
			$this->wp->handle_taxonomies();
			$this->store_latest_job_fetch( $job_posts );
			$this->insert_job_posts( $job_posts );
			$this->delete_old_jobs();
		} catch ( Error $e ) {
			throw new Error( esc_textarea( $e->getMessage() ) );
		}
	}

	/**
	 * Initializes the WP Filesystem
	 */
	private function init_wp_filesystem() {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$this->fs = $wp_filesystem;
	}

	/**
	 * Gets Jobs from the Achukkowa endpoint
	 *
	 * @return array|WP_Error
	 */
	private function fetch_jobs_from_achukkowa() {
		try {
			return $this->get_data( '/fusion/job-reqs' );
		} catch ( Error $e ) {
			return new WP_Error( 'api_error', esc_textarea( $e->getMessage() ) );
		}
	}

	/**
	 * Deletes jobs whose requisition IDs are not in the latest fetch
	 *
	 * @return void
	 */
	public function delete_old_jobs(): void {
		$latest_fetch = require get_stylesheet_directory() . '/inc/jobs/latest-jobs-fetch.php';
		if ( ! $latest_fetch ) {
			return;
		}
		$latest_jobs = $latest_fetch['jobs_map'];
		if ( ! empty( $this->jobs ) ) {
			foreach ( $this->jobs as $job_posts ) {
				$requisition_id = get_post_meta( $job_posts->ID, 'requisitionId', true );

				if ( ! array_key_exists( $requisition_id, $latest_jobs ) ) {
					wp_delete_post( $job_posts->ID );
				}
			}
		}
	}

	/**
	 * Stores the latest job fetch to a local file for post deletion later
	 *
	 * @param array $jobs The jobs.
	 * @return void
	 * @throws Error If there is an error storing the job data.
	 */
	private function store_latest_job_fetch( array $jobs ): void {
		$upload_dir = get_stylesheet_directory() . '/inc/jobs/';
		$file_path  = trailingslashit( $upload_dir ) . 'latest-jobs-fetch.php';
		$now        = new \DateTime( 'now', new \DateTimeZone( 'America/Chicago' ) );
		$data       = array();
		foreach ( $jobs as $job ) {
			$data[ $job['requisitionId'] ] = $job['title'];
		}
		$job_data = array(
			'date'       => $now->format( 'F j, Y, g:i a' ),
			'total_jobs' => count( $jobs ),
			'jobs_map'   => $data,
		);

		if ( ! $this->fs->exists( $file_path ) ) {
			$this->fs->put_contents( $file_path, '' );
		}
		$file_content = '<?php return ' . var_export( $job_data, true ) . ';'; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export
		$this->fs->put_contents( $file_path, $file_content, FS_CHMOD_FILE );
	}

	/**
	 * Parses the job data and creates/updates job posts in WordPress
	 *
	 * @param array $job_posts The job posts.
	 * @return void
	 * @throws Error If there is an error inserting the post.
	 */
	private function insert_job_posts( array $job_posts ): void {
		foreach ( $job_posts as $job_post ) {
			$post_arr = array(
				'post_title'    => sanitize_text_field( $job_post['title'] ),
				'post_type'     => 'post',
				'post_status'   => 'publish',
				'post_date_gmt' => $job_post['publishJobStartDate'],
				'post_content'  => $job_post['descriptionStr'],
				'meta_input'    => array(
					'requisitionId' => absint( $job_post['requisitionNumber'] ),
				),
			);

			$id = $this->get_post_id( absint( $job_post['requisitionNumber'] ) );
			if ( $id ) {
				$post_arr['ID'] = $id;
			}
			$post_id = wp_insert_post( $post_arr, true );
			if ( is_wp_error( $post_id ) ) {
				throw new Error( esc_textarea( $post_id->get_error_message() ) );
			}
			$this->add_post_taxonomies( $post_id, $job_post );
		}
	}

	/**
	 * Gets the post ID
	 *
	 * @param int $req_id The requisition ID.
	 * @return ?int
	 */
	private function get_post_id( int $req_id ): ?int {
		if ( ! empty( $this->jobs ) ) {
			foreach ( $this->jobs as $post ) {
				$meta = absint( get_post_meta( $post->ID, 'requisitionId', true ) );
				if ( $meta === $req_id ) {
					return $post->ID;
				}
			}
		}
		return null;
	}

	/**
	 * Adds the post taxonomies
	 *
	 * @param int   $post_id The post ID.
	 * @param array $job_post The job post.
	 * @return void
	 * @throws Error If there is an error inserting the term.
	 */
	private function add_post_taxonomies( int $post_id, array $job_post ): void {
		$taxonomies      = array(
			'category'    => $job_post['jobFamilyName'],
			'location'    => $job_post['primaryLocationFlatName'],
			'job-details' => array(),
		);
		$job_details_map = array(
			'R'         => 'Regular',
			'T'         => 'Temporary',
			'Part time' => 'Part Time',
			'Full time' => 'Full Time',
		);
		if ( ! empty( $job_post['fullPartTimeName'] ) ) {
			if ( in_array( $job_post['fullPartTimeName'], array_keys( $job_details_map ), true ) ) {
				$taxonomies['job-details'][] = $job_details_map[ $job_post['fullPartTimeName'] ];
			} else {
				$taxonomies['job-details'][] = $job_post['fullPartTimeName'];
			}
		}

		if ( ! empty( $job_post['jobSchedule'] ) ) {
			if ( in_array( $job_post['jobSchedule'], array_keys( $job_details_map ), true ) ) {
				$taxonomies['job-details'][] = $job_details_map[ $job_post['jobSchedule'] ];
			} else {
				$taxonomies['job-details'][] = $job_post['jobSchedule'];
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
