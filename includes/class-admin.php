<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin admin functionality.
 */
class Production_Events_Admin {

	/**
	 * Registration repository.
	 *
	 * @var Production_Events_Registration_Repository
	 */
	private $registration_repository;

	/**
	 * Constructor.
	 *
	 * @param Production_Events_Registration_Repository $registration_repository Registration repository.
	 */
	public function __construct( $registration_repository ) {

		$this->registration_repository = $registration_repository;
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register() {

		add_submenu_page(
			'edit.php?post_type=pem_event',
			__( 'Registrations', 'production-events-manager' ),
			__( 'Registrations', 'production-events-manager' ),
			'manage_event_registrations',
			'production-event-registrations',
			array( $this, 'render_registrations_page' )
		);
	}

	/**
	 * Render registrations admin page.
	 *
	 * @return void
	 */
	public function render_registrations_page() {

		if ( ! current_user_can( 'manage_event_registrations' ) ) {
			wp_die(
				esc_html__(
					'You do not have permission to view registrations.',
					'production-events-manager'
				)
			);
		}

		$selected_event = isset( $_GET['event_id'] )
			? absint( $_GET['event_id'] )
			: 0;

		$search = isset( $_GET['s'] )
			? sanitize_text_field(
				wp_unslash( $_GET['s'] )
			)
			: '';

		$current_page = isset( $_GET['paged'] )
			? max( 1, absint( $_GET['paged'] ) )
			: 1;

		$per_page = 20;

		$registrations =
			$this->registration_repository->get_registrations(
				array(
					'page'     => $current_page,
					'per_page' => $per_page,
					'event_id' => $selected_event,
					'search'   => $search,
				)
			);

		$total_registrations =
			$this->registration_repository->count_registrations(
				$selected_event,
				$search
			);

		$total_pages = (int) ceil(
			$total_registrations / $per_page
		);

		$events = get_posts(
			array(
				'post_type'      => 'pem_event',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		/*
		 * Build CSV export URL with the currently selected filters.
		 */
		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'post_type'               => 'pem_event',
					'page'                    => 'production-event-registrations',
					'pem_export_registrations' => 1,
					'event_id'                => $selected_event,
					's'                       => $search,
				),
				admin_url( 'edit.php' )
			),
			'pem_export_registrations'
		);

		?>

		<div class="wrap">

			<h1 class="wp-heading-inline">
				<?php
				esc_html_e(
					'Event Registrations',
					'production-events-manager'
				);
				?>
			</h1>

			<hr class="wp-header-end">

			<form method="get">

				<input
					type="hidden"
					name="post_type"
					value="pem_event"
				>

				<input
					type="hidden"
					name="page"
					value="production-event-registrations"
				>

				<div class="tablenav top">

					<div class="alignleft actions">

						<label
							class="screen-reader-text"
							for="pem-registration-search"
						>
							<?php
							esc_html_e(
								'Search registrations',
								'production-events-manager'
							);
							?>
						</label>

						<input
							type="search"
							id="pem-registration-search"
							name="s"
							value="<?php echo esc_attr( $search ); ?>"
							placeholder="<?php esc_attr_e( 'Search name or email...', 'production-events-manager' ); ?>"
						>

						<label
							class="screen-reader-text"
							for="pem-registration-event"
						>
							<?php
							esc_html_e(
								'Filter by event',
								'production-events-manager'
							);
							?>
						</label>

						<select
							id="pem-registration-event"
							name="event_id"
						>

							<option value="0">
								<?php
								esc_html_e(
									'All Events',
									'production-events-manager'
								);
								?>
							</option>

							<?php foreach ( $events as $event ) : ?>

								<option
									value="<?php echo esc_attr( $event->ID ); ?>"
									<?php selected(
										$selected_event,
										$event->ID
									); ?>
								>
									<?php echo esc_html( $event->post_title ); ?>
								</option>

							<?php endforeach; ?>

						</select>

						<?php
						submit_button(
							__( 'Filter', 'production-events-manager' ),
							'secondary',
							'filter_action',
							false
						);
						?>

						<a
							href="<?php echo esc_url( $export_url ); ?>"
							class="button"
						>
							<?php
							esc_html_e(
								'Export CSV',
								'production-events-manager'
							);
							?>
						</a>

					</div>

					<div class="tablenav-pages">

						<?php
						if ( $total_pages > 1 ) {
							echo wp_kses_post(
								paginate_links(
									array(
										'base'      => add_query_arg(
											array(
												'paged'    => '%#%',
												'event_id' => $selected_event,
												's'        => $search,
											)
										),
										'format'    => '',
										'current'   => $current_page,
										'total'     => $total_pages,
										'prev_text' => __(
											'« Previous',
											'production-events-manager'
										),
										'next_text' => __(
											'Next »',
											'production-events-manager'
										),
										'type'      => 'plain',
									)
								)
							);
						}
						?>

					</div>

				</div>

			</form>

			<table class="widefat fixed striped">

				<thead>

					<tr>

						<th scope="col">
							<?php
							esc_html_e(
								'Event',
								'production-events-manager'
							);
							?>
						</th>

						<th scope="col">
							<?php
							esc_html_e(
								'Name',
								'production-events-manager'
							);
							?>
						</th>

						<th scope="col">
							<?php
							esc_html_e(
								'Email',
								'production-events-manager'
							);
							?>
						</th>

						<th scope="col">
							<?php
							esc_html_e(
								'Registered At',
								'production-events-manager'
							);
							?>
						</th>

					</tr>

				</thead>

				<tbody>

					<?php if ( empty( $registrations ) ) : ?>

						<tr>

							<td colspan="4">

								<?php
								esc_html_e(
									'No registrations found.',
									'production-events-manager'
								);
								?>

							</td>

						</tr>

					<?php else : ?>

						<?php foreach ( $registrations as $registration ) : ?>

							<tr>

								<td>

									<?php
									$title = get_the_title(
										$registration->event_id
									);

									echo esc_html( $title );
									?>

								</td>

								<td>
									<?php
									echo esc_html(
										$registration->name
									);
									?>
								</td>

								<td>
									<?php
									echo esc_html(
										$registration->email
									);
									?>
								</td>

								<td>
									<?php
									echo esc_html(
										$registration->registered_at
									);
									?>
								</td>

							</tr>

						<?php endforeach; ?>

					<?php endif; ?>

				</tbody>

			</table>

			<?php if ( $total_pages > 1 ) : ?>

				<div class="tablenav bottom">

					<div class="tablenav-pages">

						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg(
										array(
											'paged'    => '%#%',
											'event_id' => $selected_event,
											's'        => $search,
										)
									),
									'format'    => '',
									'current'   => $current_page,
									'total'     => $total_pages,
									'prev_text' => __(
										'« Previous',
										'production-events-manager'
									),
									'next_text' => __(
										'Next »',
										'production-events-manager'
									),
									'type'      => 'plain',
								)
							)
						);
						?>

					</div>

				</div>

			<?php endif; ?>

		</div>

		<?php
	}
}