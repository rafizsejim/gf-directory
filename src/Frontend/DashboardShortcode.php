<?php
/**
 * [gf_directory_dashboard] — logged-in user dashboard with two tabs:
 *   1. Saved listings
 *   2. My submissions (entries created_by current user)
 *
 * Reuses CardRenderer so cards in the dashboard match the archive style
 * pixel for pixel. No second design system to maintain.
 *
 * @package GFDirectory\Frontend
 */

declare( strict_types=1 );

namespace GFDirectory\Frontend;

use GFDirectory\Query\EntryQuery;
use GFDirectory\Render\CardRenderer;
use GFDirectory\Render\TemplateLoader;
use GFDirectory\Saves\SavesRepository;
use GFDirectory\Settings\FormSettings;
use GFDirectory\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

final class DashboardShortcode {

	public const TAG = 'gf_directory_dashboard';

	private TemplateLoader   $loader;
	private CardRenderer     $card;
	private SavesRepository  $repo;
	private Assets           $assets;

	public function __construct( TemplateLoader $loader, CardRenderer $card, SavesRepository $repo, Assets $assets ) {
		$this->loader = $loader;
		$this->card   = $card;
		$this->repo   = $repo;
		$this->assets = $assets;
	}

	public function register(): void {
		add_shortcode( self::TAG, [ $this, 'render' ] );
	}

	public function render( $atts ): string {
		$this->assets->enqueue_for_directory();

		if ( ! is_user_logged_in() ) {
			return $this->loader->render(
				'login-prompt',
				[ 'login_url' => wp_login_url( $this->current_url() ) ]
			);
		}

		$user_id    = get_current_user_id();
		$tab        = $this->detect_tab();
		$base_url   = $this->base_url();
		$tabs_html  = $this->tabs_html( $tab, $base_url );
		$panel_html = $tab === 'submissions'
			? $this->render_submissions( $user_id, $base_url )
			: $this->render_saved( $user_id, $base_url );

		return $this->loader->render(
			'dashboard',
			[
				'tab'        => $tab,
				'tabs_html'  => $tabs_html,
				'panel_html' => $panel_html,
			]
		);
	}

