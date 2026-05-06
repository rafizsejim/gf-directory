<?php
/**
 * Read-only accessor for a form's directory settings.
 *
 * Centralises defaulting so callers never have to memorise key names or
 * scatter `?? 'default'` checks across the codebase.
 *
 * @package GFDirectory\Settings
 */

declare( strict_types=1 );

namespace GFDirectory\Settings;

defined( 'ABSPATH' ) || exit;

final class FormSettings {

	private array $settings;
	private int   $form_id;
	private array $form;

	public function __construct( array $form, array $settings ) {
		$this->form     = $form;
		$this->form_id  = (int) ( $form['id'] ?? 0 );
		$this->settings = $settings;
	}

	public static function for_form( int $form_id ): ?FormSettings {
		$form = \GFAPI::get_form( $form_id );
		if ( ! $form || empty( $form['id'] ) ) {
			return null;
		}

		$addon    = \GFDirectory\Addon\DirectoryAddOn::get_instance();
		$settings = (array) $addon->get_form_settings( $form );

		return new self( $form, $settings );
	}

	public function form_id(): int {
		return $this->form_id;
	}

	public function form(): array {
		return $this->form;
	}

	public function is_enabled(): bool {
		return ! empty( $this->settings['enabled'] );
	}

	public function get( string $key, $default = null ) {
		return $this->settings[ $key ] ?? $default;
	}

	public function enabled_views(): array {
		$views = [];
		if ( ! empty( $this->settings['enabled_views_card'] ) ) {
			$views[] = 'card';
		}
		if ( ! empty( $this->settings['enabled_views_list'] ) ) {
			$views[] = 'list';
		}
		return $views ?: [ 'card' ];
	}

	public function default_view(): string {
		$default = (string) ( $this->settings['default_view'] ?? 'card' );
		return in_array( $default, $this->enabled_views(), true ) ? $default : ( $this->enabled_views()[0] );
	}

	public function card_style(): string {
		$style = (string) ( $this->settings['card_style'] ?? 'framed' );
		return in_array( $style, [ 'framed', 'overlay' ], true ) ? $style : 'framed';
	}

	public function cards_per_row(): int {
		$n = (int) ( $this->settings['cards_per_row'] ?? 3 );
		return in_array( $n, [ 2, 3, 4 ], true ) ? $n : 3;
	}

	public function per_page(): int {
		$n = (int) ( $this->settings['per_page'] ?? 12 );
		return max( 1, min( 60, $n ) );
	}

	public function field_id( string $slot ): string {
		return (string) ( $this->settings[ $slot ] ?? '' );
	}

	/**
	 * Multi-select settings (searchable_fields, sort_options) come back as
	 * comma-separated strings or arrays depending on Gravity Forms version.
	 *
	 * @return string[]
	 */
	public function field_ids( string $slot ): array {
		$value = $this->settings[ $slot ] ?? '';
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'strval', $value ) ) );
		}
		if ( is_string( $value ) && $value !== '' ) {
			return array_values( array_filter( array_map( 'trim', explode( ',', $value ) ) ) );
		}
		return [];
	}

	public function colors(): array {
		return [
			'accent'    => (string) ( $this->settings['color_accent']    ?? '#6D5CE7' ),
			'badge'     => (string) ( $this->settings['color_badge']     ?? '#1A8C4A' ),
			'hero_bg'   => (string) ( $this->settings['color_hero_bg']   ?? '#FFFFFF' ),
			'hero_text' => (string) ( $this->settings['color_hero_text'] ?? '#0F1115' ),
		];
	}

	public function strings(): array {
		return [
			'hero_title'         => (string) ( $this->settings['hero_title']         ?? __( 'Find your next listing', 'gf-directory' ) ),
			'hero_subtitle'      => (string) ( $this->settings['hero_subtitle']      ?? __( 'Curated by us', 'gf-directory' ) ),
			'search_placeholder' => (string) ( $this->settings['search_placeholder'] ?? __( 'Search…', 'gf-directory' ) ),
			'no_results_text'    => (string) ( $this->settings['no_results_text']    ?? __( 'No listings match your filters yet.', 'gf-directory' ) ),
			'cta_label'          => (string) ( $this->settings['cta_label']          ?? __( 'View details', 'gf-directory' ) ),
		];
	}

	public function show_hero(): bool {
		return ! isset( $this->settings['show_hero'] ) ? true : (bool) $this->settings['show_hero'];
	}
}
