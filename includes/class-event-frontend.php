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
            '1.0.1'
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