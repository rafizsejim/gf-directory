<?php
/**
 * Approve / unapprove an entry for public display.
 *
 * Uses the admin-post.php action pattern. The meta box renders a link, not a
 * nested <form>, because Gravity Forms wraps the entire entry-detail page in
 * its own <form> and HTML does not allow nested forms.
 *
 * @package GFDirectory\Admin
 */

declare( strict_types=1 );

namespace GFDirectory\Admin;

use GFDirectory\Query\EntryQuery;
use GFDirectory\Settings\FormSettings;

defined( 'ABSPATH' ) || exit;

final class EntryActions {

	private const ACTION       = 'gfd_toggle_directory';
	private const NONCE_FIELD  = '_gfd_nonce';

	public function register(): void {
		add_action( 'gform_entry_detail_meta_boxes', [ $this, 'add_meta_box' ], 10, 3 );
		add_action( 'admin_post_' . self::ACTION, [ $this, 'handle_toggle' ] );
	}

	/**
	 * @param array $meta_boxes
	 * @param array $entry
	 * @param array $form
	 */
	public function add_meta_box( $meta_boxes, $entry, $form ): array {
		$settings = FormSettings::for_form( (int) ( $form['id'] ?? 0 ) );
		if ( ! $settings || ! $settings->is_enabled() ) {
			return $meta_boxes;
		}

		$meta_boxes['gfd_directory'] = [
			'title'    => esc_html__( 'Directory', 'gf-directory' ),
			'callback' => [ $this, 'render_meta_box' ],
			'context'  => 'side',
		];

		return $meta_boxes;
	}

	public function render_meta_box( array $args ): void {
		$entry     = $args['entry'] ?? [];
		$entry_id  = (int) ( $entry['id'] ?? 0 );
		$is_public = (string) gform_get_meta( $entry_id, EntryQuery::META_PUBLIC_KEY ) === '1';
		$next      = $is_public ? '0' : '1';

		$action_url = wp_nonce_url(
			add_query_arg(
				[
					'action'   => self::ACTION,
					'entry_id' => $entry_id,
					'next'     => $next,
					'redirect' => rawurlencode( $this->current_request_uri() ),
				],
				admin_url( 'admin-post.php' )
			),
			self::ACTION . '_' . $entry_id,
			self::NONCE_FIELD
		);

		$button_class = $is_public ? 'button button-secondary' : 'button button-primary';
		$button_label = $is_public
			? esc_html__( 'Remove from directory', 'gf-directory' )
			: esc_html__( 'Approve for directory', 'gf-directory' );

		$status_label = $is_public
			? esc_html__( 'Published in directory', 'gf-directory' )
			: esc_html__( 'Pending approval', 'gf-directory' );

		$status_color = $is_public ? '#1A8C4A' : '#777';

		printf(
			'<p style="margin:0 0 8px;color:%s"><strong>%s</strong></p>',
			esc_attr( $status_color ),
			esc_html( $status_label )
		);

		printf(
			'<a href="%s" class="%s">%s</a>',
			esc_url( $action_url ),
			esc_attr( $button_class ),
			esc_html( $button_label )
		);
	}

	public function handle_toggle(): void {
		$entry_id = isset( $_GET['entry_id'] ) ? (int) $_GET['entry_id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$next     = isset( $_GET['next'] ) ? (string) $_GET['next'] : '0';    // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $entry_id <= 0 ) {
			wp_die( esc_html__( 'Invalid entry.', 'gf-directory' ), '', [ 'response' => 400 ] );
		}

		if ( ! $this->user_can_toggle() ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'gf-directory' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::ACTION . '_' . $entry_id, self::NONCE_FIELD );

		if ( $next === '1' ) {
			gform_update_meta( $entry_id, EntryQuery::META_PUBLIC_KEY, '1' );
		} else {
			gform_delete_meta( $entry_id, EntryQuery::META_PUBLIC_KEY );
		}

		do_action( 'gfd_entry_publication_changed', $entry_id, $next === '1' );

		$redirect = isset( $_GET['redirect'] ) ? rawurldecode( wp_unslash( (string) $_GET['redirect'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$redirect = $redirect !== '' ? $redirect : admin_url( 'admin.php?page=gf_entries' );

		wp_safe_redirect( $redirect );
		exit;
	}

	private function current_request_uri(): string {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		return $uri !== '' ? $uri : admin_url( 'admin.php?page=gf_entries' );
	}

	/**
	 * Authorization. We accept either the GF-native edit-entries cap (handled
	 * via GFCommon for admin/full-access bypass) or core manage_options. The
	 * second branch keeps the action working for super admins on multisite
	 * setups where GF caps may not be replicated to every site.
	 */
	private function user_can_toggle(): bool {
		if ( class_exists( '\GFCommon' ) && method_exists( '\GFCommon', 'current_user_can_any' ) ) {
			if ( \GFCommon::current_user_can_any( 'gravityforms_edit_entries' ) ) {
				return true;
			}
		}
		return current_user_can( 'manage_options' );
	}
}
