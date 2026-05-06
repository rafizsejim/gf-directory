<?php
/**
 * Wraps GFAPI::get_entries with the directory-public meta filter.
 *
 * One DB round trip per page render: GF's GF_Query returns entries plus the
 * total found rows so pagination needs no second query.
 *
 * @package GFDirectory\Query
 */

declare( strict_types=1 );

namespace GFDirectory\Query;

use GFDirectory\Settings\FormSettings;

defined( 'ABSPATH' ) || exit;

final class EntryQuery {

	public const META_PUBLIC_KEY = '_gfd_public';

	private FormSettings $settings;

	public function __construct( FormSettings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @param array $criteria See SearchParser::to_criteria. Keys: q, filters, date_from, date_to, sort, page.
	 * @return array{entries: array<int,array>, total: int, page: int, per_page: int}
	 */
	public function run( array $criteria ): array {
		$per_page = $this->settings->per_page();
		$page     = max( 1, (int) ( $criteria['page'] ?? 1 ) );
		$offset   = ( $page - 1 ) * $per_page;

		$search_criteria = [
			'status'        => 'active',
			'field_filters' => $this->build_field_filters( $criteria ),
		];

		if ( ! empty( $criteria['date_from'] ) ) {
			$search_criteria['start_date'] = $criteria['date_from'];
		}
		if ( ! empty( $criteria['date_to'] ) ) {
			$search_criteria['end_date'] = $criteria['date_to'];
		}

		$sorting = $this->build_sorting( (string) ( $criteria['sort'] ?? '' ) );
		$paging  = [ 'offset' => $offset, 'page_size' => $per_page ];
		$total   = 0;

		$entries = \GFAPI::get_entries(
			$this->settings->form_id(),
			$search_criteria,
			$sorting,
			$paging,
			$total
		);

		if ( is_wp_error( $entries ) ) {
			$entries = [];
			$total   = 0;
		}

		return [
			'entries'  => $entries,
			'total'    => (int) $total,
			'page'     => $page,
			'per_page' => $per_page,
		];
	}

	private function build_field_filters( array $criteria ): array {
		$filters = [
			'mode' => 'all',
			[
				'key'      => self::META_PUBLIC_KEY,
				'operator' => '=',
				'value'    => '1',
			],
		];

		$query  = (string) ( $criteria['q'] ?? '' );
		$fields = $this->settings->field_ids( 'searchable_fields' );

		if ( $query !== '' && ! empty( $fields ) ) {
			$or_group = [ 'mode' => 'any' ];
			foreach ( $fields as $field_id ) {
				$or_group[] = [
					'key'      => (string) $field_id,
					'operator' => 'contains',
					'value'    => $query,
				];
			}
			$filters[] = $or_group;
		}

		if ( ! empty( $criteria['filters'] ) && is_array( $criteria['filters'] ) ) {
			foreach ( $criteria['filters'] as $field_id => $value ) {
				if ( $value === '' || $value === null ) {
					continue;
				}
				$filters[] = [
					'key'      => (string) $field_id,
					'operator' => '=',
					'value'    => is_scalar( $value ) ? (string) $value : '',
				];
			}
		}

		return $filters;
	}

	private function build_sorting( string $sort ): array {
		// sort encoded as `<field_id>:<asc|desc>` or `newest` / `oldest`.
		if ( $sort === 'newest' || $sort === '' ) {
			return [ 'key' => 'date_created', 'direction' => 'DESC', 'is_numeric' => false ];
		}
		if ( $sort === 'oldest' ) {
			return [ 'key' => 'date_created', 'direction' => 'ASC', 'is_numeric' => false ];
		}

		$parts     = explode( ':', $sort, 2 );
		$key       = $parts[0] ?? '';
		$direction = strtoupper( $parts[1] ?? 'ASC' );
		$direction = in_array( $direction, [ 'ASC', 'DESC' ], true ) ? $direction : 'ASC';

		return [
			'key'        => $key,
			'direction'  => $direction,
			'is_numeric' => is_numeric( $key ),
		];
	}
}
