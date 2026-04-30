<?php
/**
 * Scheduler Class
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\WP;

/**
 * Handles scheduling of the cron job for syncing jobs from the external API.
 */
class Scheduler {
	/**
	 * Cron key
	 */
	public const FETCH_CRON_KEY = 'cno_jobs_api_fetch';

	public const DELETE_CRON_KEY = 'cno_jobs_api_delete';

	/**
	 * The schedule for the cron job.
	 *
	 * @var string $schedule
	 */
	private string $schedule;

	/**
	 * The time for the cron job.
	 *
	 * @var int $time
	 */
	private int $time;

	/**
	 * Constructor
	 *
	 * @param string $schedule The schedule for the cron job.
	 * @param string $time The time for the cron job.
	 */
	public function __construct( string $schedule, string $time ) {
		$this->schedule = $schedule;
		$now            = new \DateTime( 'now', wp_timezone() );
		$split          = explode( ':', $time );
		$hour           = (int) $split[0];
		$minute         = (int) $split[1];
		$now->setTime( $hour, $minute );
		$this->time = (int) $now->format( 'U' );
	}

	/**
	 * Schedules the cron job if not already scheduled.
	 */
	public function schedule_cron_jobs(): void {
		if ( ! wp_next_scheduled( self::FETCH_CRON_KEY ) ) {
			wp_schedule_event( $this->time, $this->schedule, self::FETCH_CRON_KEY );
		}
		$delete_time = $this->time + 3600; // Schedule delete 1 hour after fetch
		if ( ! wp_next_scheduled( self::DELETE_CRON_KEY ) ) {
			wp_schedule_event( $delete_time, $this->schedule, self::DELETE_CRON_KEY );
		}
	}
}
