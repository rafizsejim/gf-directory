<?php
/**
 * "Directory" column on the entry list, showing publication status at a glance.
 *
 * @package GFDirectory\Admin
 */

declare( strict_types=1 );

namespace GFDirectory\Admin;

use GFDirectory\Query\EntryQuery;
use GFDirectory\Settings\FormSettings;

defined( 'ABSPATH' ) || exit;

final class EntryColumn {

	public function register(): void {
		add_filter( 'gform_entry_list_columns', [ $this, 'add_column' ], 10, 2 );
		add_filter( 'gform_entries_field_value', [ $this, 'render_value' ], 10, 4 );
	}

	/**
	 * @param array $columns
	 * @param int   $form_id
	 */
	public function add_column( $columns, $form_id ): array {
		$settings = FormSettings::for_form( (int) $form_id );
		if ( ! $settings || ! $settings->is_enabled() ) {
			return $columns;
		}
		$columns['gfd_status'] = esc_html__( 'Directory', 'gf-directory' );
		return $columns;
	}

	/**
	 * @param mixed  $value
	 * @param int    $form_id
	 * @param string $field_id
	 * @param array  $entry
	 */
	public function render_value( $value, $form_id, $field_id, $entry ) {
		if ( $field_id !== 'gfd_status' ) {
			return $value;
		}

		$entry_id  = (int) ( $entry['id'] ?? 0 );
		$is_public = (string) gform_get_meta( $entry_id, EntryQuery::META_PUBLIC_KEY ) === '1';

		$label = $is_public
			? esc_html__( 'Published', 'gf-directory' )
			: esc_html__( 'Pending', 'gf-directory' );
		$color = $is_public ? '#1A8C4A' : '#777';

		return sprintf( '<span style="color:%s;font-weight:600">%s</span>', esc_attr( $color ), $label );
	}
}
