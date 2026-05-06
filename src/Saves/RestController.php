<?php
/**
 * REST endpoints for save/unsave.
 *
 * Routes:
 *   POST  /gf-directory/v1/save    body: { form_id, entry_id }
 *   GET   /gf-directory/v1/saves
 *
 * Auth: WP cookie + REST nonce (X-WP-Nonce). Login required.
 *
 * @package GFDirectory\Saves
 */

declare( strict_types=1 );

namespace GFDirectory\Saves;

use GFDirectory\Query\EntryQuery;
use GFDirectory\Settings\FormSettings;

defined( 'ABSPATH' ) || exit;

final class RestController {

	public const NAMESPACE = 'gf-directory/v1';

	private SavesRepository $repo;

	public function __construct( SavesRepository $repo ) {
		$this->repo = $repo;
	}

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/save',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'toggle_save' ],
				'permission_callback' => [ $this, 'require_logged_in' ],
				'args'                => [
					'form_id'  => [ 'required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint' ],
					'entry_id' => [ 'required' => true, 'type' => 'integer', 'sanitize_callback' => 'absint' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/saves',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'list_saves' ],
				'permission_callback' => [ $this, 'require_logged_in' ],
			]
		);
	}

	/**
	 * Return type omitted because the REST permission_callback contract
	 * permits bool|WP_Error, a union not expressible until PHP 8.0.
	 */
	public function require_logged_in() {
		if ( is_user_logged_in() ) {
			return true;
		}
		return new \WP_Error(
			'gfd_login_required',
			__( 'You must be logged in to save listings.', 'gf-directory' ),
			[ 'status' => 401 ]
		);
	}

	public function toggle_save( \WP_REST_Request $request ): \WP_REST_Response {
		$form_id  = (int) $request->get_param( 'form_id' );
		$entry_id = (int) $request->get_param( 'entry_id' );
		$user_id  = get_current_user_id();

		if ( $form_id <= 0 || $entry_id <= 0 ) {
			return new \WP_REST_Response( [ 'error' => 'invalid_params' ], 400 );
		}

		// The entry must belong to this form, and the form must be a directory.
		$settings = FormSettings::for_form( $form_id );
		if ( ! $settings || ! $settings->is_enabled() ) {
			return new \WP_REST_Response( [ 'error' => 'form_not_directory' ], 404 );
		}

		$entry = \GFAPI::get_entry( $entry_id );
		if ( is_wp_error( $entry ) || (int) ( $entry['form_id'] ?? 0 ) !== $form_id ) {
			return new \WP_REST_Response( [ 'error' => 'entry_not_found' ], 404 );
		}

		// Public-only: prevent users saving entries they have no business seeing.
		$is_public = (string) gform_get_meta( $entry_id, EntryQuery::META_PUBLIC_KEY ) === '1';
		if ( ! $is_public ) {
			return new \WP_REST_Response( [ 'error' => 'entry_not_public' ], 403 );
		}

		$now_saved = $this->repo->toggle( $user_id, $form_id, $entry_id );

		return new \WP_REST_Response(
			[
				'saved'    => $now_saved,
				'entry_id' => $entry_id,
				'form_id'  => $form_id,
			],
			200
		);
	}

	public function list_saves( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();
		$entries = $this->repo->saved_entry_ids( $user_id );

		return new \WP_REST_Response( [ 'entries' => $entries ], 200 );
	}
}
