<?php
/**
 * Resolves form-field-keyed entry data into the slot-keyed structure used
 * by every template. Built once per entry, never inside a template.
 *
 * @package GFDirectory\Render
 */

declare( strict_types=1 );

namespace GFDirectory\Render;

use GFDirectory\Settings\FormSettings;
use GFDirectory\Support\FieldBlocklist;

defined( 'ABSPATH' ) || exit;

final class CardData {

	public string $title       = '';
	public string $subtitle    = '';
	public string $image_url   = '';
	public array  $images      = [];
	public string $badge       = '';
	public string $price       = '';
	public string $rating      = '';
	public string $rating_count = '';
	public string $description = '';
	public array  $features    = [];
	public string $cta_url     = '';
	public string $cta_label   = '';
	public array  $meta_icons  = [];
	public array  $stats       = [];
	public int    $entry_id    = 0;
	public string $permalink   = '';
	public bool   $is_saved    = false;

	public static function from( FormSettings $settings, array $entry, string $base_url = '', array $saved_index = [] ): CardData {
		$d            = new self();
		$d->entry_id  = (int) ( $entry['id'] ?? 0 );
		$strings      = $settings->strings();
		$d->cta_label = $strings['cta_label'];

		$form         = $settings->form();
		$fields_index = self::index_fields( $form );

		$d->title       = self::value( $entry, $fields_index, $settings->field_id( 'field_title' ) );
		$d->subtitle    = self::value( $entry, $fields_index, $settings->field_id( 'field_subtitle' ) );
		$d->badge       = self::value( $entry, $fields_index, $settings->field_id( 'field_badge' ) );
		$d->price       = self::value( $entry, $fields_index, $settings->field_id( 'field_price' ) );
		$d->rating      = self::value( $entry, $fields_index, $settings->field_id( 'field_rating' ) );
		$d->rating_count = self::value( $entry, $fields_index, $settings->field_id( 'field_rating_count' ) );
		$d->description = self::value( $entry, $fields_index, $settings->field_id( 'field_description' ) );
		$d->cta_url     = self::value( $entry, $fields_index, $settings->field_id( 'field_cta_url' ) );

		$d->images    = self::images( $entry, $fields_index, $settings->field_id( 'field_image' ) );
		$d->image_url = $d->images[0] ?? '';
		$d->features  = self::list_values( $entry, $fields_index, $settings->field_id( 'field_features' ) );

		for ( $i = 1; $i <= 4; $i++ ) {
			$field_id = $settings->field_id( "meta_icon_{$i}_field" );
			$icon     = (string) $settings->get( "meta_icon_{$i}_icon", 'none' );
			$value    = self::value( $entry, $fields_index, $field_id );
			if ( $value === '' || $icon === 'none' ) {
				continue;
			}
			$d->meta_icons[] = [ 'icon' => $icon, 'value' => $value ];
		}

		for ( $i = 1; $i <= 3; $i++ ) {
			$field_id = $settings->field_id( "stat_{$i}_field" );
			$label    = (string) $settings->get( "stat_{$i}_label", '' );
			$value    = self::value( $entry, $fields_index, $field_id );
			if ( $value === '' || $label === '' ) {
				continue;
			}
			$d->stats[] = [ 'label' => $label, 'value' => $value ];
		}

		$d->permalink = \GFDirectory\Frontend\Rewrite::entry_url( $base_url, $d->entry_id );
		$d->is_saved  = isset( $saved_index[ $d->entry_id ] );

		return $d;
	}

	private static function index_fields( array $form ): array {
		$out = [];
		foreach ( (array) ( $form['fields'] ?? [] ) as $field ) {
			$id = isset( $field['id'] ) ? (string) $field['id'] : '';
			if ( $id === '' ) {
				continue;
			}
			$out[ $id ] = $field;
		}
		return $out;
	}

	private static function value( array $entry, array $fields_index, string $field_id ): string {
		if ( $field_id === '' ) {
			return '';
		}

		$base_id = (string) (int) $field_id;
		$field   = $fields_index[ $base_id ] ?? null;

		if ( is_array( $field ) ) {
			$type = (string) ( $field['type'] ?? '' );
			if ( FieldBlocklist::is_blocked( $type ) || FieldBlocklist::is_admin_only( $field ) ) {
				return '';
			}
		}

		$value = $entry[ $field_id ] ?? '';
		if ( is_array( $value ) ) {
			$value = implode( ', ', array_filter( array_map( 'strval', $value ) ) );
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	private static function images( array $entry, array $fields_index, string $field_id ): array {
		$raw = self::value( $entry, $fields_index, $field_id );
		if ( $raw === '' ) {
			return [];
		}
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			$urls = array_filter( array_map( 'strval', $decoded ) );
		} else {
			$urls = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		}
		return array_values( array_filter( $urls, [ self::class, 'looks_like_url' ] ) );
	}

	/**
	 * @return string[]
	 */
	private static function list_values( array $entry, array $fields_index, string $field_id ): array {
		if ( $field_id === '' ) {
			return [];
		}

		$base_id = (string) (int) $field_id;
		$field   = $fields_index[ $base_id ] ?? null;

		// Multi-choice fields (checkbox / multiselect) are stored as multiple
		// inputs keyed `<id>.1`, `<id>.2`, ... so we must collect them.
		$values = [];
		foreach ( $entry as $key => $val ) {
			if ( strpos( (string) $key, $base_id . '.' ) !== 0 && (string) $key !== $base_id ) {
				continue;
			}
			if ( is_scalar( $val ) && (string) $val !== '' ) {
				$values[] = (string) $val;
			} elseif ( is_array( $val ) ) {
				foreach ( $val as $v ) {
					if ( is_scalar( $v ) && (string) $v !== '' ) {
						$values[] = (string) $v;
					}
				}
			}
		}
		return $values;
	}

	private static function looks_like_url( string $candidate ): bool {
		return (bool) preg_match( '#^https?://#i', $candidate );
	}
}
