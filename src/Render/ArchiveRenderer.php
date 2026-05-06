<?php
/**
 * Top-level archive: hero + view-toggle bar + grid/list + pagination.
 *
 * Inlines the directory color tokens into a scoped style tag so per-form
 * customizations apply without touching theme CSS.
 *
 * @package GFDirectory\Render
 */

declare( strict_types=1 );

namespace GFDirectory\Render;

use GFDirectory\Query\Cache;
use GFDirectory\Query\EntryQuery;
use GFDirectory\Query\SearchParser;
use GFDirectory\Saves\SavesRepository;
use GFDirectory\Settings\FormSettings;

defined( 'ABSPATH' ) || exit;

final class ArchiveRenderer {

	private TemplateLoader      $loader;
	private CardRenderer        $card;
	private ListRenderer        $list;
	private SearchBarRenderer   $search_bar;
	private PaginationRenderer  $pagination;
	private Cache               $cache;
	private SavesRepository     $saves;

	public function __construct(
		TemplateLoader $loader,
		CardRenderer $card,
		ListRenderer $list,
		SearchBarRenderer $search_bar,
		PaginationRenderer $pagination,
		Cache $cache,
		SavesRepository $saves
	) {
		$this->loader     = $loader;
		$this->card       = $card;
		$this->list       = $list;
		$this->search_bar = $search_bar;
		$this->pagination = $pagination;
		$this->cache      = $cache;
		$this->saves      = $saves;
	}

	public function render( FormSettings $settings ): string {
		$criteria = SearchParser::from_request( $settings );
		$base_url = $this->base_url();

		$cache_key = $this->cache->key( $settings->form_id(), [ 'criteria' => $criteria ] );
		$cached    = $this->cache->get( $cache_key );
		if ( is_array( $cached ) && isset( $cached['entries'], $cached['total'] ) ) {
			$result = $cached;
		} else {
			$query  = new EntryQuery( $settings );
			$result = $query->run( $criteria );
			$this->cache->set( $cache_key, $result );
		}

		// One DB hit total for the user's saves on this form, then O(1) lookup
		// per card via array_flip-style index.
		$saved_index = $this->build_saved_index( $settings->form_id() );

		$cards_html = '';
		if ( $criteria['view'] === 'list' ) {
			foreach ( $result['entries'] as $entry ) {
				$cards_html .= $this->list->render( $settings, $entry, $base_url, $saved_index );
			}
		} else {
			foreach ( $result['entries'] as $entry ) {
				$cards_html .= $this->card->render( $settings, $entry, $base_url, $saved_index );
			}
		}

		if ( $cards_html === '' ) {
			$cards_html = $this->loader->render(
				'empty',
				[ 'settings' => $settings, 'criteria' => $criteria ]
			);
		}

		$pagination_html = $this->pagination->render(
			(int) $result['total'],
			(int) $result['per_page'],
			(int) $result['page'],
			$criteria,
			$base_url
		);

		$search_html = $settings->show_hero()
			? $this->search_bar->render( $settings, $criteria, $base_url )
			: '';

		$instance_id = 'gfd-' . $settings->form_id();

		return $this->loader->render(
			'archive',
			[
				'settings'        => $settings,
				'criteria'        => $criteria,
				'result'          => $result,
				'cards_html'      => $cards_html,
				'pagination_html' => $pagination_html,
				'search_html'     => $search_html,
				'base_url'        => $base_url,
				'instance_id'     => $instance_id,
				'inline_style'    => $this->inline_tokens( $instance_id, $settings ),
			]
		);
	}

	private function build_saved_index( int $form_id ): array {
		if ( ! is_user_logged_in() ) {
			return [];
		}
		$ids = $this->saves->saved_entry_ids( get_current_user_id(), $form_id );
		return array_flip( array_map( 'intval', $ids ) );
	}

	private function base_url(): string {
		$queried = get_queried_object_id();
		if ( $queried > 0 ) {
			$permalink = get_permalink( $queried );
			if ( $permalink ) {
				return (string) $permalink;
			}
		}
		return home_url( '/' );
	}

	private function inline_tokens( string $instance_id, FormSettings $settings ): string {
		$colors = $settings->colors();
		$cards  = $settings->cards_per_row();
		$dark   = $this->is_dark( $colors['hero_bg'] );

		// Helper colors automatically pick light or dark variants so the inner
		// bar, labels and separators always have the right contrast regardless
		// of which hero background the admin chose.
		$bar_bg     = $dark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)';
		$bar_border = $dark ? 'rgba(255,255,255,0.10)' : 'rgba(0,0,0,0.08)';
		$separator  = $dark ? 'rgba(255,255,255,0.10)' : 'rgba(0,0,0,0.08)';
		$hint       = $dark ? 'rgba(255,255,255,0.55)' : 'rgba(0,0,0,0.55)';
		$placeholder = $dark ? 'rgba(255,255,255,0.45)' : 'rgba(0,0,0,0.40)';
		$arrow      = $dark ? 'rgba(255,255,255,0.6)' : 'rgba(0,0,0,0.6)';
		$sort_pill  = $dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.05)';

		$css = sprintf(
			'#%1$s{--gfd-accent:%2$s;--gfd-badge:%3$s;--gfd-hero-bg:%4$s;--gfd-hero-fg:%5$s;--gfd-hero-bar-bg:%7$s;--gfd-hero-bar-border:%8$s;--gfd-hero-sep:%9$s;--gfd-hero-hint:%10$s;--gfd-hero-placeholder:%11$s;--gfd-hero-arrow:%12$s;--gfd-hero-sort-bg:%13$s;--gfd-cols:%6$d;}',
			$instance_id,
			esc_attr( $colors['accent'] ),
			esc_attr( $colors['badge'] ),
			esc_attr( $colors['hero_bg'] ),
			esc_attr( $colors['hero_text'] ),
			$cards,
			$bar_bg,
			$bar_border,
			$separator,
			$hint,
			$placeholder,
			$arrow,
			$sort_pill
		);
		return '<style>' . $css . '</style>';
	}

	private function is_dark( string $hex ): bool {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
			return false;
		}
		$r         = hexdec( substr( $hex, 0, 2 ) );
		$g         = hexdec( substr( $hex, 2, 2 ) );
		$b         = hexdec( substr( $hex, 4, 2 ) );
		$luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;
		return $luminance < 140;
	}
}
