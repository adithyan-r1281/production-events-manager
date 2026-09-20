<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Production_Events_Post_Type {

	/**
	 * Register hooks.
	 */
	public function register() {

		add_action(
			'init',
			array( $this, 'register_event_post_type' )
		);

		add_action(
			'init',
			array( $this, 'register_venue_taxonomy' )
		);
	}

	/**
	 * Register Event custom post type.
	 */
	public function register_event_post_type() {

		$labels = array(
			'name'          => 'Events',
			'singular_name' => 'Event',
			'add_new'       => 'Add New Event',
			'add_new_item'  => 'Add New Event',
			'edit_item'     => 'Edit Event',
			'new_item'      => 'New Event',
			'view_item'     => 'View Event',
			'all_items'     => 'All Events',
			'menu_name'     => 'Events',
		);

        $args = array(
            'labels'       => $labels,
            'public'       => true,
            'show_in_rest' => true,
            'has_archive'  => true,
            'rewrite'      => array(
                'slug'       => 'events',
                'with_front' => false,
            ),
            'menu_icon'    => 'dashicons-calendar-alt',
            'supports'     => array(
                'title',
                'editor',
                'thumbnail',
                'custom-fields',
            ),
        );

		register_post_type(
			'pem_event',
			$args
		);
	}

	/**
	 * Register Venue taxonomy.
	 */
	public function register_venue_taxonomy() {

		$labels = array(
			'name'          => 'Venues',
			'singular_name' => 'Venue',
			'search_items'  => 'Search Venues',
			'all_items'     => 'All Venues',
			'edit_item'     => 'Edit Venue',
			'update_item'   => 'Update Venue',
			'add_new_item'  => 'Add New Venue',
			'new_item_name' => 'New Venue Name',
			'menu_name'     => 'Venues',
		);

		$args = array(
			'labels'       => $labels,
			'public'       => true,
			'show_in_rest' => true,
			'hierarchical' => false,
		);

		register_taxonomy(
			'pem_venue',
			array( 'pem_event' ),
			$args
		);
	}
}