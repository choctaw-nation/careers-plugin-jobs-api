<?php
/**
 * Plugin Settings management for Jobs API.
 *
 * Provides a single structured WordPress option that stores credentials
 * for both production and staging environments, as well as the active
 * environment selector.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\WP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages plugin settings stored as a single WordPress option.
 */
class Plugin_Settings {
	/**
	 * The WordPress option key used to store all plugin settings.
	 *
	 * @var string
	 */
	public const OPTION_KEY = 'cno_jobs_api_options';

	/**
	 * The credential field keys present in each environment.
	 *
	 * @var array
	 */
	public const CREDENTIAL_FIELDS = array(
		'jobsEndpoint',
		'clientId',
		'clientSecret',
		'syncInterval',
	);

	/**
	 * Returns the default settings structure.
	 *
	 * @return array
	 */
	public function get_defaults(): array {
		$defaults                 = array_fill_keys( self::CREDENTIAL_FIELDS, '' );
		$defaults['syncInterval'] = 'daily';
		return $defaults;
	}

	/**
	 * Initialize the default option on plugin activation if it does not already exist.
	 *
	 * @return void
	 */
	public function initialize_defaults(): void {
		if ( false === get_option( self::OPTION_KEY ) ) {
			add_option( self::OPTION_KEY, $this->get_defaults() );
		}
	}

	/**
	 * Register the setting with WordPress so it can be read and validated.
	 *
	 * @return void
	 */
	public function register(): void {
		register_setting(
			self::OPTION_KEY . '_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->get_defaults(),
			)
		);
	}

	/**
	 * Return the current settings, with defaults applied for any missing keys.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		$saved = get_option( self::OPTION_KEY, $this->get_defaults() );
		return $this->merge_with_defaults( $saved );
	}

	/**
	 * Return the credentials for the currently active environment.
	 *
	 * @return array
	 */
	public function get_active_credentials(): array {
		$settings = $this->get_settings();
		return $settings ?? array_fill_keys( self::CREDENTIAL_FIELDS, '' );
	}

	/**
	 * Return the name of the currently active environment.
	 *
	 * @return string Either 'production' or 'staging'.
	 */
	public function get_active_environment(): string {
		$settings = $this->get_settings();
		return $settings['active_environment'] ?? 'production';
	}

	/**
	 * Return the WordPress option key.
	 *
	 * @return string
	 */
	public function get_option_key(): string {
		return self::OPTION_KEY;
	}

	/**
	 * Sanitize the settings array before it is stored.
	 *
	 * Validates the active_environment value and sanitizes every credential
	 * field as plain text.  Any key not present in the allowed lists is dropped.
	 *
	 * @param mixed $input The raw input to sanitize.
	 * @return array The sanitized settings.
	 */
	public function sanitize( mixed $input ): array {
		$defaults  = $this->get_defaults();
		$sanitized = $defaults;

		if ( ! is_array( $input ) ) {
			return $defaults;
		}

		foreach ( self::CREDENTIAL_FIELDS as $field ) {
			if ( array_key_exists( $field, $input ) ) {
				if ( 'jobsEndpoint' === $field ) {
					$value = sanitize_url( $input[ $field ] );
				} else {
					$value = sanitize_text_field( $input[ $field ] );
				}
				$sanitized[ $field ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Merge a saved settings array with the defaults so every expected key exists.
	 *
	 * @param mixed $settings The saved settings to merge.
	 * @return array The complete settings array.
	 */
	private function merge_with_defaults( mixed $settings ): array {
		if ( ! is_array( $settings ) ) {
			return $this->get_defaults();
		}

		$defaults = $this->get_defaults();
		$settings = array_merge( $defaults, $settings );
		return $settings;
	}
}