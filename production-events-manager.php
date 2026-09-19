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

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-activator.php';

    register_activation_hook(
        __FILE__,
        array(
            'Production_Events_Activator',
            'activate',
    )
);

require_once plugin_dir_path( __FILE__ ) . 'includes/class-event-post-type.php';

$event_post_type = new Production_Events_Post_Type();

$event_post_type->register();

require_once plugin_dir_path( __FILE__ ) . 'includes/class-event-meta.php';

$event_meta = new Production_Events_Event_Meta();

$event_meta->register();

require_once plugin_dir_path( __FILE__ ) . 'includes/class-event-lifecycle.php';

// $event_lifecycle = new Production_Events_Event_Lifecycle();

require_once plugin_dir_path( __FILE__ ) . 'includes/class-event-repository.php';

// $event_repository = new Production_Events_Event_Repository();

require_once plugin_dir_path( __FILE__ ) . 'includes/class-event-frontend.php';

$event_frontend = new Production_Events_Event_Frontend();

$event_frontend->register();

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-registration-repository.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-registration-service.php';

require_once plugin_dir_path( __FILE__ )
	. 'includes/class-rest-controller.php';


$event_repository = new Production_Events_Event_Repository();

$event_lifecycle = new Production_Events_Event_Lifecycle();

$registration_repository =
	new Production_Events_Registration_Repository();

$registration_service =
	new Production_Events_Registration_Service(
		$event_repository,
		$event_lifecycle,
		$registration_repository
	);

$rest_controller =
	new Production_Events_REST_Controller(
		$registration_service
	);

add_action(
	'rest_api_init',
	array( $rest_controller, 'register_routes' )
);