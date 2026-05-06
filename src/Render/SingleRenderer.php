<?php
/**
 * Single-entry detail page.
 *
 * Renders the chosen entry inside a host page. Validates that the entry is
 * approved for the directory and belongs to this form before exposing
 * anything to the public.
 *
 * @package GFDirectory\Render
 */

declare( strict_types=1 );

namespace GFDirectory\Render;

use GFDirectory\Query\EntryQuery;
use GFDirectory\Saves\SavesRepository;
use GFDirectory\Settings\FormSettings;

defined( 'ABSPATH' ) || exit;

final class SingleRenderer {

	private TemplateLoader  $loader;
	private CardRenderer    $card;
	private SavesRepository $saves;

	public function __construct( TemplateLoader $loader, CardRenderer $card, SavesRepository $saves ) {
		$this->loader = $loader;
		$this->card   = $card;
		$this->saves  = $saves;
	}

	public function render( FormSettings $settings, int $entry_id, string $base_url ): string {
		$entry = \GFAPI::get_entry( $entry_id );

		if ( is_wp_error( $entry ) || ! is_array( $entry ) ) {
			return $this->not_found_html( $settings, $base_url );
		}

		if ( (int) ( $entry['form_id'] ?? 0 ) !== $settings->form_id() ) {
			return $this->not_found_html( $settings, $base_url );
		}

		$is_public = (string) gform_get_meta( $entry_id, EntryQuery::META_PUBLIC_KEY ) === '1';
		if ( ! $is_public ) {
			return $this->not_found_html( $settings, $base_url );
		}

		$saved_index = is_user_logged_in()
			? array_flip( array_map( 'intval', $this->saves->saved_entry_ids( get_current_user_id(), $settings->form_id() ) ) )
			: [];
		$data = CardData::from( $settings, $entry, $base_url, $saved_index );

		// Catch-all "details" list: every mapped field that hasn't already
		// been used in a dedicated section. Powered by the field map so admins
		// implicitly choose what shows here just by mapping.
		$used_field_ids = $this->used_field_ids( $settings );
		$details        = $this->build_details( $settings, $entry, $used_field_ids );

		// Related listings: 3 most recent approved entries, excluding this one.
		$related = $this->fetch_related( $settings, $entry_id );

		$related_html = '';
		foreach ( $related as $related_entry ) {
			$related_html .= $this->card->render( $settings, $related_entry, $base_url, $saved_index );
		}

		$instance_id = 'gfd-single-' . $settings->form_id() . '-' . $entry_id;

		return $this->loader->render(
			'single',
			[
				'settings'     => $settings,
				'entry'        => $entry,
				'data'         => $data,
				'details'      => $details,
				'related_html' => $related_html,
				'base_url'     => $base_url,
				'instance_id'  => $instance_id,
			]
		);
	}

	private function not_found_html( FormSettings $settings, string $base_url ): string {
		return $this->loader->render(
			'single-not-found',
			[ 'settings' => $settings, 'base_url' => $base_url ]
		);
	}

	private function used_field_ids( FormSettings $settings ): array {
		$ids = [];
		$slots = [
			'field_title', 'field_subtitle', 'field_image', 'field_badge', 'field_price',
			'field_rating', 'field_rating_count', 'field_description', 'field_features',
			'field_cta_url',
		];
		foreach ( $slots as $slot ) {
			$id = $settings->field_id( $slot );
			if ( $id !== '' ) {
				$ids[] = (string) (int) $id;
			}
		}
		for ( $i = 1; $i <= 4; $i++ ) {
			$id = $settings->field_id( "meta_icon_{$i}_field" );
			if ( $id !== '' ) {
				$ids[] = (string) (int) $id;
			}
		}
		for ( $i = 1; $i <= 3; $i++ ) {
			$id = $settings->field_id( "stat_{$i}_field" );
			if ( $id !== '' ) {
				$ids[] = (string) (int) $id;
			}
		}
		return $ids;
	}

	/**
	 * @return array<int,array{label:string,value:string}>
	 */
	private function build_details( FormSettings $settings, array $entry, array $used_field_ids ): array {
		$out  = [];
		$form = $settings->form();
		foreach ( (array) ( $form['fields'] ?? [] ) as $field ) {
			$id = isset( $field['id'] ) ? (string) $field['id'] : '';
			if ( $id === '' || in_array( $id, $used_field_ids, true ) ) {
				continue;
			}
			$type = (string) ( $field['type'] ?? '' );
			if ( in_array( $type, [ 'creditcard', 'password', 'consent', 'fileupload', 'post_image', 'html', 'section', 'page', 'captcha' ], true ) ) {
				continue;
			}
			if ( ! empty( $field['adminOnly'] ) ) {
				continue;
			}
			$label = (string) ( $field['label'] ?? '' );
			$value = $entry[ $id ] ?? '';
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_filter( array_map( 'strval', $value ) ) );
			}
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';
			if ( $value === '' || $label === '' ) {
				continue;
			}
			$out[] = [ 'label' => $label, 'value' => $value ];
		}
		return $out;
	}

	private function fetch_related( FormSettings $settings, int $exclude_entry_id ): array {
		$entries = \GFAPI::get_entries(
			$settings->form_id(),
			[
				'status'        => 'active',
				'field_filters' => [
					[ 'key' => EntryQuery::META_PUBLIC_KEY, 'operator' => '=', 'value' => '1' ],
				],
			],
			[ 'key' => 'date_created', 'direction' => 'DESC' ],
			[ 'offset' => 0, 'page_size' => 4 ]
		);

		if ( is_wp_error( $entries ) ) {
			return [];
		}

		$out = [];
		foreach ( (array) $entries as $entry ) {
			if ( (int) ( $entry['id'] ?? 0 ) === $exclude_entry_id ) {
				continue;
			}
			$out[] = $entry;
			if ( count( $out ) >= 3 ) {
				break;
			}
		}
		return $out;
	}
}
