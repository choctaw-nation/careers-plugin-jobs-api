<?php
/**
 * Tests for Scheduler
 *
 * @package ChoctawNation\Tests
 */

namespace ChoctawNation\Tests\Jobs_API;

use ChoctawNation\Jobs_API\WP\Scheduler;
use WP_UnitTestCase;

/**
 * Bootstrap tests for the Scheduler class.
 */
class Test_Scheduler extends WP_UnitTestCase {
	/**
	 * Test that the bihourly schedule is created with correct interval.
	 */
	public function test_bihourly_schedule_is_created() {
		$scheduler = new Scheduler( 'daily', '00:00' );
		$schedules = $scheduler->add_custom_cron_schedules( array() );
		$this->assertArrayHasKey( 'bihourly', $schedules );
		$this->assertEquals( 7200, $schedules['bihourly']['interval'] );
		$this->assertEquals( 'Every Two Hours', $schedules['bihourly']['display'] );
	}

	/**
	 * Test that cron jobs are scheduled for fetch and delete keys.
	 */
	public function test_cron_jobs_are_scheduled() {
		$scheduler = new Scheduler( 'daily', '00:00' );
		$scheduler->schedule_cron_jobs();
		$this->assertTrue( wp_next_scheduled( Scheduler::FETCH_CRON_KEY ) !== false );
		$this->assertTrue( wp_next_scheduled( Scheduler::DELETE_CRON_KEY ) !== false );
	}
}
