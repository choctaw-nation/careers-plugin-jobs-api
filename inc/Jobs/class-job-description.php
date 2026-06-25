<?php
/**
 * Job Description Class
 * Responsible for building the post content for job posts.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API\Jobs;

use ChoctawNation\Jobs_API\Data\Job_Item;

/**
 * Job Description
 */
class Job_Description {
	/**
	 * Builds the post content for a job post.
	 *
	 * @param Job_Item $job_post The job post data.
	 * @return string The built post content.
	 */
	public function build_post_content( Job_Item $job_post ): string {
		$content = '';

		$content .= $this->build_job_description( $job_post->description_str );
		$content .= $this->build_job_responsibilities_list( $job_post->responsibilities_str );
		$content .= $this->build_job_qualifications_list( $job_post->qualifications_str );
		$content .= $this->build_organization_description( $job_post->organization_description_str );
		return $content;
	}

	/**
	 * Extracts a plain text excerpt from the job description to be used as the post excerpt.
	 *
	 * @param string $html The job description HTML.
	 * @return string The extracted excerpt text.
	 */
	public function build_excerpt( string $html ): string {
		if ( preg_match_all( '/<p\b[^>]*>(.*?)<\/p>/is', $html, $paragraphs ) ) {
			foreach ( $paragraphs[0] as $paragraph_html ) {
				$text = html_entity_decode(
					wp_strip_all_tags( $paragraph_html ),
					ENT_QUOTES | ENT_HTML5,
					'UTF-8'
				);

				$text = preg_replace( '/\s+/u', ' ', $text );
				$text = trim( $text );

				if ( ! str_contains( $text, 'Job Purpose or Objective' ) ) {
					continue;
				}

				return trim(
					preg_replace(
						'/^.*?Job Purpose or Objective\s*\(s\):\s*/i',
						'',
						$text
					)
				);
			}
		}

		return '';
	}

	/**
	 * Builds the job description section of the post content.
	 *
	 * @param string $description The job description text, which may contain HTML.
	 * @return string The built job description section.
	 */
	private function build_job_description( string $description ): string {
		if ( empty( trim( wp_strip_all_tags( $description ) ) ) ) {
			return '';
		}

		$description = $this->remove_duplicate_description_sections( $description );
		$description = $this->strip_description_attributes( $description );

		if ( empty( trim( wp_strip_all_tags( $description ) ) ) ) {
			return '';
		}

		return sprintf(
			'<section class="py-0"><h2>Job Description</h2>%s</section>',
			wp_kses_post( $description )
		);
	}

	/**
	 * Removes duplicate sections from the job description that may have been included in the original description text. This is to prevent redundancy when the responsibilities and qualifications are also included as separate sections.
	 *
	 * @param string $description The original job description text.
	 * @return string The cleaned job description text with duplicate sections removed.
	 */
	private function remove_duplicate_description_sections( string $description ): string {
		$description = preg_replace(
			'/<p\b[^>]*>\s*(?:<[^>]+>\s*)*Primary\s+Tasks?:?.*?<\/p>.*?(?=<p\b[^>]*>\s*(?:<[^>]+>\s*)*(?:Job\s+Requirements?|Requirements?|Minimum(?:\s+Requirements)?|Minimum|Required\s+Education(?:,\s*Skills)?\s+and\s+Experience):?\s*(?:<\/[^>]+>\s*)*<\/p>|<h[1-6]\b|$)/isu',
			'',
			$description
		) ?? $description;

		$description = preg_replace(
			'/<p\b[^>]*>\s*(?:<[^>]+>\s*)*(?:Job\s+Requirements?|Requirements?|Minimum(?:\s+Requirements)?|Minimum|Required\s+Education(?:,\s*Skills)?\s+and\s+Experience):?.*$/isu',
			'',
			$description
		) ?? $description;

		return preg_replace(
			'/<h[1-6]\b[^>]*>\s*(?:&nbsp;|\s)*<\/h[1-6]>/isu',
			'',
			$description
		) ?? $description;
	}

