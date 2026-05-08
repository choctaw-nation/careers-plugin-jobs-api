<?php
/**
 * Job Secondary Location DTO for one secondary location item in the API response.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

declare(strict_types=1);
namespace ChoctawNation\Jobs_API\Data;

/**
 * Job secondary location DTO.
 */
final class Job_Secondary_Location {

	use DTO_Validation;

	/**
	 * Create a Job_Secondary_Location instance.
	 *
	 * @param int    $requisition_location_id Requisition location ID.
	 * @param int    $geography_node_id       Geography node ID.
	 * @param string $location_flat_name      Flat location name.
	 */
	public function __construct(
		public int $requisition_location_id,
		public int $geography_node_id,
		public string $location_flat_name,
	) {}

	/**
	 * Build a Job_Secondary_Location DTO from a payload item.
	 *
	 * @param array<string, mixed> $data Decoded secondary location row.
	 * @return self
	 *
	 * @throws \InvalidArgumentException When required fields are invalid.
	 */
	public static function from_array( array $data ): self {
		return new self(
			requisition_location_id: self::require_int_string( $data, 'requisitionLocationId' ),
			geography_node_id: self::require_int_string( $data, 'geographyNodeId' ),
			location_flat_name: self::require_string( $data, 'locationFlatName' ),
		);
	}
}
