<?php
/**
 * Uninstall handler.
 *
 * Removes plugin options, the saves table, and any directory entry meta keys.
 * Demo content (form + sample entries + pages) is left in place because the
 * user may want to keep them; remove explicitly via Forms → Directory →
 * Remove demo before uninstalling if a clean slate is desired.
 *
 * @package GFDirectory
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Drop the saves table.
$table = $wpdb->prefix . 'gfd_saves';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Remove plugin options.
delete_option( 'gfd_saves_db_version' );
delete_option( 'gfd_cache_version' );
delete_option( 'gfd_demo_state' );
delete_option( 'gfd_rewrite_version' );

// Remove per-user dismiss flags.
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'gfd_demo_notice_dismissed'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
