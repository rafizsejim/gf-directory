<?php
/**
 * Transient cache for query results.
 *
 * Key shape: gfd_q_{form_id}_{sha1(criteria)}. Busted on every event that
 * could change which entries should appear: new submissions, edits, deletes,
 * and our approval-toggle action.
 *
 * @package GFDirectory\Query
 */

declare( strict_types=1 );

namespace GFDirectory\Query;

defined( 'ABSPATH' ) || exit;

final class Cache {

	private const TTL          = 10 * MINUTE_IN_SECONDS;
	private const VERSION_OPT  = 'gfd_cache_version';

	public function register(): void {
		add_action( 'gform_after_submission',     [ $this, 'bust' ], 10, 2 );
		add_action( 'gform_post_update_entry',    [ $this, 'bust_by_entry' ], 10, 2 );
		add_action( 'gform_delete_entry',         [ $this, 'bust_by_id' ] );
		add_action( 'gfd_entry_publication_changed', [ $this, 'bust_by_id' ] );
	}

	public function key( int $form_id, array $criteria ): string {
		ksort( $criteria );
		$payload = wp_json_encode( $criteria );
		$version = (int) get_option( self::VERSION_OPT, 1 );
		return sprintf( 'gfd_q_%d_v%d_%s', $form_id, $version, sha1( (string) $payload ) );
	}

	public function get( string $key ) {
		return get_transient( $key );
	}

	public function set( string $key, $value ): void {
		set_transient( $key, $value, self::TTL );
	}

	public function bust( $entry_or_id, $form = null ): void {
		// Cheapest correct invalidation: bump a global version counter that
		// is folded into every key. No need to scan for matching transients.
		$current = (int) get_option( self::VERSION_OPT, 1 );
		update_option( self::VERSION_OPT, $current + 1, false );
	}

	public function bust_by_entry( $entry, $original ): void {
		$this->bust( $entry );
	}

	public function bust_by_id( $entry_id ): void {
		$this->bust( $entry_id );
	}
}
