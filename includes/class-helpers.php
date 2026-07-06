<?php
/**
 * Gemeinsame Hilfsmethoden: Rechte, Optionen, Sanitisierung.
 *
 * @package Kipphard\WiederVerfuegbar
 */

namespace Kipphard\WiederVerfuegbar;

defined( 'ABSPATH' ) || exit;

/**
 * Zustandslose Hilfsmethoden, die im gesamten Plugin genutzt werden.
 */
class Helpers {

	/** Erforderliche Berechtigung für alle Admin-Aktionen. */
	const CAP = 'manage_options';

	/** Options-Key für die Plugin-Einstellungen. */
	const OPT_SETTINGS = 'kipphard_back_in_stock_settings';

	/**
	 * Gibt den vollständigen Tabellennamen zurück.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'kipphard_back_in_stock_subscriptions';
	}

	/**
	 * Liefert die Standard-Einstellungen des Plugins.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		$base = array(
			'heading'       => 'Notify me when back in stock',
			'button_label'  => 'Notify me',
			'consent_text'  => 'I agree to be notified by email as soon as this product is available again.',
			'email_subject' => 'Back in stock: {product}',
			'email_body'    => "Hello,\n\nthe product \"{product}\" is now back in stock.\n\nBuy now: {link}\n\nBest regards",
			'msg_success'   => 'Thank you! We will notify you as soon as the product is available again.',
			'msg_error'     => 'An error occurred. Please try again.',
		);
		if ( class_exists( '\Kipphard\Shared\Appearance' ) ) {
			$base = array_merge( $base, \Kipphard\Shared\Appearance::defaults() );
		}
		return $base;
	}

	/**
	 * Liest eine einzelne Einstellung (mit Fallback auf den Standardwert).
	 *
	 * @param string $key Einstellungsschlüssel.
	 * @return mixed
	 */
	public static function get( $key ) {
		$settings = (array) get_option( self::OPT_SETTINGS, array() );
		$defaults = self::defaults();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : null );
	}

	/**
	 * Sanitisiert die Einstellungsfelder streng pro Feld.
	 *
	 * @param array<string,mixed> $raw Rohe $_POST-Daten.
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( array $raw ) {
		$defaults = self::defaults();

		$clean = array(
			'heading'       => isset( $raw['heading'] ) ? sanitize_text_field( wp_unslash( $raw['heading'] ) ) : $defaults['heading'],
			'button_label'  => isset( $raw['button_label'] ) ? sanitize_text_field( wp_unslash( $raw['button_label'] ) ) : $defaults['button_label'],
			'consent_text'  => isset( $raw['consent_text'] ) ? sanitize_text_field( wp_unslash( $raw['consent_text'] ) ) : $defaults['consent_text'],
			'email_subject' => isset( $raw['email_subject'] ) ? sanitize_text_field( wp_unslash( $raw['email_subject'] ) ) : $defaults['email_subject'],
			'email_body'    => isset( $raw['email_body'] ) ? sanitize_textarea_field( wp_unslash( $raw['email_body'] ) ) : $defaults['email_body'],
			'msg_success'   => isset( $raw['msg_success'] ) ? sanitize_text_field( wp_unslash( $raw['msg_success'] ) ) : $defaults['msg_success'],
			'msg_error'     => isset( $raw['msg_error'] ) ? sanitize_text_field( wp_unslash( $raw['msg_error'] ) ) : $defaults['msg_error'],
		);

		if ( class_exists( '\Kipphard\Shared\Appearance' ) ) {
			$clean = array_merge( $clean, \Kipphard\Shared\Appearance::sanitize( $raw ) );
		}

		return $clean;
	}

	/**
	 * Prüft einen Admin-POST-Request: Berechtigung + Nonce. Bricht bei Fehler ab.
	 *
	 * @param string $action Nonce-Aktion.
	 * @param string $field  Nonce-Feldname.
	 */
	public static function guard_post( $action, $field = '_wpnonce' ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'kipphard-back-in-stock' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $action, $field );
	}
}
