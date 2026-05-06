<?php
/**
 * Adds a `/listing/{entry_id}/` endpoint that can be appended to any page
 * hosting [gf_directory]. Uses add_rewrite_endpoint so we never have to know
 * the parent page's slug.
 *
 * @package GFDirectory\Frontend
 */

declare( strict_types=1 );

namespace GFDirectory\Frontend;

defined( 'ABSPATH' ) || exit;

final class Rewrite {

	public const ENDPOINT  = 'listing';
	public const QUERY_VAR = 'gfd_entry';

	private const VERSION_OPT = 'gfd_rewrite_version';
	private const VERSION     = '1';

	public function register(): void {
		add_action( 'init', [ $this, 'add_endpoint' ] );
		add_action( 'init', [ $this, 'maybe_flush' ], 99 );
		add_filter( 'query_vars', [ $this, 'add_query_var' ] );
	}

	public function add_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_PAGES, self::QUERY_VAR );
	}

	/**
	 * Self-healing flush. Runs once whenever the stored rewrite version differs
	 * from the code constant, and is a cheap option lookup otherwise. Means
	 * users never have to manually visit Settings → Permalinks after a
	 * plugin update that changes rewrite rules.
	 */
	public function maybe_flush(): void {
		if ( get_option( self::VERSION_OPT ) === self::VERSION ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( self::VERSION_OPT, self::VERSION, false );
	}

	public function add_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Build the pretty URL for a single entry, given the page that hosts the
	 * directory shortcode. Falls back to a query-string URL if the rewrite
	 * endpoint is unavailable for any reason.
	 */
	public static function entry_url( string $base_page_url, int $entry_id ): string {
		if ( $base_page_url === '' ) {
			$base_page_url = home_url( '/' );
		}
		if ( get_option( 'permalink_structure' ) === '' ) {
			return add_query_arg( self::QUERY_VAR, $entry_id, $base_page_url );
		}
		return trailingslashit( $base_page_url ) . self::ENDPOINT . '/' . $entry_id . '/';
	}
}
