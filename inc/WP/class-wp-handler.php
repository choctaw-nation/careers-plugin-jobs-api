<?php
/**
 * WP Handler Class
 * Class to handle the WordPress-specific functionality for the Jobs API.
 *
 * @package ChoctawNation
 * @subpackage Jobs
 */

namespace ChoctawNation\Jobs_API;

/**
 * WP Handler Class
 * This class initializes the Jobs API and registers necessary post types and taxonomies.
 *
 * @package ChoctawNation
 * @subpackage Jobs
 */
class WP_Handler {
	/**
	 * The taxonomy slugs
	 *
	 * @var array $taxonomies
	 */
	public $taxonomies = array(
		'job-details',
		'locations',
	);
	/**
	 * Constructor
	 * Initializes the WordPress handler.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'alter_post_labels' ) );
		$this->handle_taxonomies();
	}

	/**
	 * Alters the default post type
	 */
	public function alter_post_labels() {
		$this->update_labels();
	}

	/**
	 * Updates the labels for the default post type
	 */
	private function update_labels() {
		$labels                     = get_post_type_labels( get_post_type_object( 'post' ) );
		$labels->name               = 'Jobs';
		$labels->singular_name      = 'Job';
		$labels->add_new            = 'Add New Job';
		$labels->add_new_item       = 'Add New Job';
		$labels->edit_item          = 'Edit Job';
		$labels->new_item           = 'New Job';
		$labels->view_item          = 'View Job';
		$labels->view_items         = 'View Jobs';
		$labels->search_items       = 'Search Jobs';
		$labels->not_found          = 'No Jobs found';
		$labels->not_found_in_trash = 'No Jobs found in trash';
		$labels->all_items          = 'All Jobs';
		$labels->menu_name          = 'Jobs';
		$labels->name_admin_bar     = 'Jobs';
		$args                       = get_post_type_object( 'post' );
		$args->labels               = $labels;
	}

	/**
	 * Registers the needed taxonomies for the jobs.
	 */
	public function handle_taxonomies() {
		foreach ( $this->taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				add_action( 'init', array( $this, 'register_taxonomies' ) );
			}
		}
	}

	/**
	 * Initializes the WordPress handler.
	 */
	public function register_taxonomies() {
		register_taxonomy(
			'job-details',
			'post',
			array(
				'labels'       => array(
					'name'                       => 'Job Details',
					'singular_name'              => 'Job Details',
					'menu_name'                  => 'Job Details',
					'all_items'                  => 'All Job Details',
					'edit_item'                  => 'Edit Job Details',
					'view_item'                  => 'View Job Details',
					'update_item'                => 'Update Job Details',
					'add_new_item'               => 'Add New Job Details',
					'new_item_name'              => 'New Job Details Name',
					'search_items'               => 'Search Job Details',
					'popular_items'              => 'Popular Job Details',
					'separate_items_with_commas' => 'Separate job details with commas',
					'add_or_remove_items'        => 'Add or remove job details',
					'choose_from_most_used'      => 'Choose from the most used job details',
					'not_found'                  => 'No job details found',
					'no_terms'                   => 'No job details',
					'items_list_navigation'      => 'Job Details list navigation',
					'items_list'                 => 'Job Details list',
					'back_to_items'              => '← Go to job details',
					'item_link'                  => 'Job Details Link',
					'item_link_description'      => 'A link to a job details',
				),
				'public'       => true,
				'show_in_menu' => true,
				'show_in_rest' => true,
			)
		);
		register_taxonomy(
			'location',
			'post',
			array(
				'labels'            => array(
					'name'                       => 'Locations',
					'singular_name'              => 'Location',
					'menu_name'                  => 'Locations',
					'all_items'                  => 'All Locations',
					'edit_item'                  => 'Edit Location',
					'view_item'                  => 'View Location',
					'update_item'                => 'Update Location',
					'add_new_item'               => 'Add New Location',
					'new_item_name'              => 'New Location Name',
					'search_items'               => 'Search Locations',
					'popular_items'              => 'Popular Locations',
					'separate_items_with_commas' => 'Separate locations with commas',
					'add_or_remove_items'        => 'Add or remove locations',
					'choose_from_most_used'      => 'Choose from the most used locations',
					'not_found'                  => 'No locations found',
					'no_terms'                   => 'No locations',
					'items_list_navigation'      => 'Locations list navigation',
					'items_list'                 => 'Locations list',
					'back_to_items'              => '← Go to locations',
					'item_link'                  => 'Location Link',
					'item_link_description'      => 'A link to a location',
				),
				'public'            => true,
				'show_in_menu'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);
	}
}