<?php
/**
 * DTO for one job item in the API response.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

declare(strict_types=1);
namespace ChoctawNation\Jobs_API\Data;

use InvalidArgumentException;

/**
 * Job item DTO.
 */
final class Job_Item {

	use DTO_Validation;

	/**
	 * Create a Job_Item instance.
	 *
	 * @param int                                $requisition_id                 Requisition ID.
	 * @param string|null                        $requisition_number             Requisition number.
	 * @param string|null                        $title                          Job title.
	 * @param string|null                        $recruiting_type_code           Recruiting type code.
	 * @param string|null                        $publish_job_start_date         Publish start date.
	 * @param string|null                        $job_family_name                Job family name.
	 * @param string|null                        $job_grade_name                 Job grade.
	 * @param string|null                        $job_shift_name                 Job shift.
	 * @param string|null                        $full_part_time_name            Full/part time label.
	 * @param string|null                        $organization_name              Organization name.
	 * @param bool                               $requisition_valid_flag         Requisition validity flag.
	 * @param string|null                        $primary_location_flat_name     Primary location flat name.
	 * @param string|null                        $primary_work_location_name     Primary work location name.
	 * @param bool                               $travel_required_flag           Travel required flag.
	 * @param string|null                        $description_str                Description HTML/text.
	 * @param string|null                        $employer_description_str       Employer description.
	 * @param string|null                        $organization_description_str   Organization description.
	 * @param bool                               $internally_posted_flag         Internal post flag.
	 * @param bool                               $externally_posted_flag         External post flag.
	 * @param string|null                        $qualifications_str             Qualifications text.
	 * @param string|null                        $responsibilities_str           Responsibilities text.
	 * @param string|null                        $candidate_reapply_flag         Reapply flag text.
	 * @param array<int, Job_Requisition_DFF>    $requisition_dff                Requisition DFF DTOs.
	 * @param array<int, Job_Secondary_Location> $secondary_locations            Secondary location DTOs.
	 */
	public function __construct(
		public int $requisition_id,
		public ?string $requisition_number,
		public ?string $title,
		public ?string $recruiting_type_code,
		public ?string $publish_job_start_date,
		public ?string $job_family_name,
		public ?string $job_grade_name,
		public ?string $job_shift_name,
		public ?string $full_part_time_name,
		public ?string $organization_name,
		public bool $requisition_valid_flag,
		public ?string $primary_location_flat_name,
		public ?string $primary_work_location_name,
		public bool $travel_required_flag,
		public ?string $description_str,
		public ?string $employer_description_str,
		public ?string $organization_description_str,
		public bool $internally_posted_flag,
		public bool $externally_posted_flag,
		public ?string $qualifications_str,
		public ?string $responsibilities_str,
		public ?string $candidate_reapply_flag,
		public array $requisition_dff,
		public array $secondary_locations,
	) {}

	/**
	 * Build a Job_Item DTO from a payload item.
	 *
	 * @param array<string, mixed> $data Decoded job item row.
	 * @return self
	 *
	 * @throws InvalidArgumentException When required fields are invalid.
	 */
	public static function from_array( array $data ): self {
		return new self(
			requisition_id: self::require_int_string( $data, 'requisitionId' ),
			requisition_number: self::nullable_string( $data, 'requisitionNumber' ),
			title: self::nullable_string( $data, 'title' ),
			recruiting_type_code: self::nullable_string( $data, 'recruitingTypeCode' ),
			publish_job_start_date: self::nullable_string( $data, 'publishJobStartDate' ),
			job_family_name: self::nullable_string( $data, 'jobFamilyName' ),
			job_grade_name: self::nullable_string( $data, 'jobGradeName' ),
			job_shift_name: self::nullable_string( $data, 'jobShiftName' ),
			full_part_time_name: self::nullable_string( $data, 'fullPartTimeName' ),
			organization_name: self::nullable_string( $data, 'organizationName' ),
			requisition_valid_flag: self::require_bool( $data, 'requisitionValidFlag' ),
			primary_location_flat_name: self::nullable_string( $data, 'primaryLocationFlatName' ),
			primary_work_location_name: self::nullable_string( $data, 'primaryWorkLocationName' ),
			travel_required_flag: self::require_bool( $data, 'travelRequiredFlag' ),
			description_str: self::nullable_string( $data, 'descriptionStr' ),
			employer_description_str: self::nullable_string( $data, 'employerDescriptionStr' ),
			organization_description_str: self::nullable_string( $data, 'organizationDescriptionStr' ),
			internally_posted_flag: self::require_bool( $data, 'internallyPostedFlag' ),
			externally_posted_flag: self::require_bool( $data, 'externallyPostedFlag' ),
			qualifications_str: self::nullable_string( $data, 'qualificationsStr' ),
			responsibilities_str: self::nullable_string( $data, 'responsibilitiesStr' ),
			candidate_reapply_flag: self::nullable_string( $data, 'candidateReapplyFlag' ),
			requisition_dff: self::map_requisition_dff( self::array_or_empty( $data, 'requisitionDFF' ) ),
			secondary_locations: self::map_secondary_locations( self::array_or_empty( $data, 'secondaryLocations' ) ),
		);
	}

	/**
	 * Map raw requisition DFF rows into DTOs.
	 *
	 * @param array<mixed> $items Raw requisition DFF rows.
	 * @return array<int, Job_Requisition_DFF>
	 *
	 * @throws InvalidArgumentException When a row is not object-like.
	 */
	private static function map_requisition_dff( array $items ): array {
		$mapped = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				throw new InvalidArgumentException( 'Each requisition DFF item must be an object-like array.' );
			}

			$mapped[] = Job_Requisition_DFF::from_array( $item );
		}

		return $mapped;
	}

	/**
	 * Map raw secondary location rows into DTOs.
	 *
	 * @param array<mixed> $items Raw secondary location rows.
	 * @return array<int, Job_Secondary_Location>
	 *
	 * @throws InvalidArgumentException When a row is not object-like.
	 */
	private static function map_secondary_locations( array $items ): array {
		$mapped = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				throw new InvalidArgumentException( 'Each secondary location must be an object-like array.' );
			}

			$mapped[] = Job_Secondary_Location::from_array( $item );
		}

		return $mapped;
	}
}
