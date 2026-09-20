<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$event_repository = new Production_Events_Event_Repository();
$event_lifecycle  = new Production_Events_Event_Lifecycle();

$current_page = max(
	1,
	get_query_var( 'paged' ),
	get_query_var( 'page' )
);

$event_frontend = new Production_Events_Event_Frontend();
$filters        = $event_frontend->get_archive_filters();
$is_filtered    = (
	$filters['venue_id'] > 0 ||
	'' !== $filters['date_from'] ||
	'' !== $filters['date_to']
);
$archive_url    = get_post_type_archive_link( 'pem_event' );

$events = $event_repository->get_filtered_events(
	$filters,
	array(
		'posts_per_page' => 10,
		'paged'          => $current_page,
	)
);

/*
 * Hide the filter bar only when the archive is genuinely empty. A filtered
 * request with no matches must still show the form so it can be changed.
 */
$show_filters  = $is_filtered || $events->have_posts();
$venue_options = $show_filters ? $event_repository->get_all_venues() : array();
?>

<main class="pem-events-archive">

	<header class="pem-events-header">
		<h1><?php esc_html_e( 'Upcoming Events', 'production-events-manager' ); ?></h1>
	</header>

	<?php if ( $show_filters ) : ?>

		<form
			class="pem-events-filter"
			method="get"
			action="<?php echo esc_url( $archive_url ); ?>"
			aria-label="<?php esc_attr_e( 'Filter events', 'production-events-manager' ); ?>"
		>

			<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
				<?php /* Plain permalinks: a GET form drops the action's query string. */ ?>
				<input type="hidden" name="post_type" value="pem_event">
			<?php endif; ?>

			<?php if ( ! empty( $venue_options ) ) : ?>

				<div class="pem-filter-field pem-filter-field--venue">

					<label for="pem-filter-venue">
						<?php esc_html_e( 'Venue', 'production-events-manager' ); ?>
					</label>

					<select id="pem-filter-venue" name="pem_venue_id">

						<option value="">
							<?php esc_html_e( 'All venues', 'production-events-manager' ); ?>
						</option>

						<?php foreach ( $venue_options as $venue_option ) : ?>
							<option
								value="<?php echo esc_attr( $venue_option->term_id ); ?>"
								<?php selected( $filters['venue_id'], $venue_option->term_id ); ?>
							>
								<?php echo esc_html( $venue_option->name ); ?>
							</option>
						<?php endforeach; ?>

					</select>

				</div>

			<?php endif; ?>

			<div class="pem-filter-field">

				<label for="pem-filter-from">
					<?php esc_html_e( 'From date', 'production-events-manager' ); ?>
				</label>

				<input
					type="date"
					id="pem-filter-from"
					name="pem_date_from"
					value="<?php echo esc_attr( $filters['date_from'] ); ?>"
				>

			</div>

			<div class="pem-filter-field">

				<label for="pem-filter-to">
					<?php esc_html_e( 'To date', 'production-events-manager' ); ?>
				</label>

				<input
					type="date"
					id="pem-filter-to"
					name="pem_date_to"
					value="<?php echo esc_attr( $filters['date_to'] ); ?>"
				>

			</div>

			<div class="pem-filter-actions">

				<button type="submit" class="pem-filter-submit">
					<?php esc_html_e( 'Filter', 'production-events-manager' ); ?>
				</button>

				<?php if ( $is_filtered ) : ?>
					<a class="pem-filter-reset" href="<?php echo esc_url( $archive_url ); ?>">
						<?php esc_html_e( 'Reset', 'production-events-manager' ); ?>
					</a>
				<?php endif; ?>

			</div>

		</form>

	<?php endif; ?>

	<?php if ( $events->have_posts() ) : ?>

		<?php if ( $is_filtered ) : ?>
			<p class="pem-events-count">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of matching events. */
						_n(
							'%d event found',
							'%d events found',
							$events->found_posts,
							'production-events-manager'
						),
						$events->found_posts
					)
				);
				?>
			</p>
		<?php endif; ?>

		<div class="pem-events-list">

			<?php while ( $events->have_posts() ) : ?>

				<?php
				$events->the_post();

				$event_id = get_the_ID();

				$start = $event_lifecycle->get_event_start(
					$event_id
				);

				$end = $event_lifecycle->get_event_end(
					$event_id
				);

				$venues = $event_repository->get_venues(
					$event_id
				);
				?>

				<article
					id="post-<?php the_ID(); ?>"
					<?php post_class( 'pem-event-card' ); ?>
				>

					<?php if ( has_post_thumbnail() ) : ?>

						<div class="pem-event-card-image">

							<a href="<?php the_permalink(); ?>">

								<?php
								the_post_thumbnail(
									'medium_large',
									array(
										'alt' => the_title_attribute(
											array(
												'echo' => false,
											)
										),
									)
								);
								?>

							</a>

						</div>

					<?php endif; ?>

					<div class="pem-event-card-content">

						<h2 class="pem-event-card-title">

							<a href="<?php the_permalink(); ?>">
								<?php the_title(); ?>
							</a>

						</h2>

						<?php if ( $start ) : ?>

							<p class="pem-event-card-date">

								<strong>
									<?php esc_html_e( 'Starts:', 'production-events-manager' ); ?>
								</strong>

								<?php
								echo esc_html(
									wp_date(
										'F j, Y g:i A',
										$start->getTimestamp(),
										wp_timezone()
									)
								);
								?>

							</p>

						<?php endif; ?>

						<?php if ( $end ) : ?>

							<p class="pem-event-card-date">

								<strong>
									<?php esc_html_e( 'Ends:', 'production-events-manager' ); ?>
								</strong>

								<?php
								echo esc_html(
									wp_date(
										'F j, Y g:i A',
										$end->getTimestamp(),
										wp_timezone()
									)
								);
								?>

							</p>

						<?php endif; ?>

						<?php if ( ! empty( $venues ) ) : ?>

							<p class="pem-event-card-venue">

								<strong>
									<?php esc_html_e( 'Venue:', 'production-events-manager' ); ?>
								</strong>

								<?php
								$venue_names = wp_list_pluck(
									$venues,
									'name'
								);

								echo esc_html(
									implode( ', ', $venue_names )
								);
								?>

							</p>

						<?php endif; ?>

						<div class="pem-event-card-excerpt">
							<?php the_excerpt(); ?>
						</div>

						<p class="pem-event-card-link">

							<a href="<?php the_permalink(); ?>">
								<?php esc_html_e( 'View Event', 'production-events-manager' ); ?>
							</a>

						</p>

					</div>

				</article>

			<?php endwhile; ?>

		</div>

		<?php
		$pagination_args = array();

		if ( $filters['venue_id'] > 0 ) {
			$pagination_args['pem_venue_id'] = $filters['venue_id'];
		}

		if ( '' !== $filters['date_from'] ) {
			$pagination_args['pem_date_from'] = $filters['date_from'];
		}

		if ( '' !== $filters['date_to'] ) {
			$pagination_args['pem_date_to'] = $filters['date_to'];
		}

		$pagination = paginate_links(
			array(
				'total'    => $events->max_num_pages,
				'current'  => $current_page,
				'type'     => 'list',
				'add_args' => $pagination_args,
			)
		);
		?>

		<?php if ( $pagination ) : ?>

			<nav
				class="pem-events-pagination"
				aria-label="<?php esc_attr_e( 'Events pagination', 'production-events-manager' ); ?>"
			>
				<?php echo wp_kses_post( $pagination ); ?>
			</nav>

		<?php endif; ?>

	<?php else : ?>

		<p class="pem-no-events">
			<?php if ( $is_filtered ) : ?>
				<?php esc_html_e( 'No upcoming events match your filters.', 'production-events-manager' ); ?>
				<a href="<?php echo esc_url( $archive_url ); ?>">
					<?php esc_html_e( 'Clear filters', 'production-events-manager' ); ?>
				</a>
			<?php else : ?>
				<?php esc_html_e( 'There are no upcoming events at this time.', 'production-events-manager' ); ?>
			<?php endif; ?>
		</p>

	<?php endif; ?>

</main>

<?php
wp_reset_postdata();

get_footer();