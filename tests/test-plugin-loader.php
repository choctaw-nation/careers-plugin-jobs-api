<?php
/**
 * Tests for Plugin_Loader
 *
 * @package ChoctawNation\Tests
 */

namespace ChoctawNation\Tests\Jobs_API;

use ChoctawNation\Jobs_API\Plugin_Loader;
use WP_UnitTestCase;

/**
 * Bootstrap tests for the Plugin_Loader class.
 */
class Test_Plugin_Loader extends WP_UnitTestCase {
	private Plugin_Loader $instance;
	public function set_up(): void {
		parent::set_up();
		$this->instance = new Plugin_Loader( __DIR__ );
	}
}