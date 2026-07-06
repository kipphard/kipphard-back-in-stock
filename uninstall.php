<?php
/**
 * Plugin-Deinstallation: Option und benutzerdefinierte Tabelle entfernen.
 *
 * @package Kipphard\WiederVerfuegbar
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'kipphard_back_in_stock_settings' );

$table = $wpdb->prefix . 'kipphard_back_in_stock_subscriptions';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
