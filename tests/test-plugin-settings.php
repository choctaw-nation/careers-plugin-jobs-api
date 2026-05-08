<?php
/**
 * Tests for Plugin_Settings
 *
 * @package ChoctawNation\Tests
 */

namespace ChoctawNation\Tests\Jobs_API;

use ChoctawNation\Jobs_API\WP\Plugin_Settings;
use WP_UnitTestCase;

/**
 * Tests for the Plugin_Settings settings management class.
 */
class Test_Plugin_Settings extends WP_UnitTestCase {

	/**
	 * Plugin_Settings instance under test.
	 *
	 * @var Plugin_Settings
	 */
	private Plugin_Settings $settings;

	/**
	 * Set up fresh instance and clear the option before each test.
	 */
	public function set_up() {
		parent::set_up();
		$this->settings = new Plugin_Settings();
	}

	/**
	 * Tear down: remove the option after each test.
	 */
	public function tear_down() {
		delete_option( Plugin_Settings::OPTION_KEY );
		parent::tear_down();
	}

	/**
	 * The option key constant must equal the expected value.
	 */
	public function test_option_key_constant() {
		$this->assertSame( 'cno_jobs_api_options', Plugin_Settings::OPTION_KEY );
	}

	/**
	 * Get defaults returns a complete structure with 'production' active and all fields empty.
	 */
	public function test_get_defaults_returns_expected_structure() {
		$defaults = $this->settings->get_defaults();

		$this->assertIsArray( $defaults );
		foreach ( Plugin_Settings::CREDENTIAL_FIELDS as $field ) {
			if ( ! in_array( $field, array( 'syncInterval', 'syncTime' ), true ) ) {
				$this->assertArrayHasKey( $field, $defaults );
				$this->assertSame( '', $defaults[ $field ] );
			}
		}
		$this->assertSame( 'daily', $defaults['syncInterval'] );
		$this->assertSame( '00:00', $defaults['syncTime'] );
	}

	/**
	 * Initialize_defaults adds the option if it does not exist.
	 */
	public function test_initialize_defaults_creates_option_when_missing() {
		$this->assertFalse( get_option( Plugin_Settings::OPTION_KEY ) );

		$this->settings->initialize_defaults();

		$saved = get_option( Plugin_Settings::OPTION_KEY );
		$this->assertIsArray( $saved );
		$this->assertSame( '00:00', $saved['syncTime'] );
	}

	/**
	 * Merging defaults does not override existing values when initializing defaults.
	 */
	public function test_merging_defaults_does_not_override_existing_values() {
		update_option(
			Plugin_Settings::OPTION_KEY,
			array(
				'clientId' => 'existing-client-id',
			)
		);

		$saved = $this->settings->get_settings();
		$this->assertIsArray( $saved );
		$this->assertSame( 'existing-client-id', $saved['clientId'] );
	}

	/**
	 * Initialize_defaults does NOT overwrite an existing option.
	 */
	public function test_initialize_defaults_does_not_overwrite_existing_option() {
		$existing = array(
			'clientId' => 'staging',
		);
		update_option( Plugin_Settings::OPTION_KEY, $existing );

		$this->settings->initialize_defaults();

		$saved = get_option( Plugin_Settings::OPTION_KEY );
		$this->assertSame( 'staging', $saved['clientId'] );
	}
}