	/**
	 * Strips unwanted attributes from the job description HTML to ensure cleaner and more consistent formatting in the post content.
	 *
	 * @param string $description The original job description HTML.
	 * @return string The cleaned job description HTML with unwanted attributes removed.
	 */
	private function strip_description_attributes( string $description ): string {
		$processor = new \WP_HTML_Tag_Processor( $description );

		while ( $processor->next_tag() ) {
			$processor->remove_attribute( 'style' );
			$processor->remove_attribute( 'class' );
			$processor->remove_attribute( 'data-teams' );
			$processor->remove_attribute( 'data-pm-slice' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Builds the qualifications section of the post content as a list.
	 *
	 * @param string $qualifications The qualifications text, expected to be separated by double newlines for list items.
	 * @return string The built qualifications section with a list.
	 */
	private function build_job_qualifications_list( string $qualifications ): string {
		return $this->build_section_list( 'Job Requirements', $qualifications );
	}

	/**
	 * Builds the responsibilities section of the post content as a list.
	 *
	 * @param string $responsibilities The responsibilities text, expected to be separated by double newlines for list items.
	 * @return string The built responsibilities section with a list.
	 */
	private function build_job_responsibilities_list( string $responsibilities ): string {
		return $this->build_section_list( 'Primary Tasks', $responsibilities );
	}

	/**
	 * Builds the organization description section of the post content.
	 *
	 * @param string $organization_description The organization description text.
	 * @return string The built organization description section.
	 */
	private function build_organization_description( string $organization_description ): string {
		if ( empty( $organization_description ) ) {
			return '';
		}
		return sprintf( '<aside><h2>About the Choctaw Nation of Oklahoma</h2>%s</aside>', wp_kses_post( $organization_description ) );
	}

	/**
	 * Builds a section with a heading and either a list or a paragraph based on the content.
	 *
	 * @param string $heading The heading for the section.
	 * @param string $content The content for the section, which may contain list items separated by double newlines or HTML list tags.
	 */
	private function build_section_list( string $heading, string $content ): string {
		if ( empty( trim( wp_strip_all_tags( $content ) ) ) ) {
			return '';
		}

		$items = $this->extract_list_items( $content );

		if ( empty( $items ) ) {
			return sprintf(
				'<section class="py-0 mb-3"><h2>%s</h2><p>%s</p></section>',
				esc_html( $heading ),
				esc_html( trim( wp_strip_all_tags( $content ) ) )
			);
		}

		$list_items = '';

		foreach ( $items as $item ) {
			$list_items .= '<li>' . esc_html( $item ) . '</li>';
		}

		return sprintf(
			'<section class="py-0 mb-3"><h2>%s</h2><ul>%s</ul></section>',
			esc_html( $heading ),
			$list_items
		);
	}

	/**
	 * Extracts list items from the given content, handling both HTML lists and plain text separated by newlines.
	 *
	 * @param string $content The content to extract list items from.
	 */
	private function extract_list_items( string $content ): array {
		$content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );

		if ( str_contains( $content, '<li' ) ) {
			preg_match_all( '/<li\b[^>]*>(.*?)<\/li>/isu', $content, $matches );
			$items = $matches[1] ?? array();
		} elseif ( str_contains( $content, '<p' ) ) {
			preg_match_all( '/<p\b[^>]*>(.*?)<\/p>/isu', $content, $matches );
			$items = $matches[1] ?? array();
		} else {
			$items = preg_split( "/\n+/", $content ) ?: array(); // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
		}

		$items = array_map(
			function ( string $item ): string {
				$item = trim( wp_strip_all_tags( $item ) );
				$item = preg_replace( '/^\s*(?:\d+[\.\)]|[•·\-])\s*/u', '', $item );
				$item = preg_replace( '/\s+/u', ' ', $item );

				return trim( $item ?? '' );
			},
			$items
		);

		return array_values( array_filter( $items ) );
	}
}
