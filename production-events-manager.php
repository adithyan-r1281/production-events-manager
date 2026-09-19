<?php
/**
 * Plugin Name: Production Events Manager
 * Description: A WordPress plugin for managing and displaying events.
 * Version: 1.0.5
 * Author: Your Name
 * Text Domain: production-events-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Core plugin classes.
 */
require_once plugin_dir_path( __FILE__ )
    . 'includes/class-activator.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-deactivator.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-event-post-type.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-event-meta.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-event-lifecycle.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-event-repository.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-event-frontend.php';

/*
 * Registration classes.
 */
require_once plugin_dir_path( __FILE__ )
	. 'includes/class-registration-repository.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-registration-service.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-rest-controller.php';

/*
 * Admin classes.
 */
require_once plugin_dir_path( __FILE__ )
	. 'includes/class-capabilities.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-admin.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-csv-exporter.php';

/*
 * Activation.
 */
register_activation_hook(
	__FILE__,
	array(
		'Production_Events_Activator',
		'activate',
	)
);

register_deactivation_hook(
	__FILE__,
	array( 'Production_Events_Deactivator', 'deactivate' )
);

/*
 * Event post type.
 */
$event_post_type = new Production_Events_Post_Type();
$event_post_type->register();

add_action(
	'init',
	function () {

		if (
			! get_option(
				'production_events_flush_rewrite_rules'
			)
		) {
			return;
		}

		flush_rewrite_rules();

		delete_option(
			'production_events_flush_rewrite_rules'
		);
	},
	99
);

/*
 * Event metadata.
 */
$event_meta = new Production_Events_Event_Meta();
$event_meta->register();

/*
 * Frontend event rendering.
 */
$event_frontend = new Production_Events_Event_Frontend();
$event_frontend->register();

/*
 * Core event services.
 */
$event_repository = new Production_Events_Event_Repository();

$event_lifecycle = new Production_Events_Event_Lifecycle();

/*
 * Registration services.
 */
$registration_repository =
	new Production_Events_Registration_Repository();

$registration_service =
	new Production_Events_Registration_Service(
		$event_repository,
		$event_lifecycle,
		$registration_repository
	);

/*
 * REST API.
 */
$rest_controller =
	new Production_Events_REST_Controller(
		$registration_service
	);

add_action(
	'rest_api_init',
	array( $rest_controller, 'register_routes' )
);

/*
 * Admin registrations page.
 */
$admin = new Production_Events_Admin(
	$registration_repository
);

add_action(
	'admin_menu',
	array( $admin, 'register' )
);

/*
 * CSV export.
 */
$csv_exporter = new Production_Events_CSV_Exporter(
	$registration_repository
);

$csv_exporter->register();