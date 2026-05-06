<?php
/**
 * Lightweight sanitizer helpers shared across modules.
 *
 * @package GFDirectory\Support
 */

declare( strict_types=1 );

namespace GFDirectory\Support;

defined( 'ABSPATH' ) || exit;

final class Sanitizer {

	public static function int( $value, int $default = 0, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX ): int {
		$int = is_numeric( $value ) ? (int) $value : $default;
		return max( $min, min( $max, $int ) );
	}

	public static function text( $value, int $max_length = 200 ): string {
		$value = is_scalar( $value ) ? (string) $value : '';
		$value = sanitize_text_field( $value );
		return mb_substr( $value, 0, $max_length );
	}

	public static function slug( $value ): string {
		$value = is_scalar( $value ) ? (string) $value : '';
		return sanitize_key( $value );
	}

	/**
	 * Sanitize an array of mixed scalars into clean text.
	 *
	 * @param mixed $value
	 * @return string[]
	 */
	public static function text_array( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}
		$out = [];
		foreach ( $value as $item ) {
			if ( ! is_scalar( $item ) ) {
				continue;
			}
			$clean = sanitize_text_field( (string) $item );
			if ( $clean !== '' ) {
				$out[] = $clean;
			}
		}
		return $out;
	}
}