	private function detect_tab(): string {
		$tab = isset( $_GET['gfd_tab'] ) ? sanitize_key( wp_unslash( (string) $_GET['gfd_tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $tab, [ 'saved', 'submissions' ], true ) ? $tab : 'saved';
	}

	private function tabs_html( string $current, string $base ): string {
		$tabs = [
			'saved'       => esc_html__( 'Saved', 'gf-directory' ),
			'submissions' => esc_html__( 'My submissions', 'gf-directory' ),
		];

		$out = '<nav class="gfd-dash__tabs" role="tablist">';
		foreach ( $tabs as $key => $label ) {
			$url    = add_query_arg( 'gfd_tab', $key, $base );
			$active = ( $current === $key );
			$out   .= sprintf(
				'<a href="%s" class="gfd-dash__tab%s" role="tab" aria-selected="%s">%s</a>',
				esc_url( $url ),
				$active ? ' is-active' : '',
				$active ? 'true' : 'false',
				esc_html( $label )
			);
		}
		return $out . '</nav>';
	}

	/**
	 * Saved tab: enrich each saved entry id with form settings + entry data,
	 * skipping anything where the entry has been removed or unapproved since.
	 */
	private function render_saved( int $user_id, string $base_url ): string {
		$grouped = $this->repo->saved_grouped( $user_id );
		if ( empty( $grouped ) ) {
			return $this->empty_panel(
				__( 'You have not saved any listings yet.', 'gf-directory' ),
				__( 'Browse the directory and tap the heart on any listing to save it for later.', 'gf-directory' )
			);
		}

		$cards = '';
		foreach ( $grouped as $form_id => $entry_ids ) {
			$settings = FormSettings::for_form( (int) $form_id );
			if ( ! $settings || ! $settings->is_enabled() ) {
				continue;
			}
			$saved_index = array_flip( array_map( 'intval', $entry_ids ) );
			foreach ( $entry_ids as $entry_id ) {
				$entry = \GFAPI::get_entry( (int) $entry_id );
				if ( is_wp_error( $entry ) || ! is_array( $entry ) ) {
					continue;
				}
				$is_public = (string) gform_get_meta( (int) $entry_id, EntryQuery::META_PUBLIC_KEY ) === '1';
				if ( ! $is_public ) {
					continue;
				}
				$cards .= $this->card->render( $settings, $entry, $base_url, $saved_index );
			}
		}

		if ( $cards === '' ) {
			return $this->empty_panel(
				__( 'Your saved listings are no longer available.', 'gf-directory' ),
				__( 'They may have been removed or unpublished.', 'gf-directory' )
			);
		}

		return '<div class="gfd__grid gfd-dash__grid">' . $cards . '</div>';
	}

	/**
	 * My submissions tab: entries where created_by = current user, on any
	 * directory-enabled form. Shows a status pill per row.
	 */
	private function render_submissions( int $user_id, string $base_url ): string {
		$forms     = $this->directory_forms();
		if ( empty( $forms ) ) {
			return $this->empty_panel( __( 'No directories configured.', 'gf-directory' ), '' );
		}

		$rows = '';
		foreach ( $forms as $settings ) {
			$entries = \GFAPI::get_entries(
				$settings->form_id(),
				[
					'status'        => 'active',
					'field_filters' => [
						[ 'key' => 'created_by', 'operator' => '=', 'value' => (string) $user_id ],
					],
				],
				[ 'key' => 'date_created', 'direction' => 'DESC' ],
				[ 'offset' => 0, 'page_size' => 50 ]
			);
			if ( is_wp_error( $entries ) ) {
				continue;
			}
			foreach ( (array) $entries as $entry ) {
				$rows .= $this->submission_row( $settings, $entry, $base_url );
			}
		}

		if ( $rows === '' ) {
			return $this->empty_panel(
				__( 'You have not submitted any listings yet.', 'gf-directory' ),
				__( 'Submissions you make to directory-enabled forms will appear here.', 'gf-directory' )
			);
		}

		return '<div class="gfd-dash__submissions">' . $rows . '</div>';
	}

	/**
	 * @return FormSettings[]
	 */
	private function directory_forms(): array {
		$forms = \GFAPI::get_forms( true );
		$out   = [];
		foreach ( (array) $forms as $form ) {
			$settings = FormSettings::for_form( (int) ( $form['id'] ?? 0 ) );
			if ( $settings && $settings->is_enabled() ) {
				$out[] = $settings;
			}
		}
		return $out;
	}

	private function submission_row( FormSettings $settings, array $entry, string $base_url ): string {
		$entry_id  = (int) ( $entry['id'] ?? 0 );
		$is_public = (string) gform_get_meta( $entry_id, EntryQuery::META_PUBLIC_KEY ) === '1';
		$status    = $is_public ? __( 'Published', 'gf-directory' ) : __( 'Pending review', 'gf-directory' );
		$status_cls = $is_public ? 'is-published' : 'is-pending';

		$title_field = $settings->field_id( 'field_title' );
		$title       = $title_field !== '' ? (string) ( $entry[ $title_field ] ?? '' ) : '';
		$title       = $title !== '' ? $title : '#' . $entry_id;

		$date    = mysql2date( get_option( 'date_format' ) ?: 'Y-m-d', (string) ( $entry['date_created'] ?? '' ) );
		$view_url = $is_public ? Rewrite::entry_url( $base_url, $entry_id ) : '';

		$out  = '<article class="gfd-dash__submission">';
		$out .= '<div class="gfd-dash__sub-main">';
		$out .= '<h3 class="gfd-dash__sub-title">' . esc_html( $title ) . '</h3>';
		$out .= '<p class="gfd-dash__sub-meta">' . esc_html( (string) ( $settings->form()['title'] ?? '' ) ) . ' · ' . esc_html( $date ) . '</p>';
		$out .= '</div>';
		$out .= '<div class="gfd-dash__sub-aside">';
		$out .= '<span class="gfd-dash__status ' . esc_attr( $status_cls ) . '">' . esc_html( $status ) . '</span>';
		if ( $view_url !== '' ) {
			$out .= '<a class="gfd-dash__view" href="' . esc_url( $view_url ) . '">' . esc_html__( 'View', 'gf-directory' ) . '</a>';
		}
		$out .= '</div>';
		$out .= '</article>';

		return $out;
	}

	private function empty_panel( string $title, string $body ): string {
		return sprintf(
			'<div class="gfd-empty"><div class="gfd-empty__art" aria-hidden="true">∅</div><p class="gfd-empty__text"><strong>%s</strong></p><p class="gfd-empty__sub">%s</p></div>',
			esc_html( $title ),
			esc_html( $body )
		);
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

	private function current_url(): string {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
		return home_url( $path );
	}
}
