<?php
/**
 * WordPress-Admin-UI: Abonnementsliste, Einstellungsseite und POST-Handler.
 *
 * @package Kipphard\WiederVerfuegbar
 */

namespace Kipphard\WiederVerfuegbar;

defined( 'ABSPATH' ) || exit;

/**
 * Registriert Admin-Menüs und verarbeitet Formularabsendungen.
 */
class Admin {

	/** Abonnements pro Seite in der Listenansicht. */
	const PER_PAGE = 50;

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_kipphard_back_in_stock_save_settings', array( $this, 'handle_save_settings' ) );
	}

	/**
	 * Untermenüs unter WooCommerce registrieren.
	 */
	public function register_menus() {
		add_submenu_page(
			'woocommerce',
			__( 'Back in Stock – Subscriptions', 'kipphard-back-in-stock' ),
			__( 'Back in Stock', 'kipphard-back-in-stock' ),
			Helpers::CAP,
			KIPPHARD_BACK_IN_STOCK_SLUG,
			array( $this, 'render_subscriptions' )
		);
		add_submenu_page(
			'woocommerce',
			__( 'Back in Stock – Settings', 'kipphard-back-in-stock' ),
			__( 'Back in Stock Settings', 'kipphard-back-in-stock' ),
			Helpers::CAP,
			KIPPHARD_BACK_IN_STOCK_SLUG . '-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * Assets nur auf den Plugin-Seiten einbinden.
	 *
	 * @param string $hook Aktueller Admin-Seiten-Hook.
	 */
	public function enqueue_assets( $hook ) {
		$pages = array(
			'woocommerce_page_' . KIPPHARD_BACK_IN_STOCK_SLUG,
			'woocommerce_page_' . KIPPHARD_BACK_IN_STOCK_SLUG . '-settings',
		);
		if ( ! in_array( $hook, $pages, true ) ) {
			return;
		}
		wp_enqueue_style(
			'wvb-admin',
			KIPPHARD_BACK_IN_STOCK_URL . 'assets/admin.css',
			array(),
			KIPPHARD_BACK_IN_STOCK_VERSION
		);
		wp_enqueue_script(
			'wvb-admin',
			KIPPHARD_BACK_IN_STOCK_URL . 'assets/admin.js',
			array(),
			KIPPHARD_BACK_IN_STOCK_VERSION,
			true
		);

		// kip-admin.css nur auf der Einstellungsseite.
		if ( 'woocommerce_page_' . KIPPHARD_BACK_IN_STOCK_SLUG . '-settings' === $hook ) {
			if ( is_readable( KIPPHARD_BACK_IN_STOCK_DIR . 'shared/kip-admin.css' ) ) {
				wp_enqueue_style( 'kip-admin', KIPPHARD_BACK_IN_STOCK_URL . 'shared/kip-admin.css', array(), KIPPHARD_BACK_IN_STOCK_VERSION );
			}
		}
	}

	// -------------------------------------------------------------------------
	// POST-Handler
	// -------------------------------------------------------------------------

	/**
	 * Einstellungen speichern.
	 */
	public function handle_save_settings() {
		Helpers::guard_post( 'kipphard_back_in_stock_save_settings' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in Helpers::guard_post() above.
		$clean = Helpers::sanitize_settings( $_POST );
		update_option( Helpers::OPT_SETTINGS, $clean );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => KIPPHARD_BACK_IN_STOCK_SLUG . '-settings',
					'notice' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// Seiten-Renderer
	// -------------------------------------------------------------------------

	/**
	 * Abonnementsliste rendern (paginiert, aus DB).
	 */
	public function render_subscriptions() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}

		global $wpdb;
		$table = Helpers::table();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display value, no state change.
		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$current_page = max( 1, $current_page );
		$offset       = ( $current_page - 1 ) * self::PER_PAGE;

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, product_id, email, status, created FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
				self::PER_PAGE,
				$offset
			),
			ARRAY_A
		);

		$total_pages = $total > 0 ? ceil( $total / self::PER_PAGE ) : 1;
		?>
		<div class="wrap wvb-wrap">
			<h1><?php esc_html_e( 'Back in Stock – Subscriptions', 'kipphard-back-in-stock' ); ?></h1>

			<p>
				<?php
				printf(
					/* translators: %d: Anzahl aktiver Abonnements */
					esc_html__( 'Total active subscriptions: %d', 'kipphard-back-in-stock' ),
					Subscriptions::count_active()
				);
				?>
			</p>

			<?php if ( empty( $rows ) ) : ?>
				<p><?php esc_html_e( 'No subscriptions yet.', 'kipphard-back-in-stock' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped wvb-subscriptions-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'kipphard-back-in-stock' ); ?></th>
							<th><?php esc_html_e( 'Product', 'kipphard-back-in-stock' ); ?></th>
							<th><?php esc_html_e( 'Email', 'kipphard-back-in-stock' ); ?></th>
							<th><?php esc_html_e( 'Status', 'kipphard-back-in-stock' ); ?></th>
							<th><?php esc_html_e( 'Date', 'kipphard-back-in-stock' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<?php
							$product_id   = absint( $row['product_id'] );
							$product      = wc_get_product( $product_id );
							$product_name = $product ? $product->get_name() : sprintf( '#%d', $product_id );
							$product_url  = $product ? get_edit_post_link( $product_id ) : '';
							?>
							<tr>
								<td><?php echo esc_html( $row['id'] ); ?></td>
								<td>
									<?php if ( $product_url ) : ?>
										<a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $product_name ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $product_name ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $row['email'] ); ?></td>
								<td><?php echo esc_html( $row['status'] ); ?></td>
								<td><?php echo esc_html( $row['created'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav bottom">
						<div class="tablenav-pages">
							<?php
							echo paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%' ),
									'format'    => '',
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
									'total'     => $total_pages,
									'current'   => $current_page,
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>

			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Einstellungsseite rendern.
	 */
	public function render_settings() {
		if ( ! current_user_can( Helpers::CAP ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display value, no state change.
		$notice   = isset( $_GET['notice'] ) ? sanitize_key( $_GET['notice'] ) : '';
		$settings = (array) get_option( Helpers::OPT_SETTINGS, array() );
		$defaults = Helpers::defaults();

		$heading       = isset( $settings['heading'] ) ? $settings['heading'] : $defaults['heading'];
		$button_label  = isset( $settings['button_label'] ) ? $settings['button_label'] : $defaults['button_label'];
		$consent_text  = isset( $settings['consent_text'] ) ? $settings['consent_text'] : $defaults['consent_text'];
		$email_subject = isset( $settings['email_subject'] ) ? $settings['email_subject'] : $defaults['email_subject'];
		$email_body    = isset( $settings['email_body'] ) ? $settings['email_body'] : $defaults['email_body'];
		$msg_success   = isset( $settings['msg_success'] ) ? $settings['msg_success'] : $defaults['msg_success'];
		$msg_error     = isset( $settings['msg_error'] ) ? $settings['msg_error'] : $defaults['msg_error'];
		?>
		<div class="wrap wvb-wrap kip-admin">
			<h1><?php esc_html_e( 'Back in Stock – Settings', 'kipphard-back-in-stock' ); ?><span class="kip-admin__suite">Kipphard</span></h1>

			<?php if ( 'saved' === $notice ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'kipphard-back-in-stock' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="kipphard_back_in_stock_save_settings">
				<?php wp_nonce_field( 'kipphard_back_in_stock_save_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="wvb-heading"><?php esc_html_e( 'Form Heading', 'kipphard-back-in-stock' ); ?></label>
						</th>
						<td>
							<input type="text" id="wvb-heading" name="heading" class="regular-text"
								value="<?php echo esc_attr( $heading ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wvb-button-label"><?php esc_html_e( 'Button Label', 'kipphard-back-in-stock' ); ?></label>
						</th>
						<td>
							<input type="text" id="wvb-button-label" name="button_label" class="regular-text"
								value="<?php echo esc_attr( $button_label ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wvb-consent-text"><?php esc_html_e( 'Consent Text (GDPR)', 'kipphard-back-in-stock' ); ?></label>
						</th>
						<td>
							<input type="text" id="wvb-consent-text" name="consent_text" class="large-text"
								value="<?php echo esc_attr( $consent_text ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wvb-email-subject"><?php esc_html_e( 'Email Subject', 'kipphard-back-in-stock' ); ?></label>
						</th>
						<td>
							<input type="text" id="wvb-email-subject" name="email_subject" class="large-text"
								value="<?php echo esc_attr( $email_subject ); ?>">
							<p class="description">
								<?php esc_html_e( 'Placeholder: {product}', 'kipphard-back-in-stock' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wvb-email-body"><?php esc_html_e( 'Email Body', 'kipphard-back-in-stock' ); ?></label>
						</th>
						<td>
							<textarea id="wvb-email-body" name="email_body" rows="8" class="large-text"><?php echo esc_textarea( $email_body ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Placeholders: {product}, {link}', 'kipphard-back-in-stock' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wvb-msg-success"><?php esc_html_e( 'Success Message', 'kipphard-back-in-stock' ); ?></label>
						</th>
						<td>
							<input type="text" id="wvb-msg-success" name="msg_success" class="large-text"
								value="<?php echo esc_attr( $msg_success ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="wvb-msg-error"><?php esc_html_e( 'Error Message', 'kipphard-back-in-stock' ); ?></label>
						</th>
						<td>
							<input type="text" id="wvb-msg-error" name="msg_error" class="large-text"
								value="<?php echo esc_attr( $msg_error ); ?>">
						</td>
					</tr>
				</table>

				<?php if ( class_exists( '\Kipphard\Shared\Appearance' ) ) : ?>
					<h2 class="title"><?php esc_html_e( 'Appearance', 'kipphard-back-in-stock' ); ?></h2>
					<table class="form-table" role="presentation">
						<?php \Kipphard\Shared\Appearance::render_fields( $settings ); ?>
					</table>
				<?php endif; ?>

				<?php submit_button( __( 'Save Settings', 'kipphard-back-in-stock' ) ); ?>
			</form>

		</div>
		<?php
	}
}
