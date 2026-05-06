<?php
/**
 * Conditional asset enqueueing.
 *
 * Registers handles early so they are known to wp_enqueue_*. Actually
 * enqueues only on pages that contain a [gf_directory] shortcode (or, in
 * later phases, a directory block / single-entry view). Keeps the directory
 * styles off every other page on the site.
 *
 * @package GFDirectory\Frontend
 */

declare( strict_types=1 );

namespace GFDirectory\Frontend;

defined( 'ABSPATH' ) || exit;

final class Assets {

	private bool $enqueued = false;

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_handles' ], 5 );
	}

	public function register_handles(): void {
		wp_register_style(
			'gf-directory',
			GFDIRECTORY_URL . 'assets/css/directory.css',
			[],
			GFDIRECTORY_VERSION
		);
		wp_register_style(
			'gf-directory-cards',
			GFDIRECTORY_URL . 'assets/css/view-cards.css',
			[ 'gf-directory' ],
			GFDIRECTORY_VERSION
		);
		wp_register_style(
			'gf-directory-list',
			GFDIRECTORY_URL . 'assets/css/view-list.css',
			[ 'gf-directory' ],
			GFDIRECTORY_VERSION
		);
		wp_register_style(
			'gf-directory-single',
			GFDIRECTORY_URL . 'assets/css/single.css',
			[ 'gf-directory', 'gf-directory-cards' ],
			GFDIRECTORY_VERSION
		);
		wp_register_style(
			'gf-directory-dashboard',
			GFDIRECTORY_URL . 'assets/css/dashboard.css',
			[ 'gf-directory' ],
			GFDIRECTORY_VERSION
		);
		wp_register_script(
			'gf-directory',
			GFDIRECTORY_URL . 'assets/js/directory.js',
			[],
			GFDIRECTORY_VERSION,
			[ 'in_footer' => true, 'strategy' => 'defer' ]
		);
	}

	/**
	 * Called by Shortcode immediately before rendering. Idempotent.
	 */
	public function enqueue_for_directory(): void {
		if ( $this->enqueued ) {
			return;
		}
		wp_enqueue_style( 'gf-directory' );
		wp_enqueue_style( 'gf-directory-cards' );
		wp_enqueue_style( 'gf-directory-list' );
		wp_enqueue_style( 'gf-directory-single' );
		wp_enqueue_style( 'gf-directory-dashboard' );
		wp_enqueue_script( 'gf-directory' );

		wp_localize_script(
			'gf-directory',
			'gfdSaves',
			[
				'restUrl'        => esc_url_raw( rest_url( 'gf-directory/v1/' ) ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'loggedIn'       => is_user_logged_in(),
				'loginUrl'       => wp_login_url( $this->current_url() ),
				'loginPrompt'    => __( 'Please log in to save listings.', 'gf-directory' ),
				'loginLabel'     => __( 'Log in', 'gf-directory' ),
				'savedLabel'     => __( 'Saved to your dashboard', 'gf-directory' ),
				'unsavedLabel'   => __( 'Removed from saved', 'gf-directory' ),
				'saveWordLabel'  => __( 'Save', 'gf-directory' ),
				'savedWordLabel' => __( 'Saved', 'gf-directory' ),
			]
		);

		$this->enqueued = true;
	}

	private function current_url(): string {
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
		return home_url( $path );
	}
}
