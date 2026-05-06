<?php
/**
 * Hero search bar with up to four filter slots, optional date range,
 * sort and view toggle.
 *
 * @package GFDirectory\Render
 */

declare( strict_types=1 );

namespace GFDirectory\Render;

use GFDirectory\Query\SearchParser;
use GFDirectory\Settings\FormSettings;

defined( 'ABSPATH' ) || exit;

final class SearchBarRenderer {

	private TemplateLoader $loader;

	public function __construct( TemplateLoader $loader ) {
		$this->loader = $loader;
	}

	public function render( FormSettings $settings, array $criteria, string $base_url ): string {
		return $this->loader->render(
			'search-bar',
			[
				'settings'    => $settings,
				'criteria'    => $criteria,
				'base_url'    => $base_url,
				'filter_meta' => $this->collect_filters( $settings ),
				'sort_meta'   => $this->collect_sorts( $settings ),
				'param'       => [
					'q'         => SearchParser::PARAM_QUERY,
					'filters'   => SearchParser::PARAM_FILTERS,
					'view'      => SearchParser::PARAM_VIEW,
					'sort'      => SearchParser::PARAM_SORT,
					'page'      => SearchParser::PARAM_PAGE,
					'date_from' => SearchParser::PARAM_DATE_FROM,
					'date_to'   => SearchParser::PARAM_DATE_TO,
				],
			]
		);
	}

	/**
	 * Build [{field_id, label, choices[]}, ...] for each configured filter slot.
	 * Choices come from the form definition, no DB query.
	 */
	private function collect_filters( FormSettings $settings ): array {
		$form    = $settings->form();
		$indexed = [];
		foreach ( (array) ( $form['fields'] ?? [] ) as $f ) {
			if ( ! empty( $f['id'] ) ) {
				$indexed[ (string) $f['id'] ] = $f;
			}
		}

		$out = [];
		foreach ( [ 'filter_field_1', 'filter_field_2', 'filter_field_3', 'filter_field_4' ] as $slot ) {
			$id   = $settings->field_id( $slot );
			$base = (string) (int) $id;
			if ( $id === '' || ! isset( $indexed[ $base ] ) ) {
				continue;
			}
			$field   = $indexed[ $base ];
			$choices = [];
			foreach ( (array) ( $field['choices'] ?? [] ) as $choice ) {
				$value = (string) ( $choice['value'] ?? '' );
				if ( $value === '' ) {
					continue;
				}
				$choices[] = [
					'value' => $value,
					'label' => (string) ( $choice['text'] ?? $choice['value'] ?? '' ),
				];
			}
			$out[] = [
				'field_id' => $id,
				'label'    => (string) ( $field['label'] ?? '' ),
				'choices'  => $choices,
			];
		}
		return $out;
	}

	private function collect_sorts( FormSettings $settings ): array {
		$out = [
			[ 'value' => 'newest', 'label' => __( 'Newest', 'gf-directory' ) ],
			[ 'value' => 'oldest', 'label' => __( 'Oldest', 'gf-directory' ) ],
		];

		$ids     = $settings->field_ids( 'sort_options' );
		$form    = $settings->form();
		$indexed = [];
		foreach ( (array) ( $form['fields'] ?? [] ) as $f ) {
			if ( ! empty( $f['id'] ) ) {
				$indexed[ (string) $f['id'] ] = $f;
			}
		}

		foreach ( $ids as $field_id ) {
			$base = (string) (int) $field_id;
			if ( ! isset( $indexed[ $base ] ) ) {
				continue;
			}
			$label = (string) ( $indexed[ $base ]['label'] ?? $field_id );
			$out[] = [ 'value' => $field_id . ':asc',  'label' => $label . ' ↑' ];
			$out[] = [ 'value' => $field_id . ':desc', 'label' => $label . ' ↓' ];
		}
		return $out;
	}
}
