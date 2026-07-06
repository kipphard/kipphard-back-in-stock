<?php
/**
 * PSR-4 autoloader for the shared Kipphard\Shared\ namespace.
 *
 * Injected into each plugin build at <slug>/shared/ by tools/build-zip.mjs and
 * required (guarded) from the plugin's main file. Mirrors each plugin's own
 * autoloader convention: Kipphard\Shared\Foo_Bar -> class-foo-bar.php.
 *
 * @package Kipphard\Shared
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'Kipphard\\Shared\\';
		if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$file     = 'class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		$path     = __DIR__ . '/' . $file;
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

// Load the shared 'kipphard-back-in-stock' text domain (the Appearance UI strings). English is the
// source/baseline; a de_DE .mo ships alongside for German sites. Loaded once per
// request when a plugin boots (plugins_loaded), guarded for older WP.
if ( function_exists( 'determine_locale' ) && function_exists( 'load_textdomain' ) ) {
	$kipphard_back_in_stock_mo = __DIR__ . '/languages/kip-' . determine_locale() . '.mo';
	if ( is_readable( $kipphard_back_in_stock_mo ) ) {
		load_textdomain( 'kipphard-back-in-stock', $kipphard_back_in_stock_mo );
	}
}
