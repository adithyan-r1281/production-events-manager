<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Production_Events_Event_Meta {

	/**
	 * Register hooks.
	 */
	public function register() {

		add_action(
			'add_meta_boxes',
			array( $this, 'add_meta_boxes' )
		);

		add_action(
			'save_post_pem_event',
			array( $this, 'save_meta' )
		);

		add_action(
			'admin_notices',
			array( $this, 'display_date_error' )
		);

        add_action(
            'init',
            array( $this, 'register_meta_fields' )
        );

        add_filter(
            'rest_pre_insert_pem_event',
            array( $this, 'validate_rest_dates' ),
            10,
            2
        );
       
        add_action(
            'admin_enqueue_scripts',
            array( $this, 'enqueue_date_validation_script' )
        );
	}

	/**
	 * Add Event Details meta box.
	 */
	public function add_meta_boxes() {

		add_meta_box(
			'pem_event_details',
			'Event Details',
			array( $this, 'render_meta_box' ),
			'pem_event',
			'normal',
			'high'
		);
	}

	/**
	 * Render Event Details meta box.
	 */
	public function render_meta_box( $post ) {

		wp_nonce_field(
			'pem_save_event_meta',
			'pem_event_meta_nonce'
		);

		$start_datetime = get_post_meta(
			$post->ID,
			'pem_start_datetime',
			true
		);

		$end_datetime = get_post_meta(
			$post->ID,
			'pem_end_datetime',
			true
		);

		?>

		<p>
			<label for="pem_start_datetime">
				<strong>Start Date &amp; Time</strong>
			</label>
		</p>

		<input
			type="datetime-local"
			id="pem_start_datetime"
			name="pem_start_datetime"
			value="<?php echo esc_attr( $start_datetime ); ?>"
			class="widefat"
		>

		<p>
			<label for="pem_end_datetime">
				<strong>End Date &amp; Time</strong>
			</label>
		</p>

		<input
			type="datetime-local"
			id="pem_end_datetime"
			name="pem_end_datetime"
			value="<?php echo esc_attr( $end_datetime ); ?>"
			class="widefat"
		>
        <p>
            <label for="pem_capacity">
                <strong>Capacity</strong>
            </label>
        </p>

        <?php
        $capacity = get_post_meta(
            $post->ID,
            'pem_capacity',
            true
        );
        ?>

        <input
            type="number"
            id="pem_capacity"
            name="pem_capacity"
            value="<?php echo esc_attr( $capacity ); ?>"
            min="0"
            step="1"
            class="widefat"
            aria-describedby="pem-capacity-error"
        >

        <p
            id="pem-capacity-error"
            class="pem-validation-error"
            style="display: none;"
        ></p>

        <p class="description">
            Enter the maximum number of attendees. Use 0 for unlimited capacity.
        </p>
        <?php
            $registration_closing_datetime = get_post_meta(
                $post->ID,
                'pem_registration_closing_datetime',
                true
            );
            ?>

            <p>
                <label for="pem_registration_closing_datetime">
                    <strong>Registration Closing Date &amp; Time</strong>
                </label>
            </p>

            <input
                type="datetime-local"
                id="pem_registration_closing_datetime"
                name="pem_registration_closing_datetime"
                value="<?php echo esc_attr( $registration_closing_datetime ); ?>"
                class="widefat"
                aria-describedby="pem-registration-closing-error"
            >

            <p
                id="pem-registration-closing-error"
                class="pem-validation-error"
                style="display: none;"
            ></p>

            <p class="description">
                Leave empty to keep registration open until another event restriction applies.
            </p>

            <?php
                $cancelled = get_post_meta(
                    $post->ID,
                    'pem_cancelled',
                    true
                );
                ?>

                <p>
                    <label for="pem_cancelled">
                        <strong>Event Status</strong>
                    </label>
                </p>

                <label for="pem_cancelled">
                    <input
                        type="checkbox"
                        id="pem_cancelled"
                        name="pem_cancelled"
                        value="1"
                        <?php checked( $cancelled, '1' ); ?>
                    >
                    Event Cancelled
                </label>

                <p class="description">
                    Mark this event as cancelled. The event will remain published but
                    registration will be disabled.
                </p>

		    <?php
	}

	/**
	 * Save Event metadata.
	 */
	public function save_meta( $post_id ) {

		// Prevent autosave from running this function.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't save metadata for revisions.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Check the nonce.
		if (
			! isset( $_POST['pem_event_meta_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field(
					wp_unslash( $_POST['pem_event_meta_nonce'] )
				),
				'pem_save_event_meta'
			)
		) {
			return;
		}

		// Check user permission.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check that this is actually an Event.
		if ( 'pem_event' !== get_post_type( $post_id ) ) {
			return;
		}

        // Get cancellation status.
        $cancelled = isset( $_POST['pem_cancelled'] );

		// Get start date/time.
		$start_datetime = '';

		if ( isset( $_POST['pem_start_datetime'] ) ) {

			$start_datetime = sanitize_text_field(
				wp_unslash( $_POST['pem_start_datetime'] )
			);
		}

		// Get end date/time.
		$end_datetime = '';

		if ( isset( $_POST['pem_end_datetime'] ) ) {

			$end_datetime = sanitize_text_field(
				wp_unslash( $_POST['pem_end_datetime'] )
			);
		}

        // Get capacity.
        $capacity = 0;

        if ( isset( $_POST['pem_capacity'] ) ) {

            $raw_capacity = wp_unslash( $_POST['pem_capacity'] );

            if (
                is_string( $raw_capacity ) &&
                preg_match( '/^\d+$/', $raw_capacity )
            ) {
                $capacity = (int) $raw_capacity;
            }
        }

        // Get registration closing date/time.
        $registration_closing_datetime = '';

        if ( isset( $_POST['pem_registration_closing_datetime'] ) ) {

            $registration_closing_datetime = sanitize_text_field(
                wp_unslash( $_POST['pem_registration_closing_datetime'] )
            );
        }

        // Validate event dates.
        if ( ! $this->validate_dates( $start_datetime, $end_datetime ) ) {
            return;
        }

        // Validate registration closing date/time.
        if (
            ! $this->validate_registration_closing(
                $registration_closing_datetime,
                $start_datetime
            )
        ) {
            return;
        }

		// Save the start date/time.
		if ( ! empty( $start_datetime ) ) {

			update_post_meta(
				$post_id,
				'pem_start_datetime',
				$start_datetime
			);
		}

		// Save the end date/time.
		if ( ! empty( $end_datetime ) ) {

			update_post_meta(
				$post_id,
				'pem_end_datetime',
				$end_datetime
			);
		}
        // Save capacity.
        update_post_meta(
            $post_id,
            'pem_capacity',
            $capacity
        );
        // Save registration closing date/time.
        if ( ! empty( $registration_closing_datetime ) ) {

            update_post_meta(
                $post_id,
                'pem_registration_closing_datetime',
                $registration_closing_datetime
            );

        } else {

            delete_post_meta(
                $post_id,
                'pem_registration_closing_datetime'
            );
        }
        // Save cancellation status.
        update_post_meta(
            $post_id,
            'pem_cancelled',
            $cancelled
        );
	}

	/**
	 * Validate event dates.
	 *
	 * @param string $start_datetime Event start date/time.
	 * @param string $end_datetime   Event end date/time.
	 * @return bool True if dates are valid.
	 */
	private function validate_dates( $start_datetime, $end_datetime ) {

		if ( empty( $start_datetime ) || empty( $end_datetime ) ) {
			return true;
		}

		$start_timestamp = strtotime( $start_datetime );
		$end_timestamp   = strtotime( $end_datetime );

		if ( false === $start_timestamp || false === $end_timestamp ) {
			return false;
		}

		return $end_timestamp > $start_timestamp;
	}
    /**
     * Validate registration closing date/time.
     *
     * Registration closing time must not be later than
     * the event start time.
     *
     * @param string $closing_datetime Registration closing date/time.
     * @param string $start_datetime   Event start date/time.
     * @return bool True if valid.
     */
    private function validate_registration_closing(
        $closing_datetime,
        $start_datetime
    ) {

        // Registration closing time is optional.
        if ( empty( $closing_datetime ) ) {
            return true;
        }

        // Closing time requires a valid event start time.
        if ( empty( $start_datetime ) ) {
            return false;
        }

        $closing_timestamp = strtotime( $closing_datetime );
        $start_timestamp   = strtotime( $start_datetime );

        if (
            false === $closing_timestamp ||
            false === $start_timestamp
        ) {
            return false;
        }

        return $closing_timestamp <= $start_timestamp;
    }

	/**
	 * Display date validation error.
	 */
	public function display_date_error() {

		$transient_key = 'pem_date_error_' . get_current_user_id();

		if ( ! get_transient( $transient_key ) ) {
			return;
		}

		delete_transient( $transient_key );

		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong>Event dates are invalid.</strong>
				End Date &amp; Time must be after Start Date &amp; Time.
			</p>
		</div>
		<?php
	}
    /**
     * Register event date meta fields.
     */
    public function register_meta_fields() {

        register_post_meta(
            'pem_event',
            'pem_start_datetime',
            array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
            )
        );

        register_post_meta(
            'pem_event',
            'pem_end_datetime',
            array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
            )
        );

        register_post_meta(
            'pem_event',
            'pem_capacity',
            array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'integer',
                'default'      => 0,
            )
        );

        register_post_meta(
            'pem_event',
            'pem_registration_closing_datetime',
            array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'string',
            )
        );

        register_post_meta(
            'pem_event',
            'pem_cancelled',
            array(
                'show_in_rest' => true,
                'single'       => true,
                'type'         => 'boolean',
                'default'      => false,
            )
        );
    }
    /**
     * Validate event dates before REST API saves the event.
     *
     * @param WP_Post         $prepared_post Prepared post object.
     * @param WP_REST_Request $request       REST API request.
     * @return WP_Post|WP_Error
     */
    public function validate_rest_dates( $prepared_post, $request ) {

        $meta = $request->get_param( 'meta' );

        if ( ! is_array( $meta ) ) {
            return $prepared_post;
        }

        $start_datetime = isset( $meta['pem_start_datetime'] )
            ? sanitize_text_field( $meta['pem_start_datetime'] )
            : get_post_meta( $prepared_post->ID, 'pem_start_datetime', true );

        $end_datetime = isset( $meta['pem_end_datetime'] )
            ? sanitize_text_field( $meta['pem_end_datetime'] )
            : get_post_meta( $prepared_post->ID, 'pem_end_datetime', true );

        $registration_closing_datetime = isset(
            $meta['pem_registration_closing_datetime']
        )
            ? sanitize_text_field(
                $meta['pem_registration_closing_datetime']
            )
            : get_post_meta(
                $prepared_post->ID,
                'pem_registration_closing_datetime',
                true
            );

        if ( ! $this->validate_dates( $start_datetime, $end_datetime ) ) {

            return new WP_Error(
                'pem_invalid_dates',
                'End Date & Time must be after Start Date & Time.',
                array(
                    'status' => 400,
                )
            );
        }

        if (
            ! $this->validate_registration_closing(
                $registration_closing_datetime,
                $start_datetime
            )
        ) {

            return new WP_Error(
                'pem_invalid_registration_closing',
                'Registration Closing Date & Time must not be later than Start Date & Time.',
                array(
                    'status' => 400,
                )
            );
        }

        return $prepared_post;
    }
    /**
     * Enqueue event date validation script.
     */
    public function enqueue_date_validation_script( $hook ) {

        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'pem-event-date-validation',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/event-date-validation.js',
            array(),
            '1.0.1',
            true
        );
    }
}