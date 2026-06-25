<?php
/**
 * Tests for Last_Load_Transient
 *
 * @package ChoctawNation\Tests
 */

namespace ChoctawNation\Tests\Jobs_API;

use ChoctawNation\Jobs_API\Jobs\Last_Load_Transient;
use WP_UnitTestCase;

/**
 * Bootstrap tests for the Last_Load_Transient class.
 */
class Test_Last_Load_Transient extends WP_UnitTestCase {

	/**
	 * Last_Load_Transient instance under test.
	 *
	 * @var Last_Load_Transient
	 */
	private Last_Load_Transient $transient;

	/**
	 * Set up the test environment.
	 */
	public function set_up() {
		parent::set_up();
		$this->transient = new Last_Load_Transient();
	}

	/**
	 * Tear down the test environment.
	 */
	public function tear_down() {
		parent::tear_down();
		delete_transient( $this->transient->transient_key );
	}

	/**
	 * Test normalization removes extra microseconds from timestamp strings.
	 */
	public function test_normalize_timestamp_properly_removes_extra_microseconds() {
		$timestamp  = '2024-06-01T12:34:56.7891234';
		$normalized = $this->transient->normalize_timestamp( $timestamp );
		$this->assertEquals( '2024-06-01T12:34:56.789123', $normalized );
	}

	/**
	 * Test that the transient returns a DateTimeImmutable object when set.
	 */
	public function test_transient_returns_datetimeimmutable_object() {
		$timestamp = '2024-06-01T12:34:56.7891234';
		set_transient( $this->transient->transient_key, $timestamp, $this->transient->last_load_refresh_rate );
		$datetime = $this->transient->get_transient();
		$this->assertInstanceOf( \DateTimeImmutable::class, $datetime );
		$this->assertEquals( '2024-06-01 12:34:56.789123', $datetime->format( 'Y-m-d H:i:s.u' ) );
	}

	/**
	 * Test that get_transient returns false when no transient is set.
	 */
	public function test_transient_returns_false_when_not_set() {
		$datetime = $this->transient->get_transient();
		$this->assertFalse( $datetime );
	}
}
