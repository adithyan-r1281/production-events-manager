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
}