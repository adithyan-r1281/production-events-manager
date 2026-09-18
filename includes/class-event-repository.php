<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles event data retrieval.
 */
class Production_Events_Event_Repository {

	/**
	 * Get a single event.
	 *
	 * @param int $event_id Event ID.
	 * @return WP_Post|null
	 */
	public function get_event( $event_id ) {

		$event = get_post( $event_id );

		if ( ! $event instanceof WP_Post ) {
			return null;
		}

		if ( 'pem_event' !== $event->post_type ) {
			return null;
		}

		return $event;
	}

	/**
	 * Get published events.
	 *
	 * @param array $args Query arguments.
	 * @return WP_Query
	 */
	public function get_published_events( $args = array() ) {

		$defaults = array(
			'post_type'      => 'pem_event',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'paged'          => 1,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_key'       => 'pem_start_datetime',
		);

		$query_args = wp_parse_args(
			$args,
			$defaults
		);

		return new WP_Query( $query_args );
	}

	/**
	 * Get upcoming published events.
	 *
	 * Cancelled events are excluded.
	 *
	 * @param array $args Query arguments.
	 * @return WP_Query
	 */
	public function get_upcoming_events( $args = array() ) {

		$timezone = wp_timezone();

		$now = new DateTimeImmutable(
			'now',
			$timezone
		);

		$now_string = $now->format( 'Y-m-d\TH:i' );

		$defaults = array(
			'post_type'      => 'pem_event',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'paged'          => 1,
			'meta_key'       => 'pem_start_datetime',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'pem_start_datetime',
					'value'   => $now_string,
					'compare' => '>=',
					'type'    => 'CHAR',
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'pem_cancelled',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'pem_cancelled',
						'value'   => '1',
						'compare' => '!=',
					),
				),
			),
		);

		$query_args = wp_parse_args(
			$args,
			$defaults
		);

		return new WP_Query( $query_args );
	}

	/**
	 * Get events filtered by venue.
	 *
	 * @param int   $venue_id Venue term ID.
	 * @param array $args     Query arguments.
	 * @return WP_Query
	 */
	public function get_events_by_venue( $venue_id, $args = array() ) {

		$defaults = array(
			'post_type'      => 'pem_event',
			'post_status'    => 'publish',
			'posts_per_page' => 10,
			'paged'          => 1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'pem_venue',
					'field'    => 'term_id',
					'terms'    => absint( $venue_id ),
				),
			),
			'meta_key'       => 'pem_start_datetime',
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
		);

		$query_args = wp_parse_args(
			$args,
			$defaults
		);

		return new WP_Query( $query_args );
	}

	/**
	 * Get the event capacity.
	 *
	 * A capacity of 0 means unlimited.
	 *
	 * @param int $event_id Event ID.
	 * @return int
	 */
	public function get_capacity( $event_id ) {

		$capacity = get_post_meta(
			$event_id,
			'pem_capacity',
			true
		);

		if ( '' === $capacity ) {
			return 0;
		}

		return absint( $capacity );
	}

	/**
	 * Get the event venue terms.
	 *
	 * @param int $event_id Event ID.
	 * @return WP_Term[]
	 */
	public function get_venues( $event_id ) {

		$venues = get_the_terms(
			$event_id,
			'pem_venue'
		);

		if ( is_wp_error( $venues ) || empty( $venues ) ) {
			return array();
		}

		return $venues;
	}

	/**
	 * Determine whether an event is published.
	 *
	 * @param int $event_id Event ID.
	 * @return bool
	 */
	public function is_published( $event_id ) {

		return 'publish' === get_post_status( $event_id );
	}
}