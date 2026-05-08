<?php
/**
 * Last Load Job Fetch Transient Class
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\Jobs;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Handles setting and getting a transient that stores the timestamp of the last successful job data load. This is used to determine if a new API fetch is needed based on the age of the data.
 */
class Last_Load_Transient {
	/**
	 * The transient key for storing the timestamp of the last successful job data load.
	 *
	 * @var string $transient_key
	 */
	private string $transient_key;

	/**
	 * The timezone for handling timestamps.
	 *
	 * @var DateTimeZone $timezone
	 */
	public DateTimeZone $timezone;

	/**
	 * The datetime format for storing and comparing timestamps.
	 *
	 * @var string $datetime_format
	 */
	public string $datetime_format;

	/**
	 * The refresh rate for the last load timestamp, in seconds. This determines how long before a new API fetch is needed.
	 *
	 * @var int $last_load_refresh_rate
	 */
	public int $last_load_refresh_rate;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->transient_key          = 'jobs_last_load_timestamp';
		$this->timezone               = new DateTimeZone( 'UTC' );
		$this->datetime_format        = 'Y-m-d\TH:i:s.u';
		$this->last_load_refresh_rate = 2 * HOUR_IN_SECONDS; // 2 hours in seconds
	}

	/**
	 * Sets the transient with the current timestamp to indicate a successful job data load.
	 *
	 * @param string $last_load The timestamp of the last load (not currently used but can be extended for future functionality).
	 */
	public function set_transient( string $last_load ): void {
		set_transient( $this->transient_key, $last_load, $this->last_load_refresh_rate );
	}

	/**
	 * Normalizes a timestamp string by removing extra microseconds digits to ensure it matches the expected datetime format.
	 *
	 * @param string $timestamp The timestamp string to normalize.
	 * @return string The normalized timestamp string.
	 */
	public function normalize_timestamp( string $timestamp ): string {
		return preg_replace(
			'/\.(\d{6})\d+/',
			'.$1',
			$timestamp
		);
	}

	/**
	 * Retrieves the timestamp of the last successful job data load from the transient.
	 *
	 * @return false|DateTimeImmutable The timestamp of the last load, or false if not set or expired.
	 */
	public function get_transient(): false|DateTimeImmutable {
		$transient = get_transient( $this->transient_key );
		if ( false === $transient ) {
			return false;
		}
		$normalized = $this->normalize_timestamp( $transient );
		return DateTimeImmutable::createFromFormat( $this->datetime_format, $normalized, $this->timezone );
	}
}
