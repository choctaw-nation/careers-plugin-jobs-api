<?php
/**
 * DTO Validation Trait
 *
 * Provides common validation methods for DTO classes in the Jobs API.
 * This trait is used by Job_Item, Job_Data, Job_Requisition_DFF, and Job_Secondary_Location DTO classes to ensure consistent validation logic across all data objects.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

declare(strict_types=1);
namespace ChoctawNation\Jobs_API\Data;

use InvalidArgumentException;

trait DTO_Validation {

	/**
	 * Require an integer-formatted string and cast it to int.
	 *
	 * Accepts strings like "0", "42", or "-7".
	 *
	 * @param array<string, mixed> $data Source data.
	 * @param string               $key  Array key.
	 * @return int
	 *
	 * @throws InvalidArgumentException When the value is missing or invalid.
	 */
	protected static function require_int_string( array $data, string $key ): int {
		if ( is_int( $data[ $key ] ) ) {
			return absint( $data[ $key ] );
		}
		if ( ! array_key_exists( $key, $data ) || ! is_string( $data[ $key ] ) ) {
			throw new InvalidArgumentException( 'Missing or invalid value; expected numeric string.' );
		}

		if ( 1 !== preg_match( '/^-?(?:0|[1-9]\d*)$/', $data[ $key ] ) ) {
			throw new InvalidArgumentException( 'Invalid value; expected integer-formatted string.' );
		}

		return (int) $data[ $key ];
	}

	/**
	 * Require a string value.
	 *
	 * @param array<string, mixed> $data Source data.
	 * @param string               $key  Array key.
	 * @return string
	 *
	 * @throws InvalidArgumentException When the value is missing or invalid.
	 */
	protected static function require_string( array $data, string $key ): string {
		if ( ! array_key_exists( $key, $data ) || ! is_string( $data[ $key ] ) ) {
			throw new InvalidArgumentException( 'Missing or invalid value; expected string.' );
		}

		return $data[ $key ];
	}

	/**
	 * Require a boolean value.
	 *
	 * @param array<string, mixed> $data Source data.
	 * @param string               $key  Array key.
	 * @return bool
	 *
	 * @throws InvalidArgumentException When the value is missing or invalid.
	 */
	protected static function require_bool( array $data, string $key ): bool {
		if ( ! array_key_exists( $key, $data ) || ! is_bool( $data[ $key ] ) ) {
			throw new InvalidArgumentException( 'Missing or invalid value; expected boolean.' );
		}

		return $data[ $key ];
	}

	/**
	 * Return a nullable string value.
	 *
	 * Missing keys are treated as null.
	 *
	 * @param array<string, mixed> $data Source data.
	 * @param string               $key  Array key.
	 * @return string|null
	 *
	 * @throws InvalidArgumentException When the value is present but invalid.
	 */
	protected static function nullable_string( array $data, string $key ): ?string {
		if ( ! array_key_exists( $key, $data ) || null === $data[ $key ] ) {
			return null;
		}

		if ( ! is_string( $data[ $key ] ) ) {
			throw new InvalidArgumentException( 'Invalid value; expected string or null.' );
		}

		return $data[ $key ];
	}

	/**
	 * Require an array value.
	 *
	 * @param array<string, mixed> $data Source data.
	 * @param string               $key  Array key.
	 * @return array<mixed>
	 *
	 * @throws InvalidArgumentException When the value is missing or invalid.
	 */
	protected static function require_array( array $data, string $key ): array {
		if ( ! array_key_exists( $key, $data ) || ! is_array( $data[ $key ] ) ) {
			throw new InvalidArgumentException( 'Missing or invalid value; expected array.' );
		}

		return $data[ $key ];
	}

	/**
	 * Return an array value or an empty array when missing.
	 *
	 * @param array<string, mixed> $data Source data.
	 * @param string               $key  Array key.
	 * @return array<mixed>
	 *
	 * @throws InvalidArgumentException When the value is present but invalid.
	 */
	protected static function array_or_empty( array $data, string $key ): array {
		if ( ! array_key_exists( $key, $data ) || null === $data[ $key ] ) {
			return array();
		}

		if ( ! is_array( $data[ $key ] ) ) {
			throw new InvalidArgumentException( 'Invalid value; expected array.' );
		}

		return $data[ $key ];
	}
}
