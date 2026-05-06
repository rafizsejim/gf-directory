<?php
/**
 * Pagination: 1 2 3 ... 99 with prev/next arrows.
 *
 * @package GFDirectory\Render
 */

declare( strict_types=1 );

namespace GFDirectory\Render;

use GFDirectory\Query\SearchParser;

defined( 'ABSPATH' ) || exit;

final class PaginationRenderer {

	public function render( int $total, int $per_page, int $current, array $criteria, string $base_url ): string {
		$pages = (int) max( 1, ceil( $total / max( 1, $per_page ) ) );
		if ( $pages <= 1 ) {
			return '';
		}

		$current = max( 1, min( $pages, $current ) );
		$out     = '<nav class="gfd-pagination" aria-label="' . esc_attr__( 'Pagination', 'gf-directory' ) . '">';

		$out .= $this->link( $base_url, $criteria, max( 1, $current - 1 ), '←', $current === 1, 'prev' );

		foreach ( $this->page_numbers( $pages, $current ) as $page ) {
			if ( $page === '…' ) {
				$out .= '<span class="gfd-pagination__gap">…</span>';
				continue;
			}
			$is_current = ( $page === $current );
			$out       .= $this->link( $base_url, $criteria, (int) $page, (string) $page, false, $is_current ? 'current' : 'page' );
		}

		$out .= $this->link( $base_url, $criteria, min( $pages, $current + 1 ), '→', $current === $pages, 'next' );

		return $out . '</nav>';
	}

	/**
	 * @return array<int|string>
	 */
	private function page_numbers( int $pages, int $current ): array {
		$window = 1;
		$out    = [];
		$out[]  = 1;

		if ( $current - $window > 2 ) {
			$out[] = '…';
		}
		for ( $p = max( 2, $current - $window ); $p <= min( $pages - 1, $current + $window ); $p++ ) {
			$out[] = $p;
		}
		if ( $current + $window < $pages - 1 ) {
			$out[] = '…';
		}
		if ( $pages > 1 ) {
			$out[] = $pages;
		}
		return $out;
	}

	private function link( string $base, array $criteria, int $page, string $label, bool $disabled, string $kind ): string {
		if ( $disabled ) {
			return sprintf(
				'<span class="gfd-pagination__item gfd-pagination__item--disabled" aria-disabled="true">%s</span>',
				esc_html( $label )
			);
		}
		$url = SearchParser::build_url( $base, $criteria, [ 'page' => $page ] );
		$cls = 'gfd-pagination__item';
		if ( $kind === 'current' ) {
			$cls .= ' gfd-pagination__item--current';
		}
		return sprintf(
			'<a class="%s" href="%s"%s>%s</a>',
			esc_attr( $cls ),
			esc_url( $url ),
			$kind === 'current' ? ' aria-current="page"' : '',
			esc_html( $label )
		);
	}
}
