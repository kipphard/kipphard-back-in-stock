<?php
/**
 * Shared "Appearance" layer for the Kipphard plugin suite.
 *
 * Gives every plugin a consistent, theme-safe, customizable look driven by the
 * kip-ui CSS variables. The plugin stores these keys inside its own settings
 * option; this class supplies the defaults, sanitisation, the admin fields, the
 * scoped container attributes and the raw inline CSS (enqueued via
 * wp_add_inline_style — never echoed as a <style> tag).
 *
 * All appearance options are free and fully functional (no license gating, no
 * arbitrary-code field) so the layer is WordPress.org-compliant for every build.
 *
 * @package Kipphard\Shared
 */

namespace Kipphard\Shared;

defined( 'ABSPATH' ) || exit;

/**
 * Stateless helpers for the shared appearance layer.
 */
class Appearance {

	const PRESETS = array( 'theme', 'soft', 'bold', 'minimal' );
	const LAYOUTS = array( 'list', 'grid', 'table' );

	/**
	 * Default appearance options (merged into a plugin's settings array).
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'kip_style_enabled' => true,
			'kip_preset'        => 'theme',
			'kip_accent'        => '#2563eb',
			'kip_layout'        => 'grid',
			'kip_scheme'        => 'auto',
			'kip_font'          => '',
			'kip_radius'        => '',
		);
	}

	/**
	 * Preset definitions (key => label).
	 *
	 * @return array<string,string>
	 */
	public static function presets() {
		return array(
			'theme'   => __( 'Theme (adaptive)', 'kipphard-back-in-stock' ),
			'soft'    => __( 'Soft', 'kipphard-back-in-stock' ),
			'bold'    => __( 'Bold', 'kipphard-back-in-stock' ),
			'minimal' => __( 'Minimal', 'kipphard-back-in-stock' ),
		);
	}

	/**
	 * Whether the plugin styling layer is switched on.
	 *
	 * @param array<string,mixed> $opts Settings.
	 * @return bool
	 */
	public static function is_enabled( array $opts ) {
		return ! empty( $opts['kip_style_enabled'] );
	}

	/**
	 * Sanitise the appearance subset out of raw $_POST. All options are free.
	 *
	 * @param array<string,mixed> $raw Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $raw ) {
		$d = self::defaults();

		$preset = isset( $raw['kip_preset'] ) ? sanitize_key( $raw['kip_preset'] ) : $d['kip_preset'];
		if ( ! in_array( $preset, self::PRESETS, true ) ) {
			$preset = $d['kip_preset'];
		}

		$layout = isset( $raw['kip_layout'] ) ? sanitize_key( $raw['kip_layout'] ) : $d['kip_layout'];
		if ( ! in_array( $layout, self::LAYOUTS, true ) ) {
			$layout = $d['kip_layout'];
		}

		$scheme = isset( $raw['kip_scheme'] ) ? sanitize_key( $raw['kip_scheme'] ) : $d['kip_scheme'];
		if ( ! in_array( $scheme, array( 'auto', 'light', 'dark' ), true ) ) {
			$scheme = $d['kip_scheme'];
		}

		$accent = isset( $raw['kip_accent'] ) ? sanitize_hex_color( wp_unslash( $raw['kip_accent'] ) ) : '';
		if ( ! $accent ) {
			$accent = $d['kip_accent'];
		}

		$font = '';
		if ( ! empty( $raw['kip_font'] ) ) {
			// Allow a font-family list: letters, numbers, spaces, comma, hyphen, quotes.
			$font = trim( preg_replace( '/[^a-zA-Z0-9 ,\'"_-]/', '', wp_unslash( $raw['kip_font'] ) ) );
		}

		$radius = '';
		if ( isset( $raw['kip_radius'] ) && '' !== $raw['kip_radius'] ) {
			$radius = (string) min( 40, absint( $raw['kip_radius'] ) );
		}

		return array(
			'kip_style_enabled' => ! empty( $raw['kip_style_enabled'] ),
			'kip_preset'        => $preset,
			'kip_accent'        => $accent,
			'kip_layout'        => $layout,
			'kip_scheme'        => $scheme,
			'kip_font'          => $font,
			'kip_radius'        => $radius,
		);
	}

	/**
	 * Merge stored options over the defaults so every key is present.
	 *
	 * @param array<string,mixed> $opts Stored settings.
	 * @return array<string,mixed>
	 */
	public static function resolve( array $opts ) {
		return array_merge( self::defaults(), array_intersect_key( $opts, self::defaults() ) );
	}

	/**
	 * Data attributes for the scoped .kip-ui wrapper (preset + scheme), escaped.
	 *
	 * @param array<string,mixed> $opts Settings.
	 * @return string
	 */
	public static function data_atts( array $opts ) {
		$opts = self::resolve( $opts );
		return ' data-kip-preset="' . esc_attr( $opts['kip_preset'] ) . '"'
			. ' data-kip-scheme="' . esc_attr( $opts['kip_scheme'] ) . '"';
	}

