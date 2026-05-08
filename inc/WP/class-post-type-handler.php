<?php
/**
 * Post Type Handler Class
 * Alters the default 'post' post type to be used for the jobs and registers the needed taxonomies for the jobs.
 *
 * @package ChoctawNation
 * @subpackage Jobs_API
 */

namespace ChoctawNation\Jobs_API;

/**
 * Post Type Handler Class
 */
class Post_Type_Handler {
	/**
	 * The post type to alter
	 *
	 * @var string $post_type
	 */
	public $post_type;

	/**
	 * Constructor
	 *
	 * @param string $post_type The post type to alter (default: 'post')
	 */
	public function __construct( string $post_type = 'post' ) {
		$this->post_type = $post_type;
	}

	/**
	 * Registers the custom post type if it doesn't exist yet.
	 */
	public function maybe_register_cpt() {
		if ( ! post_type_exists( $this->post_type ) ) {
			add_action(
				'init',
				function () {
					register_post_type(
						$this->post_type,
						array(
							'public'       => true,
							'show_in_menu' => true,
							'show_in_rest' => true,
						)
					);
				}
			);
			flush_rewrite_rules();
		}
	}

	/**
	 * Unregisters the custom post type if it exists.
	 */
	public function maybe_unregister_cpt() {
		if ( post_type_exists( $this->post_type ) && 'post' !== $this->post_type ) {
			unregister_post_type( $this->post_type );
			flush_rewrite_rules();
		}
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
		$labels                     = get_post_type_labels( get_post_type_object( $this->post_type ) );
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
	 * Initializes the WordPress handler.
	 */
	public function register_taxonomies() {
		if ( taxonomy_exists( 'job-details' ) && taxonomy_exists( 'location' ) && taxonomy_exists( 'work-location' ) ) {
			return;
		}
		register_taxonomy(
			'job-details',
			$this->post_type,
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
			$this->post_type,
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
		register_taxonomy(
			'work-location',
			$this->post_type,
			array(
				'labels'            => array(
					'name'                       => 'Work Locations',
					'singular_name'              => 'Work Location',
					'menu_name'                  => 'Work Locations',
					'all_items'                  => 'All Work Locations',
					'edit_item'                  => 'Edit Work Location',
					'view_item'                  => 'View Work Location',
					'update_item'                => 'Update Work Location',
					'add_new_item'               => 'Add New Work Location',
					'new_item_name'              => 'New Work Location Name',
					'search_items'               => 'Search Work Locations',
					'popular_items'              => 'Popular Work Locations',
					'separate_items_with_commas' => 'Separate work locations with commas',
					'add_or_remove_items'        => 'Add or remove work locations',
					'choose_from_most_used'      => 'Choose from the most used work locations',
					'not_found'                  => 'No work locations found',
					'no_terms'                   => 'No work locations',
					'items_list_navigation'      => 'Work Locations list navigation',
					'items_list'                 => 'Work Locations list',
					'back_to_items'              => '← Go to work locations',
					'item_link'                  => 'Work Location Link',
					'item_link_description'      => 'A link to a work location',
				),
				'public'            => true,
				'show_in_menu'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
			)
		);
	}

	/**
	 * Unregisters the taxonomies if they exist.
	 */
	public function unregister_taxonomies() {
		if ( taxonomy_exists( 'job-details' ) ) {
			unregister_taxonomy( 'job-details' );
		}
		if ( taxonomy_exists( 'location' ) ) {
			unregister_taxonomy( 'location' );
		}
		if ( taxonomy_exists( 'work-location' ) ) {
			unregister_taxonomy( 'work-location' );
		}
	}
}
