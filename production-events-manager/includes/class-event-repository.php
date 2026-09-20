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
			'meta_query'     => $this->get_upcoming_meta_query(
				$now_string
			),
		);

		$query_args = wp_parse_args(
			$args,
			$defaults
		);

		return new WP_Query( $query_args );
	}

	/**
	 * Get upcoming published events, optionally narrowed by venue and date.
	 *
	 * The archive only lists upcoming, non-cancelled events. A date range can
	 * narrow that set but never widen it, so a "from" date in the past is
	 * clamped to the current time.
	 *
	 * Dates filter on the event start datetime, inclusive of both days.
	 *
	 * @param array $filters {
	 *     Optional filters.
	 *
	 *     @type int    $venue_id  Venue term ID. 0 or omitted for any venue.
	 *     @type string $date_from Earliest start date (Y-m-d). Empty for none.
	 *     @type string $date_to   Latest start date (Y-m-d). Empty for none.
	 * }
	 * @param array $args    Additional WP_Query arguments.
	 * @return WP_Query
	 */
	public function get_filtered_events( $filters = array(), $args = array() ) {

		$filters = wp_parse_args(
			$filters,
			array(
				'venue_id'  => 0,
				'date_from' => '',
				'date_to'   => '',
			)
		);

		$date_from = $this->normalize_filter_date( $filters['date_from'] );
		$date_to   = $this->normalize_filter_date( $filters['date_to'] );
		$venue_id  = absint( $filters['venue_id'] );

		$start_from = ( new DateTimeImmutable( 'now', wp_timezone() ) )
			->format( 'Y-m-d\TH:i' );

		if (
			'' !== $date_from &&
			$date_from . 'T00:00' > $start_from
		) {
			$start_from = $date_from . 'T00:00';
		}

		$start_to = '' !== $date_to ? $date_to . 'T23:59' : '';

		$filter_args = array(
			'meta_query' => $this->get_upcoming_meta_query(
				$start_from,
				$start_to
			),
		);

		if ( $venue_id > 0 ) {
			$filter_args['tax_query'] = array(
				array(
					'taxonomy' => 'pem_venue',
					'field'    => 'term_id',
					'terms'    => $venue_id,
				),
			);
		}

		/*
		 * The filter clauses win over any caller-supplied meta/tax query so the
		 * "upcoming and not cancelled" rules cannot be bypassed by accident.
		 */
		return $this->get_upcoming_events(
			array_merge( $args, $filter_args )
		);
	}

	/**
	 * Build the meta query for upcoming, non-cancelled events.
	 *
	 * Start datetimes are stored as local "Y-m-d\TH:i" strings, so they sort and
	 * compare correctly as plain strings.
	 *
	 * @param string $start_from Earliest start (Y-m-d\TH:i), inclusive.
	 * @param string $start_to   Latest start (Y-m-d\TH:i), inclusive. Empty for none.
	 * @return array
	 */
	private function get_upcoming_meta_query( $start_from, $start_to = '' ) {

		if ( '' === $start_to ) {
			$start_clause = array(
				'key'     => 'pem_start_datetime',
				'value'   => $start_from,
				'compare' => '>=',
				'type'    => 'CHAR',
			);
		} else {
			$start_clause = array(
				'key'     => 'pem_start_datetime',
				'value'   => array( $start_from, $start_to ),
				'compare' => 'BETWEEN',
				'type'    => 'CHAR',
			);
		}

		return array(
			'relation' => 'AND',
			$start_clause,
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
		);
	}

	/**
	 * Return a strict Y-m-d date, or an empty string for anything else.
	 *
	 * @param mixed $value Candidate date.
	 * @return string
	 */
	private function normalize_filter_date( $value ) {

		if ( ! is_string( $value ) ) {
			return '';
		}

		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value )
			? $value
			: '';
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
	 * Get every venue that has at least one published event.
	 *
	 * Used to populate the archive venue filter.
	 *
	 * @return WP_Term[]
	 */
	public function get_all_venues() {

		$venues = get_terms(
			array(
				'taxonomy'   => 'pem_venue',
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		if ( is_wp_error( $venues ) || empty( $venues ) ) {
			return array();
		}

		return $venues;
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