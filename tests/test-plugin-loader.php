<?php
/**
 * Tests for Plugin Loader
 *
 * @package ChoctawNation\Tests
 */

namespace ChoctawNation\Tests\Jobs_API;

use WP_UnitTestCase;

class Test_Plugin_Loader extends WP_UnitTestCase {

	public function test_that_cron_jobs_have_callbacks() {
		$this->assertTrue( has_action( \ChoctawNation\Jobs_API\WP\Scheduler::FETCH_CRON_KEY ) !== false );
		$this->assertTrue( has_action( \ChoctawNation\Jobs_API\WP\Scheduler::DELETE_CRON_KEY ) !== false );
	}
}
