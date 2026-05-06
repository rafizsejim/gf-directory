<?php
/**
 * Field types that must never be rendered to the public directory.
 *
 * @package GFDirectory\Support
 */

declare( strict_types=1 );

namespace GFDirectory\Support;

defined( 'ABSPATH' ) || exit;

final class FieldBlocklist {

	/**
	 * Field types whose values can never appear publicly, regardless of mapping.
	 *
	 * Mapping a blocked field to a card slot is a no-op. The list stays narrow
	 * on purpose: it covers payment, secrets, and consent text. Admin-only and
	 * conditional-hidden fields are handled separately.
	 */
	private const BLOCKED_TYPES = [
		'creditcard',
		'password',
		'consent',
	];

	public static function is_blocked( string $field_type ): bool {
		return in_array( $field_type, self::BLOCKED_TYPES, true );
	}

	/**
	 * True when a field is marked admin-only in the form editor or hidden by
	 * conditional logic for a given entry. Admin-only is checked first because
	 * conditional logic evaluation is more expensive.
	 *
	 * @param array $field GF field array.
	 */
	public static function is_admin_only( array $field ): bool {
		return ! empty( $field['adminOnly'] );
	}
}
