<?php
/**
 * Custom table for user saves.
 *
 * Custom table over user_meta because user_meta scales poorly for indexed,
 * paginated lookups by (user_id, form_id) and bloats wp_usermeta over time.
 *
 * @package GFDirectory\Saves
 */

declare( strict_types=1 );

namespace GFDirectory\Saves;

defined( 'ABSPATH' ) || exit;

final class SavesTable {

	private const VERSION_OPT = 'gfd_saves_db_version';
	private const VERSION     = '1';

	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'gfd_saves';
	}

	public static function maybe_install(): void {
		if ( get_option( self::VERSION_OPT ) === self::VERSION ) {
			return;
		}

		global $wpdb;
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			form_id BIGINT UNSIGNED NOT NULL,
			entry_id BIGINT UNSIGNED NOT NULL,
			saved_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_entry (user_id, entry_id),
			KEY user_form (user_id, form_id),
			KEY entry (entry_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::VERSION_OPT, self::VERSION, false );
	}
}