	/**
	 * The resolved layout key (list|grid|table).
	 *
	 * @param array<string,mixed> $opts Settings.
	 * @return string
	 */
	public static function layout( array $opts ) {
		$opts = self::resolve( $opts );
		return $opts['kip_layout'];
	}

	/**
	 * Raw inline CSS overriding the kip-ui tokens for the given scope selector.
	 * Pass the return value to wp_add_inline_style() (never echo it as a <style>).
	 * Returns '' when styling is disabled or nothing needs overriding.
	 *
	 * @param array<string,mixed> $opts     Settings (already sanitised on save).
	 * @param string              $selector Scope selector, e.g. ".kip-ui.kip-wishlist".
	 * @return string
	 */
	public static function css( array $opts, $selector ) {
		$opts = self::resolve( $opts );
		if ( ! self::is_enabled( $opts ) ) {
			return '';
		}

		$decls = '';
		if ( $opts['kip_accent'] ) {
			$decls .= '--kip-accent:' . $opts['kip_accent'] . ';';
			$decls .= '--kip-accent-hover:color-mix(in srgb,' . $opts['kip_accent'] . ' 84%,#000);';
		}
		if ( '' !== $opts['kip_font'] ) {
			$decls .= '--kip-font:' . $opts['kip_font'] . ',inherit;';
		}
		if ( '' !== $opts['kip_radius'] ) {
			$decls .= '--kip-radius:' . absint( $opts['kip_radius'] ) . 'px;';
		}

		return '' === $decls ? '' : $selector . '{' . $decls . '}';
	}

	/**
	 * Render the admin settings rows (form-table <tr>s) for the appearance
	 * section. Field names match the keys consumed by sanitize().
	 *
	 * @param array<string,mixed> $opts Settings.
	 * @return void
	 */
	public static function render_fields( array $opts ) {
		$opts    = self::resolve( $opts );
		$presets = self::presets();
		?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Use plugin styling', 'kipphard-back-in-stock' ); ?></th>
			<td>
				<label>
					<input type="checkbox" name="kip_style_enabled" value="1" <?php checked( self::is_enabled( $opts ) ); ?>>
					<?php esc_html_e( 'Apply the built-in design (uncheck to inherit your theme completely).', 'kipphard-back-in-stock' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="kip-preset"><?php esc_html_e( 'Design preset', 'kipphard-back-in-stock' ); ?></label></th>
			<td>
				<select id="kip-preset" name="kip_preset">
					<?php foreach ( $presets as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $opts['kip_preset'], $key ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'A one-click look. "Theme" blends into your site; "Soft" is the modern default.', 'kipphard-back-in-stock' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="kip-accent"><?php esc_html_e( 'Accent colour', 'kipphard-back-in-stock' ); ?></label></th>
			<td>
				<input type="color" id="kip-accent" name="kip_accent" value="<?php echo esc_attr( $opts['kip_accent'] ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="kip-layout"><?php esc_html_e( 'Layout', 'kipphard-back-in-stock' ); ?></label></th>
			<td>
				<select id="kip-layout" name="kip_layout">
					<option value="grid" <?php selected( $opts['kip_layout'], 'grid' ); ?>><?php esc_html_e( 'Grid (cards)', 'kipphard-back-in-stock' ); ?></option>
					<option value="list" <?php selected( $opts['kip_layout'], 'list' ); ?>><?php esc_html_e( 'List', 'kipphard-back-in-stock' ); ?></option>
					<option value="table" <?php selected( $opts['kip_layout'], 'table' ); ?>><?php esc_html_e( 'Table', 'kipphard-back-in-stock' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="kip-scheme"><?php esc_html_e( 'Colour scheme', 'kipphard-back-in-stock' ); ?></label></th>
			<td>
				<select id="kip-scheme" name="kip_scheme">
					<option value="auto" <?php selected( $opts['kip_scheme'], 'auto' ); ?>><?php esc_html_e( 'Auto (follow device)', 'kipphard-back-in-stock' ); ?></option>
					<option value="light" <?php selected( $opts['kip_scheme'], 'light' ); ?>><?php esc_html_e( 'Light', 'kipphard-back-in-stock' ); ?></option>
					<option value="dark" <?php selected( $opts['kip_scheme'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'kipphard-back-in-stock' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="kip-font"><?php esc_html_e( 'Font family', 'kipphard-back-in-stock' ); ?></label></th>
			<td>
				<input type="text" id="kip-font" name="kip_font" class="regular-text" value="<?php echo esc_attr( $opts['kip_font'] ); ?>"
					placeholder="<?php esc_attr_e( 'Inherit theme font', 'kipphard-back-in-stock' ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="kip-radius"><?php esc_html_e( 'Corner radius (px)', 'kipphard-back-in-stock' ); ?></label></th>
			<td>
				<input type="number" id="kip-radius" name="kip_radius" min="0" max="40" value="<?php echo esc_attr( $opts['kip_radius'] ); ?>"
					placeholder="<?php esc_attr_e( 'Preset default', 'kipphard-back-in-stock' ); ?>">
			</td>
		</tr>
		<?php
	}
}
