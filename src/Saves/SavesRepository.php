<?php
/**
 * Read/write access to the saves table.
 *
 * @package GFDirectory\Saves
 */

declare( strict_types=1 );

namespace GFDirectory\Saves;

defined( 'ABSPATH' ) || exit;

final class SavesRepository {

	public function is_saved( int $user_id, int $entry_id ): bool {
		if ( $user_id <= 0 || $entry_id <= 0 ) {
			return false;
		}
		global $wpdb;
		$table = SavesTable::table_name();
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND entry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$entry_id
			)
		);
		return $count > 0;
	}

	public function save( int $user_id, int $form_id, int $entry_id ): bool {
		if ( $user_id <= 0 || $form_id <= 0 || $entry_id <= 0 ) {
			return false;
		}
		global $wpdb;
		$table = SavesTable::table_name();

		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$table} (user_id, form_id, entry_id, saved_at) VALUES (%d, %d, %d, %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$form_id,
				$entry_id,
				current_time( 'mysql', true )
			)
		);

		return true;
	}

	public function unsave( int $user_id, int $entry_id ): bool {
		if ( $user_id <= 0 || $entry_id <= 0 ) {
			return false;
		}
		global $wpdb;
		$table = SavesTable::table_name();
		$wpdb->delete( $table, [ 'user_id' => $user_id, 'entry_id' => $entry_id ], [ '%d', '%d' ] );
		return true;
	}

	/**
	 * Toggle. Returns true if the entry is now saved, false if just removed.
	 */
	public function toggle( int $user_id, int $form_id, int $entry_id ): bool {
		if ( $this->is_saved( $user_id, $entry_id ) ) {
			$this->unsave( $user_id, $entry_id );
			return false;
		}
		$this->save( $user_id, $form_id, $entry_id );
		return true;
	}

	/**
	 * Saved entry IDs for a user, optionally scoped to a form.
	 *
	 * @return int[]
	 */
	public function saved_entry_ids( int $user_id, ?int $form_id = null ): array {
		if ( $user_id <= 0 ) {
			return [];
		}
		global $wpdb;
		$table = SavesTable::table_name();

		if ( $form_id !== null && $form_id > 0 ) {
			$rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT entry_id FROM {$table} WHERE user_id = %d AND form_id = %d ORDER BY saved_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_id,
					$form_id
				)
			);
		} else {
			$rows = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT entry_id FROM {$table} WHERE user_id = %d ORDER BY saved_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_id
				)
			);
		}

		return array_map( 'intval', (array) $rows );
	}

	/**
	 * Saved entries grouped by form ID, returned in a single query.
	 *
	 * @return array<int,int[]>
	 */
	public function saved_grouped( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return [];
		}
		global $wpdb;
		$table = SavesTable::table_name();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT form_id, entry_id FROM {$table} WHERE user_id = %d ORDER BY saved_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			)
		);

		$out = [];
		foreach ( (array) $rows as $row ) {
			$fid = (int) $row->form_id;
			$eid = (int) $row->entry_id;
			$out[ $fid ][] = $eid;
		}
		return $out;
	}
}
