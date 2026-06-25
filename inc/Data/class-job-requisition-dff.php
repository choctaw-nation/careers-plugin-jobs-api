<?php
/**
 * Job Requisition DFF DTO for one job item in the API response.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

declare(strict_types=1);
namespace ChoctawNation\Jobs_API\Data;

/**
 * Job requisition DFF DTO.
 */
final class Job_Requisition_DFF {

	use DTO_Validation;

	/**
	 * Create a Job_Requisition_DFF instance.
	 *
	 * @param int         $requisition_id Requisition ID.
	 * @param string|null $req_usage_code Requisition usage code.
	 * @param string|null $onsite_remote  Onsite/remote value.
	 */
	public function __construct(
		public int $requisition_id,
		public ?string $req_usage_code,
		public ?string $onsite_remote,
	) {}

	/**
	 * Build a Job_Requisition_DFF DTO from a payload item.
	 *
	 * @param array<string, mixed> $data Decoded requisition DFF row.
	 * @return self
	 *
	 * @throws \InvalidArgumentException When required fields are invalid.
	 */
	public static function from_array( array $data ): self {
		return new self(
			requisition_id: self::require_int_string( $data, 'requisitionId' ),
			req_usage_code: self::nullable_string( $data, 'reqUsageCode' ),
			onsite_remote: self::nullable_string( $data, 'onsiteRemote' ),
		);
	}
}
