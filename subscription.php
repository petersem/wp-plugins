<?php
/*
 * Plugin Name: Subscription
 * Description: Collects email subscriptions for event and product news.
 * Version: 1.1.1
 * Plugin URI: https://github.com/petersem/wp-plugins
 * Author: Matt Petersen
 * Author URI: https://github.com/petersem
 * GitHub Plugin URI: https://github.com/petersem/wp-plugins
 * GitHub Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SUBSCRIPTION_DB_VERSION', '1.0.0' );

function subscription_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'subscription_subscribers';
}

register_activation_hook( __FILE__, 'subscription_install' );
add_action( 'plugins_loaded', 'subscription_maybe_upgrade' );

function subscription_maybe_upgrade() {
	global $wpdb;

	$table_exists = $wpdb->get_var(
		$wpdb->prepare( 'SHOW TABLES LIKE %s', subscription_table_name() )
	);

	if ( SUBSCRIPTION_DB_VERSION !== get_option( 'subscription_db_version' ) || subscription_table_name() !== $table_exists ) {
		subscription_install();
	}
}

function subscription_install() {
	global $wpdb;

	$table_name = $wpdb->prefix . 'subscription_subscribers';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		email VARCHAR(320) NOT NULL,
		subscribed_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY email (email)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	update_option( 'subscription_db_version', SUBSCRIPTION_DB_VERSION );
}

add_action( 'astra_footer', 'subscription_footer_button', 0 );
add_action( 'wp_footer', 'subscription_footer_output', 5 );

function subscription_footer_button() {
	$form_submitted = isset( $_POST['subscription_submit'] ) || isset( $_POST['subscription_unsubscribe'] );
	$button_text    = $form_submitted ? 'Hide Subscription Form' : 'Subscribe';
	$button_style   = $form_submitted
		? 'background:#1e73be; color:#fff; border-color:#1e73be;'
		: 'background:#ccc; color:#000; border-color:#999;';
	?>
	<style>
		#footer-sitemap-button-wrapper,
		#footer-subscription-button-wrapper {
			display: inline-block !important;
			width: auto !important;
			vertical-align: bottom !important;
		}
		#footer-subscription-button-wrapper {
			margin-left: 8px !important;
		}
	</style>
	<div id="footer-subscription-button-wrapper" style="padding-left:0; margin:10px 0 0 8px;">
		<button id="footer-subscription-toggle"
				style="padding:6px 12px; border-radius:8px 8px 0 0; <?php echo esc_attr( $button_style ); ?>">
			<?php echo esc_html( $button_text ); ?>
		</button>
	</div>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const sitemapWrapper = document.getElementById('footer-sitemap-button-wrapper');
			const subscriptionWrapper = document.getElementById('footer-subscription-button-wrapper');

			if (sitemapWrapper && subscriptionWrapper) {
				sitemapWrapper.appendChild(subscriptionWrapper);
			}
		});
	</script>
	<?php
}

function subscription_footer_output() {
	$form_submitted = isset( $_POST['subscription_submit'] ) || isset( $_POST['subscription_unsubscribe'] );
	$panel_display  = $form_submitted ? 'block' : 'none';
	?>
	<div id="footer-subscription-content"
		 style="display:<?php echo esc_attr( $panel_display ); ?>; padding:15px; border:1px solid #ccc; background:#f5f5f5; margin-top:20px;">
		<h4>Stay up to date</h4>
		<p>Subscribe for special events and product news.</p>
		<style>
			#footer-subscription-content .subscription-form {
				display: flex;
				align-items: center;
				gap: 8px;
				flex-wrap: wrap;
			}
			#footer-subscription-content .subscription-form label {
				margin: 0;
			}
			#footer-subscription-content .subscription-form input[type="email"] {
				width: min(320px, 100%);
			}
			#footer-subscription-content .subscription-form p {
				flex-basis: 100%;
				margin: 4px 0 0;
			}
		</style>
		<?php echo subscription_render_form(); ?>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const button = document.getElementById('footer-subscription-toggle');
			const content = document.getElementById('footer-subscription-content');

			if (!button || !content) {
				return;
			}

			button.addEventListener('click', function() {
				const isHidden = content.style.display === 'none';
				content.style.display = isHidden ? 'block' : 'none';
				button.textContent = isHidden ? 'Hide Subscription Form' : 'Subscribe';
				button.style.background = isHidden ? '#1e73be' : '#ccc';
				button.style.color = isHidden ? '#fff' : '#000';
				button.style.borderColor = isHidden ? '#1e73be' : '#999';

				if (isHidden) {
					window.scrollTo({
						top: document.body.scrollHeight,
						behavior: 'smooth'
					});
				}
			});

			if (content.style.display !== 'none') {
				window.scrollTo({
					top: document.body.scrollHeight,
					behavior: 'smooth'
				});
			}
		});
	</script>
	<?php
}

function subscription_render_form() {
	$message = '';
	$email   = '';

	if ( isset( $_POST['subscription_submit'] ) || isset( $_POST['subscription_unsubscribe'] ) ) {
		$email = isset( $_POST['subscription_email'] )
			? sanitize_email( wp_unslash( $_POST['subscription_email'] ) )
			: '';
		$is_unsubscribe = isset( $_POST['subscription_unsubscribe'] );

		if ( ! isset( $_POST['subscription_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['subscription_nonce'] ) ), 'subscription_form' ) ) {
			$message = '<p class="subscription-error">Please refresh the page and try again.</p>';
		} elseif ( ! is_email( $email ) ) {
			$message = '<p class="subscription-error">Please enter a valid email address.</p>';
		} elseif ( $is_unsubscribe && subscription_remove_email( $email ) ) {
			$email   = '';
			$message = '<p class="subscription-success">You have been unsubscribed.</p>';
		} elseif ( $is_unsubscribe ) {
			$message = '<p class="subscription-notice">That email address was not subscribed.</p>';
		} elseif ( subscription_email_exists( $email ) ) {
			$message = '<p class="subscription-notice">That email address is already subscribed.</p>';
		} elseif ( subscription_add_email( $email ) ) {
			$email   = '';
			$message = '<p class="subscription-success">You are now subscribed for updates.</p>';
		} else {
			$message = '<p class="subscription-error">We could not save your subscription. Please try again.</p>';
		}
	}

	ob_start();
	?>
	<form class="subscription-form" method="post">
		<?php wp_nonce_field( 'subscription_form', 'subscription_nonce' ); ?>
		<label for="subscription-email">Email address</label>
		<input id="subscription-email" type="email" name="subscription_email" value="<?php echo esc_attr( $email ); ?>" required>
		<button type="submit" name="subscription_submit" value="1">Subscribe</button>
		<button type="submit" name="subscription_unsubscribe" value="1">Unsubscribe</button>
		<?php echo wp_kses_post( $message ); ?>
	</form>
	<?php
	return ob_get_clean();
}

function subscription_email_exists( $email ) {
	global $wpdb;

	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			'SELECT id FROM ' . subscription_table_name() . ' WHERE email = %s LIMIT 1',
			strtolower( $email )
		)
	);
}

function subscription_add_email( $email ) {
	global $wpdb;

	return false !== $wpdb->insert(
		subscription_table_name(),
		array(
			'email'         => strtolower( $email ),
			'subscribed_at' => current_time( 'mysql' ),
		),
		array( '%s', '%s' )
	);
}

add_action( 'admin_menu', 'subscription_register_admin_page' );
add_action( 'admin_post_subscription_export_csv', 'subscription_export_csv' );

function subscription_register_admin_page() {
	add_menu_page(
		'Subscribers',
		'Subscribers',
		'manage_options',
		'subscription-subscribers',
		'subscription_render_admin_page',
		'dashicons-email-alt',
		25
	);
}

function subscription_render_admin_page() {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'subscription' ) );
	}

	$notice       = '';
	$search       = isset( $_GET['subscription_search'] ) ? sanitize_text_field( wp_unslash( $_GET['subscription_search'] ) ) : '';
	$table_name   = subscription_table_name();

	if ( isset( $_POST['subscription_delete_id'] ) ) {
		if ( ! isset( $_POST['subscription_admin_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['subscription_admin_nonce'] ) ), 'subscription_delete' ) ) {
			$notice = '<div class="notice notice-error"><p>Unable to verify the delete request.</p></div>';
		} else {
			$deleted = $wpdb->delete(
				$table_name,
				array( 'id' => absint( $_POST['subscription_delete_id'] ) ),
				array( '%d' )
			);

			$notice = $deleted ? '<div class="notice notice-success"><p>Subscriber deleted.</p></div>' : '<div class="notice notice-error"><p>Subscriber could not be deleted.</p></div>';
		}
	}

	$query = 'SELECT id, email, subscribed_at FROM ' . $table_name;
	$args  = array();

	if ( '' !== $search ) {
		$query .= ' WHERE email LIKE %s';
		$args[] = '%' . $wpdb->esc_like( $search ) . '%';
	}

	$query       .= ' ORDER BY id DESC';
	$subscribers = $args ? $wpdb->get_results( $wpdb->prepare( $query, $args ) ) : $wpdb->get_results( $query );
	?>
	<div class="wrap">
		<h1>Subscribers</h1>
		<?php echo wp_kses_post( $notice ); ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin: 0 0 12px;">
			<input type="hidden" name="action" value="subscription_export_csv">
			<?php wp_nonce_field( 'subscription_export_csv', 'subscription_export_nonce' ); ?>
			<?php submit_button( 'Download CSV', 'primary', '', false ); ?>
		</form>

		<form method="get">
			<input type="hidden" name="page" value="subscription-subscribers">
			<label for="subscription-search">Search by email</label>
			<input id="subscription-search" type="search" name="subscription_search" value="<?php echo esc_attr( $search ); ?>">
			<?php submit_button( 'Search', 'secondary', '', false ); ?>
			<?php if ( '' !== $search ) : ?>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=subscription-subscribers' ) ); ?>">Clear</a>
			<?php endif; ?>
		</form>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col">ID</th>
					<th scope="col">Email</th>
					<th scope="col">Subscribed</th>
					<th scope="col">Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $subscribers ) ) : ?>
					<tr><td colspan="4">No matching subscribers found.</td></tr>
				<?php else : ?>
					<?php foreach ( $subscribers as $subscriber ) : ?>
						<tr>
							<td><?php echo esc_html( $subscriber->id ); ?></td>
							<td><?php echo esc_html( $subscriber->email ); ?></td>
							<td><?php echo esc_html( $subscriber->subscribed_at ); ?></td>
							<td>
								<form method="post">
									<?php wp_nonce_field( 'subscription_delete', 'subscription_admin_nonce' ); ?>
									<input type="hidden" name="subscription_delete_id" value="<?php echo esc_attr( $subscriber->id ); ?>">
									<button type="submit" class="button-link-delete" onclick="return confirm('Delete this subscriber?');">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function subscription_export_csv() {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to export subscribers.', 'subscription' ) );
	}

	check_admin_referer( 'subscription_export_csv', 'subscription_export_nonce' );

	$subscribers = $wpdb->get_results( 'SELECT id, email, subscribed_at FROM ' . subscription_table_name() . ' ORDER BY id ASC', ARRAY_A );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );

	$output = fopen( 'php://output', 'w' );
	fputcsv( $output, array( 'ID', 'Email', 'Subscribed' ) );

	foreach ( $subscribers as $subscriber ) {
		fputcsv( $output, array( $subscriber['id'], $subscriber['email'], $subscriber['subscribed_at'] ) );
	}

	fclose( $output );
	exit;
}

function subscription_remove_email( $email ) {
	global $wpdb;

	return 1 === $wpdb->delete(
		subscription_table_name(),
		array( 'email' => strtolower( $email ) ),
		array( '%s' )
	);
}