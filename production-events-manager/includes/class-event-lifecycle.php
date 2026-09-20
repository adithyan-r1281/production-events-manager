<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles event lifecycle and timezone-aware date calculations.
 */
class Production_Events_Event_Lifecycle {

	/**
	 * Get the WordPress timezone.
	 *
	 * @return DateTimeZone
	 */
	private function get_timezone() {

		return wp_timezone();
	}

	/**
	 * Get an event datetime as a DateTimeImmutable object.
	 *
	 * Event metadata is stored as a local WordPress datetime.
	 *
	 * @param int    $event_id Event ID.
	 * @param string $meta_key Metadata key.
	 * @return DateTimeImmutable|null
	 */
	private function get_event_datetime( $event_id, $meta_key ) {

		$value = get_post_meta(
			$event_id,
			$meta_key,
			true
		);

		if ( empty( $value ) ) {
			return null;
		}

		try {

			return new DateTimeImmutable(
				$value,
				$this->get_timezone()
			);

		} catch ( Exception $exception ) {

			return null;
		}
	}

	/**
	 * Get the event start datetime.
	 *
	 * @param int $event_id Event ID.
	 * @return DateTimeImmutable|null
	 */
	public function get_event_start( $event_id ) {

		return $this->get_event_datetime(
			$event_id,
			'pem_start_datetime'
		);
	}

	/**
	 * Get the event end datetime.
	 *
	 * @param int $event_id Event ID.
	 * @return DateTimeImmutable|null
	 */
	public function get_event_end( $event_id ) {

		return $this->get_event_datetime(
			$event_id,
			'pem_end_datetime'
		);
	}

	/**
	 * Get the registration closing datetime.
	 *
	 * @param int $event_id Event ID.
	 * @return DateTimeImmutable|null
	 */
	public function get_registration_closing( $event_id ) {

		return $this->get_event_datetime(
			$event_id,
			'pem_registration_closing_datetime'
		);
	}

	/**
	 * Determine whether the event is cancelled.
	 *
	 * @param int $event_id Event ID.
	 * @return bool
	 */
	public function is_cancelled( $event_id ) {

		return (bool) get_post_meta(
			$event_id,
			'pem_cancelled',
			true
		);
	}

	/**
	 * Determine whether the event has started.
	 *
	 * @param int $event_id Event ID.
	 * @return bool
	 */
	public function has_started( $event_id ) {

		$start = $this->get_event_start( $event_id );

		if ( null === $start ) {
			return false;
		}

		$now = new DateTimeImmutable(
			'now',
			$this->get_timezone()
		);

		return $now >= $start;
	}

	/**
	 * Determine whether the event has ended.
	 *
	 * @param int $event_id Event ID.
	 * @return bool
	 */
	public function has_ended( $event_id ) {

		$end = $this->get_event_end( $event_id );

		if ( null === $end ) {
			return false;
		}

		$now = new DateTimeImmutable(
			'now',
			$this->get_timezone()
		);

		return $now >= $end;
	}

	/**
	 * Determine whether registration has closed.
	 *
	 * @param int $event_id Event ID.
	 * @return bool
	 */
	public function is_registration_closed( $event_id ) {

		$closing = $this->get_registration_closing( $event_id );

		if ( null === $closing ) {
			return false;
		}

		$now = new DateTimeImmutable(
			'now',
			$this->get_timezone()
		);

		return $now >= $closing;
	}

	/**
	 * Determine the current event lifecycle status.
	 *
	 * @param int $event_id Event ID.
	 * @return string
	 */
	public function get_status( $event_id ) {

		if ( $this->is_cancelled( $event_id ) ) {
			return 'cancelled';
		}

		if ( $this->has_ended( $event_id ) ) {
			return 'expired';
		}

		if ( $this->is_registration_closed( $event_id ) ) {
			return 'registration_closed';
		}

		if ( $this->has_started( $event_id ) ) {
			return 'started';
		}

		return 'upcoming';
	}
}