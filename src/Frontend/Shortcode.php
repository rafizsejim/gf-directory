<?php
/**
 * [gf_directory] shortcode.
 *
 * Switches between archive and single-entry rendering based on the
 * `gfd_entry` query var (populated by the rewrite endpoint).
 *
 * @package GFDirectory\Frontend
 */

declare( strict_types=1 );

namespace GFDirectory\Frontend;

use GFDirectory\Render\ArchiveRenderer;
use GFDirectory\Render\SingleRenderer;
use GFDirectory\Settings\FormSettings;
use GFDirectory\Support\Sanitizer;

defined( 'ABSPATH' ) || exit;

final class Shortcode {

	public const TAG = 'gf_directory';

	private ArchiveRenderer $archive;
	private SingleRenderer  $single;
	private Assets          $assets;

	public function __construct( ArchiveRenderer $archive, SingleRenderer $single, Assets $assets ) {
		$this->archive = $archive;
		$this->single  = $single;
		$this->assets  = $assets;
	}

	public function register(): void {
		add_shortcode( self::TAG, [ $this, 'render' ] );
	}

	public function render( $atts ): string {
		$atts = shortcode_atts(
			[ 'form' => 0 ],
			is_array( $atts ) ? $atts : [],
			self::TAG
		);

		$form_id = Sanitizer::int( $atts['form'], 0, 0 );
		if ( $form_id <= 0 ) {
			return $this->error_html( __( 'Missing form attribute. Use [gf_directory form="ID"].', 'gf-directory' ) );
		}

		$settings = FormSettings::for_form( $form_id );
		if ( ! $settings ) {
			return $this->error_html( __( 'Form not found.', 'gf-directory' ) );
		}

		if ( ! $settings->is_enabled() ) {
			return $this->error_html( __( 'Directory is not enabled for this form. Enable it under Form Settings → Directory.', 'gf-directory' ) );
		}

		$this->assets->enqueue_for_directory();

		$entry_id = $this->detect_single_entry();
		if ( $entry_id > 0 ) {
			return $this->single->render( $settings, $entry_id, $this->base_url() );
		}

		return $this->archive->render( $settings );
	}

	private function detect_single_entry(): int {
		// Pretty-permalink endpoint: get_query_var('gfd_entry').
		$id = (int) get_query_var( Rewrite::QUERY_VAR );

		// Fallback for sites without permalinks: ?gfd_entry=N.
		if ( $id <= 0 && isset( $_GET[ Rewrite::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$id = (int) $_GET[ Rewrite::QUERY_VAR ];               // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		return max( 0, $id );
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

	private function error_html( string $message ): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		return '<div class="gfd gfd--error" style="padding:12px;border:1px dashed #d63638;color:#d63638">' . esc_html( $message ) . '</div>';
	}
}
