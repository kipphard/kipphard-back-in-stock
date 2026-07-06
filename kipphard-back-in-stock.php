<?php
/**
 * Plugin Name:       Kipphard Back in Stock for WooCommerce
 * Plugin URI:        https://kipphard.com/products/wieder-verfuegbar
 * Description:       Notifies customers by email when an out-of-stock WooCommerce product becomes available again. Clean UX, honest scope.
 * Version:           0.4.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            André Kipphard
 * Author URI:        https://kipphard.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kipphard-back-in-stock
 * Domain Path:       /languages
 *
 * @package Kipphard\WiederVerfuegbar
 */

defined( 'ABSPATH' ) || exit;

define( 'KIPPHARD_BACK_IN_STOCK_VERSION', '0.4.0' );
define( 'KIPPHARD_BACK_IN_STOCK_FILE', __FILE__ );
define( 'KIPPHARD_BACK_IN_STOCK_DIR', plugin_dir_path( __FILE__ ) );
define( 'KIPPHARD_BACK_IN_STOCK_URL', plugin_dir_url( __FILE__ ) );
define( 'KIPPHARD_BACK_IN_STOCK_SLUG', 'kipphard-back-in-stock' );

/**
 * Minimaler PSR-4-Autoloader für den Kipphard\WiederVerfuegbar\-Namespace.
 * Kipphard\WiederVerfuegbar\Foo_Bar → includes/class-foo-bar.php
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Kipphard\\WiederVerfuegbar\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		$path     = KIPPHARD_BACK_IN_STOCK_DIR . 'includes/' . $file;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

// Shared design system (kip-ui). Injected into the build at /shared by build-zip;
// guarded so the plugin still runs unstyled if it's absent.
$kipphard_back_in_stock_shared_autoload = KIPPHARD_BACK_IN_STOCK_DIR . 'shared/autoload.php';
if ( is_readable( $kipphard_back_in_stock_shared_autoload ) ) {
	require_once $kipphard_back_in_stock_shared_autoload;
}

register_activation_hook( __FILE__, array( '\Kipphard\WiederVerfuegbar\Plugin', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\Kipphard\WiederVerfuegbar\Plugin::instance()->boot();
	}
);
