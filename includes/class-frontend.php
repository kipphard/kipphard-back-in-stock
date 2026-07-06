<?php
/**
 * Frontend-Hooks: Formular auf ausverkauften Produktseiten + AJAX-Handler.
 *
 * @package Kipphard\WiederVerfuegbar
 */

namespace Kipphard\WiederVerfuegbar;

defined( 'ABSPATH' ) || exit;

/**
 * Rendert das „Benachrichtige mich"-Formular und verarbeitet AJAX-Subscribes.
 */
class Frontend {

	/**
	 * Hooks registrieren.
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_form' ), 35 );
		add_action( 'wp_ajax_kipphard_back_in_stock_subscribe', array( $this, 'ajax_subscribe' ) );
		add_action( 'wp_ajax_nopriv_kipphard_back_in_stock_subscribe', array( $this, 'ajax_subscribe' ) );
	}

	/**
	 * Assets einbinden (nur auf Produktseiten nötig).
	 */
	public function enqueue_assets() {
		if ( ! is_product() ) {
			return;
		}

		// Shared design system (kip-ui) — load first so plugin CSS can read its variables.
		$settings = (array) get_option( Helpers::OPT_SETTINGS, array() );
		$has_kip  = class_exists( '\Kipphard\Shared\Appearance' );
		$styled   = ! $has_kip || \Kipphard\Shared\Appearance::is_enabled( $settings );
		$deps     = array();

		if ( $has_kip && $styled && is_readable( KIPPHARD_BACK_IN_STOCK_DIR . 'shared/kip-ui.css' ) ) {
			wp_enqueue_style( 'kip-ui', KIPPHARD_BACK_IN_STOCK_URL . 'shared/kip-ui.css', array(), KIPPHARD_BACK_IN_STOCK_VERSION );
			wp_add_inline_style( 'kip-ui', \Kipphard\Shared\Appearance::css( $settings, '.kip-ui.kip-back-in-stock' ) );
			$deps[] = 'kip-ui';
		}

		wp_enqueue_style(
			'wvb-frontend',
			KIPPHARD_BACK_IN_STOCK_URL . 'assets/frontend.css',
			$deps,
			KIPPHARD_BACK_IN_STOCK_VERSION
		);
		wp_enqueue_script(
			'wvb-frontend',
			KIPPHARD_BACK_IN_STOCK_URL . 'assets/frontend.js',
			array(),
			KIPPHARD_BACK_IN_STOCK_VERSION,
			true
		);
		wp_localize_script(
			'wvb-frontend',
			'wvbData',
			array(
				'ajaxUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'nonce'   => wp_create_nonce( 'kipphard_back_in_stock_subscribe' ),
				'i18n'    => array(
					'success' => Helpers::get( 'msg_success' ),
					'error'   => Helpers::get( 'msg_error' ),
				),
			)
		);
	}

	/**
	 * Rendert das „Benachrichtige mich"-Formular auf der Produktseite.
	 * Nur wenn das Produkt ausverkauft ist.
	 */
	public function render_form() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		if ( $product->is_in_stock() ) {
			return;
		}

		$heading      = esc_html( Helpers::get( 'heading' ) );
		$button_label = esc_html( Helpers::get( 'button_label' ) );
		$consent_text = esc_html( Helpers::get( 'consent_text' ) );
		$product_id   = absint( $product->get_id() );

		$settings   = (array) get_option( Helpers::OPT_SETTINGS, array() );
		$styled     = class_exists( '\Kipphard\Shared\Appearance' ) && \Kipphard\Shared\Appearance::is_enabled( $settings );
		$wrap_class = $styled ? 'kip-ui kip-back-in-stock wvb-notify-wrap' : 'wvb-notify-wrap';
		$kip_atts   = $styled ? \Kipphard\Shared\Appearance::data_atts( $settings ) : '';
		?>
		<div class="<?php echo esc_attr( $wrap_class ); ?>" id="wvb-notify-wrap"<?php echo $kip_atts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped in Appearance::data_atts(). ?>>
			<h3 class="wvb-heading"><?php echo $heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></h3>

			<div class="wvb-message kip-notice" aria-live="polite" style="display:none;"></div>

			<form class="wvb-form" id="wvb-form" novalidate>
				<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">

				<div class="wvb-field kip-field">
					<label for="wvb-email">
						<?php esc_html_e( 'Email address', 'kipphard-back-in-stock' ); ?>
						<span class="wvb-required" aria-hidden="true">*</span>
					</label>
					<input
						type="email"
						id="wvb-email"
						name="email"
						required
						autocomplete="email"
						placeholder="<?php esc_attr_e( 'your@email.com', 'kipphard-back-in-stock' ); ?>"
					>
				</div>

				<div class="wvb-field wvb-consent-field kip-field">
					<label class="wvb-consent-label">
						<input type="checkbox" name="consent" value="1" required>
						<?php echo $consent_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
						<span class="wvb-required" aria-hidden="true">*</span>
					</label>
				</div>

				<button type="submit" class="kip-btn kip-btn--primary wvb-submit">
					<?php echo $button_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
				</button>
			</form>
		</div>
		<?php
	}

	/**
	 * AJAX: E-Mail für Benachrichtigung eintragen.
	 * Öffentlich → Nonce + Produktvalidierung + Ausverkauft-Prüfung zwingend.
	 */
	public function ajax_subscribe() {
		check_ajax_referer( 'kipphard_back_in_stock_subscribe', 'nonce' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in check_ajax_referer() above.
		$product_id = absint( isset( $_POST['product_id'] ) ? $_POST['product_id'] : 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in check_ajax_referer() above.
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in check_ajax_referer() above.
		$consent    = ! empty( $_POST['consent'] );

		// E-Mail validieren.
		if ( ! is_email( $email ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Please enter a valid email address.', 'kipphard-back-in-stock' ) ),
				400
			);
		}

		// DSGVO-Einwilligung erforderlich.
		if ( ! $consent ) {
			wp_send_json_error(
				array( 'message' => __( 'Please agree to receive the notification.', 'kipphard-back-in-stock' ) ),
				400
			);
		}

		// Produkt muss existieren und veröffentlicht sein.
		if ( $product_id <= 0 ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid product.', 'kipphard-back-in-stock' ) ),
				400
			);
		}

		$post = get_post( $product_id );
		if ( ! $post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid product.', 'kipphard-back-in-stock' ) ),
				400
			);
		}

		// Produkt muss tatsächlich ausverkauft sein.
		$product = wc_get_product( $product_id );
		if ( ! $product || $product->is_in_stock() ) {
			wp_send_json_error(
				array( 'message' => __( 'This product is already in stock.', 'kipphard-back-in-stock' ) ),
				400
			);
		}

		// Double-Opt-in (Pro-Add-on): nur verfügbar, wenn die Pro-Klasse geladen ist.
		if ( class_exists( __NAMESPACE__ . '\\Double_Optin' ) && Double_Optin::is_enabled() ) {
			Double_Optin::initiate( $product_id, $email );
			wp_send_json_success(
				array( 'message' => __( 'Please confirm your email address. We have sent you an email.', 'kipphard-back-in-stock' ) )
			);
		}

		$added = Subscriptions::add( $product_id, $email );

		if ( false === $added ) {
			// Bereits eingetragen – trotzdem positives Feedback geben (kein Datenleck).
			wp_send_json_success(
				array( 'message' => esc_html( Helpers::get( 'msg_success' ) ) )
			);
		}

		wp_send_json_success(
			array( 'message' => esc_html( Helpers::get( 'msg_success' ) ) )
		);
	}
}
