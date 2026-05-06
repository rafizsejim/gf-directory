<?php
/**
 * Single source of truth for URL → search criteria.
 *
 * Every URL parameter is type-coerced and bounds-checked here so callers
 * downstream never have to defend against $_GET tampering.
 *
 * @package GFDirectory\Query
 */

declare( strict_types=1 );

namespace GFDirectory\Query;

use GFDirectory\Settings\FormSettings;
use GFDirectory\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

final class SearchParser {

	public const PARAM_QUERY      = 'gfd_q';
	public const PARAM_FILTERS    = 'gfd_f';
	public const PARAM_VIEW       = 'gfd_view';
	public const PARAM_SORT       = 'gfd_sort';
	public const PARAM_PAGE       = 'gfd_page';
	public const PARAM_DATE_FROM  = 'gfd_dfrom';
	public const PARAM_DATE_TO    = 'gfd_dto';

	/**
	 * @return array{q:string,filters:array<string,string>,view:string,sort:string,page:int,date_from:string,date_to:string}
	 */
	public static function from_request( FormSettings $settings ): array {
		$enabled_views = $settings->enabled_views();
		$default_view  = $settings->default_view();

		$view_raw = isset( $_GET[ self::PARAM_VIEW ] ) ? sanitize_key( wp_unslash( (string) $_GET[ self::PARAM_VIEW ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$view     = in_array( $view_raw, $enabled_views, true ) ? $view_raw : $default_view;

		$page = Sanitizer::int( $_GET[ self::PARAM_PAGE ] ?? 1, 1, 1, 9999 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q    = Sanitizer::text( $_GET[ self::PARAM_QUERY ] ?? '', 200 );     // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$filters_raw = isset( $_GET[ self::PARAM_FILTERS ] ) && is_array( $_GET[ self::PARAM_FILTERS ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? wp_unslash( $_GET[ self::PARAM_FILTERS ] )                                                  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: [];

		$allowed_filter_fields = self::allowed_filter_fields( $settings );
		$filters               = [];
		foreach ( $filters_raw as $field_id => $value ) {
			$field_id = (string) $field_id;
			if ( ! in_array( $field_id, $allowed_filter_fields, true ) ) {
				continue;
			}
			$clean = Sanitizer::text( $value, 200 );
			if ( $clean !== '' ) {
				$filters[ $field_id ] = $clean;
			}
		}

		$sort = self::parse_sort( $settings, (string) ( $_GET[ self::PARAM_SORT ] ?? 'newest' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$date_from = self::parse_date( $_GET[ self::PARAM_DATE_FROM ] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_to   = self::parse_date( $_GET[ self::PARAM_DATE_TO ] ?? '' );   // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return [
			'q'         => $q,
			'filters'   => $filters,
			'view'      => $view,
			'sort'      => $sort,
			'page'      => $page,
			'date_from' => $date_from,
			'date_to'   => $date_to,
		];
	}

	/**
	 * Build a URL with one or more criteria overrides.
	 */
	public static function build_url( string $base, array $criteria, array $overrides = [] ): string {
		$args = [];

		if ( $criteria['q'] !== '' ) {
			$args[ self::PARAM_QUERY ] = $criteria['q'];
		}
		if ( ! empty( $criteria['filters'] ) ) {
			$args[ self::PARAM_FILTERS ] = $criteria['filters'];
		}
		if ( $criteria['view'] !== '' ) {
			$args[ self::PARAM_VIEW ] = $criteria['view'];
		}
		if ( $criteria['sort'] !== '' && $criteria['sort'] !== 'newest' ) {
			$args[ self::PARAM_SORT ] = $criteria['sort'];
		}
		if ( ! empty( $criteria['page'] ) && $criteria['page'] > 1 ) {
			$args[ self::PARAM_PAGE ] = $criteria['page'];
		}
		if ( ! empty( $criteria['date_from'] ) ) {
			$args[ self::PARAM_DATE_FROM ] = $criteria['date_from'];
		}
		if ( ! empty( $criteria['date_to'] ) ) {
			$args[ self::PARAM_DATE_TO ] = $criteria['date_to'];
		}

		foreach ( $overrides as $key => $value ) {
			$map = [
				'q'         => self::PARAM_QUERY,
				'filters'   => self::PARAM_FILTERS,
				'view'      => self::PARAM_VIEW,
				'sort'      => self::PARAM_SORT,
				'page'      => self::PARAM_PAGE,
				'date_from' => self::PARAM_DATE_FROM,
				'date_to'   => self::PARAM_DATE_TO,
			];
			$param = $map[ $key ] ?? null;
			if ( $param === null ) {
				continue;
			}
			if ( $value === null || $value === '' || $value === [] ) {
				unset( $args[ $param ] );
				continue;
			}
			$args[ $param ] = $value;
		}

		return add_query_arg( $args, $base );
	}

	private static function allowed_filter_fields( FormSettings $settings ): array {
		$ids = [];
		foreach ( [ 'filter_field_1', 'filter_field_2', 'filter_field_3', 'filter_field_4' ] as $slot ) {
			$id = $settings->field_id( $slot );
			if ( $id !== '' ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	private static function parse_sort( FormSettings $settings, string $raw ): string {
		$raw = sanitize_text_field( $raw );
		if ( in_array( $raw, [ 'newest', 'oldest' ], true ) ) {
			return $raw;
		}
		$parts = explode( ':', $raw, 2 );
		if ( count( $parts ) !== 2 ) {
			return 'newest';
		}
		[ $field_id, $direction ] = $parts;
		$direction                 = strtolower( $direction ) === 'desc' ? 'desc' : 'asc';
		$allowed                   = $settings->field_ids( 'sort_options' );
		if ( ! in_array( $field_id, $allowed, true ) ) {
			return 'newest';
		}
		return $field_id . ':' . $direction;
	}

	private static function parse_date( $raw ): string {
		$raw = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		if ( $raw === '' ) {
			return '';
		}
		// Accept Y-m-d only. Anything else gets dropped to keep GFAPI happy.
		$d = \DateTime::createFromFormat( 'Y-m-d', $raw );
		if ( $d === false ) {
			return '';
		}
		return $d->format( 'Y-m-d' );
	}
}
