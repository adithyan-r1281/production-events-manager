<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles frontend event rendering.
 */
class Production_Events_Event_Frontend {

	/**
	 * Register frontend hooks.
	 */
	public function register() {

		add_filter(
			'template_include',
			array( $this, 'load_event_template' )
		);

        add_action(
            'wp_enqueue_scripts',
            array( $this, 'enqueue_registration_assets' )
        );
	}

	/**
	 * Load plugin event templates.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function load_event_template( $template ) {

		$plugin_template = '';

		if ( is_post_type_archive( 'pem_event' ) ) {

			$plugin_template = plugin_dir_path( dirname( __FILE__ ) )
				. 'templates/archive-event.php';

		} elseif ( is_singular( 'pem_event' ) ) {

			$plugin_template = plugin_dir_path( dirname( __FILE__ ) )
				. 'templates/single-event.php';
		}

		if (
			! empty( $plugin_template ) &&
			file_exists( $plugin_template )
		) {
			return $plugin_template;
		}

		return $template;
	}

	/**
	 * Read and validate the archive filters from the query string.
	 *
	 * Invalid values are dropped rather than reported, so a stale or
	 * hand-edited URL still renders the archive. If the date range is
	 * back-to-front the two dates are swapped.
	 *
	 * @return array {
	 *     @type int    $venue_id  Venue term ID, or 0 for any venue.
	 *     @type string $date_from Y-m-d, or an empty string.
	 *     @type string $date_to   Y-m-d, or an empty string.
	 * }
	 */
	public function get_archive_filters() {

		// Read-only GET filters: no state changes, so no nonce is required.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$venue_id = isset( $_GET['pem_venue_id'] ) && is_string( $_GET['pem_venue_id'] )
			? absint( wp_unslash( $_GET['pem_venue_id'] ) )
			: 0;

		$date_from = isset( $_GET['pem_date_from'] )
			? $this->sanitize_filter_date( wp_unslash( $_GET['pem_date_from'] ) )
			: '';

		$date_to = isset( $_GET['pem_date_to'] )
			? $this->sanitize_filter_date( wp_unslash( $_GET['pem_date_to'] ) )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $venue_id > 0 && ! get_term( $venue_id, 'pem_venue' ) instanceof WP_Term ) {
			$venue_id = 0;
		}

		if ( '' !== $date_from && '' !== $date_to && $date_from > $date_to ) {
			$swap      = $date_from;
			$date_from = $date_to;
			$date_to   = $swap;
		}

		return array(
			'venue_id'  => $venue_id,
			'date_from' => $date_from,
			'date_to'   => $date_to,
		);
	}

	/**
	 * Validate a Y-m-d date string.
	 *
	 * Rejects impossible dates such as 2026-02-31 instead of letting PHP roll
	 * them over into March.
	 *
	 * @param mixed $value Raw value.
	 * @return string Valid Y-m-d date, or an empty string.
	 */
	private function sanitize_filter_date( $value ) {

		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = sanitize_text_field( $value );

		$date = DateTimeImmutable::createFromFormat(
			'!Y-m-d',
			$value,
			wp_timezone()
		);

		if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) {
			return '';
		}

		return $value;
	}

    /**
     * Enqueue registration assets.
     *
     * @return void
     */
    public function enqueue_registration_assets() {

        $is_single  = is_singular( 'pem_event' );
        $is_archive = is_post_type_archive( 'pem_event' );

        if ( ! $is_single && ! $is_archive ) {
            return;
        }

        wp_enqueue_style(
            'production-events-registration',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/event-registration.css',
            array(),
            '1.0.2'
        );

        if ( $is_single ) {
            wp_enqueue_script(
                'production-events-registration',
                plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/registration-form.js',
                array(),
                '1.0.0',
                true
            );

            wp_localize_script(
                'production-events-registration',
                'pemRegistration',
                array(
                    'restUrl' => esc_url_raw( rest_url( 'production-events/v1/events/' ) ),
                )
            );
        }
    }
}