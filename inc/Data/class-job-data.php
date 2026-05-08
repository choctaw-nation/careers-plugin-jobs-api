<?php
/**
 * DTO for one top-level job data object.
 *
 * Hydrate a full API response like:
 * $payload = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
 * $job_data_collection = Job_Data::collection_from_array( $payload );
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

declare(strict_types=1);

namespace ChoctawNation\Jobs_API\Data;

use InvalidArgumentException;
use Error;

/**
 * Job data DTO.
 */
final class Job_Data {

	use DTO_Validation;

	/**
	 * Create a Job_Data instance.
	 *
	 * @param int                  $count          Total result count.
	 * @param string|null          $last_data_load Last load timestamp.
	 * @param array<int, Job_Item> $items          Job item DTOs.
	 */
	public function __construct(
		public int $count,
		public ?string $last_data_load,
		public array $items,
	) {}

	/**
	 * Build a Job_Data DTO from a payload item.
	 *
	 * @param array<string, mixed> $data Decoded job data row.
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return new self(
			count: self::require_int_string( $data, 'count' ),
			last_data_load: self::nullable_string( $data, 'lastDataLoad' ),
			items: self::map_items( self::array_or_empty( $data, 'items' ) ),
		);
	}

	/**
	 * Map raw item rows into Job_Item DTOs.
	 *
	 * @param array<mixed> $items Raw job item rows.
	 * @return array<int, Job_Item>
	 *
	 * @throws Error When an item is not object-like.
	 */
	private static function map_items( array $items ): array {
		$mapped = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				throw new Error( 'Each job item must be an object-like array.' );
			}

			$mapped[] = Job_Item::from_array( $item );
		}

		return $mapped;
	}
}
