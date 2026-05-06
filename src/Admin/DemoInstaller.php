<?php
/**
 * One-click demo installer.
 *
 * Creates a Properties form, configures the directory addon settings on it,
 * imports six pre-approved sample entries, and creates an archive page plus
 * a dashboard page. Idempotent: safe to call repeatedly. Provides an
 * "uninstall demo" path for cleanup.
 *
 * @package GFDirectory\Admin
 */

declare( strict_types=1 );

namespace GFDirectory\Admin;

use GFDirectory\Addon\DirectoryAddOn;
use GFDirectory\Query\EntryQuery;

defined( 'ABSPATH' ) || exit;

final class DemoInstaller {

	private const STATE_OPTION    = 'gfd_demo_state';
	private const ACTION_INSTALL  = 'gfd_install_demo';
	private const ACTION_REMOVE   = 'gfd_remove_demo';
	private const NONCE_FIELD     = '_gfd_demo_nonce';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_post_' . self::ACTION_INSTALL, [ $this, 'handle_install' ] );
		add_action( 'admin_post_' . self::ACTION_REMOVE, [ $this, 'handle_remove' ] );
		add_action( 'admin_notices', [ $this, 'maybe_show_notice' ] );
	}

	public function register_admin_page(): void {
		// Parented under Tools (core WP file `tools.php`) instead of Gravity
		// Forms' menu, because GF builds its menu by mutating the $menu /
		// $submenu globals directly rather than using add_menu_page. That
		// breaks WordPress's submenu URL generator and produces invalid
		// `/wp-admin/<slug>/` links instead of the expected
		// `/wp-admin/admin.php?page=<slug>`. Tools is core, so URL generation
		// is reliable.
		add_submenu_page(
			'tools.php',
			esc_html__( 'GF Directory', 'gf-directory' ),
			esc_html__( 'GF Directory', 'gf-directory' ),
			'manage_options',
			'gf-directory-tools',
			[ $this, 'render_admin_page' ]
		);
	}

	public function maybe_show_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || strpos( (string) $screen->id, 'gf_edit_forms' ) === false ) {
			return;
		}
		if ( $this->is_installed() ) {
			return;
		}
		if ( get_user_meta( get_current_user_id(), 'gfd_demo_notice_dismissed', true ) ) {
			return;
		}

		$install_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ACTION_INSTALL ),
			self::ACTION_INSTALL,
			self::NONCE_FIELD
		);
		printf(
			'<div class="notice notice-info is-dismissible" data-gfd-notice="demo"><p>%s <a href="%s" class="button button-primary" style="margin-left:8px">%s</a></p></div>',
			esc_html__( 'Want to see GF Directory in action? Install the one-click demo: imports a Properties form with six pre-approved sample listings and creates the archive + dashboard pages.', 'gf-directory' ),
			esc_url( $install_url ),
			esc_html__( 'Install demo', 'gf-directory' )
		);
	}

	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'gf-directory' ) );
		}

		$state     = $this->get_state();
		$installed = $this->is_installed();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'GF Directory', 'gf-directory' ) . '</h1>';
		echo '<p>' . esc_html__( 'Tools for setting up your directory quickly.', 'gf-directory' ) . '</p>';

		echo '<div class="card" style="max-width:720px">';
		echo '<h2>' . esc_html__( 'Demo data', 'gf-directory' ) . '</h2>';

		if ( ! $installed ) {
			echo '<p>' . esc_html__( 'Installs a Properties form pre-configured with the directory enabled, six sample listings, plus an archive page and a dashboard page so you can immediately see the plugin in action.', 'gf-directory' ) . '</p>';
			$install_url = wp_nonce_url(
				admin_url( 'admin-post.php?action=' . self::ACTION_INSTALL ),
				self::ACTION_INSTALL,
				self::NONCE_FIELD
			);
			echo '<p><a href="' . esc_url( $install_url ) . '" class="button button-primary">' . esc_html__( 'Install demo', 'gf-directory' ) . '</a></p>';
		} else {
			echo '<p>' . esc_html__( 'Demo is installed. Pages and form created:', 'gf-directory' ) . '</p>';
			echo '<ul style="list-style:disc;padding-left:24px">';
			if ( ! empty( $state['archive_page_id'] ) ) {
				$url = (string) get_permalink( (int) $state['archive_page_id'] );
				echo '<li><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html__( 'Properties Directory page', 'gf-directory' ) . '</a></li>';
			}
			if ( ! empty( $state['dashboard_page_id'] ) ) {
				$url = (string) get_permalink( (int) $state['dashboard_page_id'] );
				echo '<li><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html__( 'My Dashboard page', 'gf-directory' ) . '</a></li>';
			}
			if ( ! empty( $state['form_id'] ) ) {
				$url = admin_url( 'admin.php?page=gf_edit_forms&id=' . (int) $state['form_id'] );
				echo '<li><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html__( 'Properties form (admin)', 'gf-directory' ) . '</a></li>';
			}
			echo '</ul>';

			$remove_url = wp_nonce_url(
				admin_url( 'admin-post.php?action=' . self::ACTION_REMOVE ),
				self::ACTION_REMOVE,
				self::NONCE_FIELD
			);
			echo '<p style="margin-top:16px"><a href="' . esc_url( $remove_url ) . '" class="button button-secondary" onclick="return confirm(\'' . esc_attr__( 'Remove demo form, entries, and pages?', 'gf-directory' ) . '\')">' . esc_html__( 'Remove demo', 'gf-directory' ) . '</a></p>';
		}

		echo '</div>';
		echo '</div>';
	}

	public function handle_install(): void {
		$this->guard( self::ACTION_INSTALL );

		if ( ! class_exists( '\GFAPI' ) ) {
			wp_die( esc_html__( 'Gravity Forms is not active.', 'gf-directory' ) );
		}

		if ( $this->is_installed() ) {
			$this->redirect_with_notice( 'already' );
		}

		$form_id = (int) \GFAPI::add_form( $this->build_form() );
		if ( $form_id <= 0 ) {
			$this->redirect_with_notice( 'form_failed' );
		}

		$this->save_directory_settings( $form_id );
		$entry_ids = $this->seed_entries( $form_id );
		$archive   = $this->create_page(
			__( 'Properties Directory', 'gf-directory' ),
			'[gf_directory form="' . $form_id . '"]'
		);
		$dashboard = $this->create_page(
			__( 'My Dashboard', 'gf-directory' ),
			'[gf_directory_dashboard]'
		);

		$this->set_state(
			[
				'form_id'           => $form_id,
				'entry_ids'         => $entry_ids,
				'archive_page_id'   => $archive,
				'dashboard_page_id' => $dashboard,
				'installed_at'      => current_time( 'mysql', true ),
			]
		);

		// Flush so the /listing/{id}/ endpoint is routable on the new page
		// without making the user click Settings → Permalinks first.
		flush_rewrite_rules( false );

		$this->redirect_with_notice( 'installed' );
	}

	public function handle_remove(): void {
		$this->guard( self::ACTION_REMOVE );

		$state = $this->get_state();
		if ( ! empty( $state['archive_page_id'] ) ) {
			wp_delete_post( (int) $state['archive_page_id'], true );
		}
		if ( ! empty( $state['dashboard_page_id'] ) ) {
			wp_delete_post( (int) $state['dashboard_page_id'], true );
		}
		if ( ! empty( $state['entry_ids'] ) ) {
			foreach ( (array) $state['entry_ids'] as $eid ) {
				\GFAPI::delete_entry( (int) $eid );
			}
		}
		if ( ! empty( $state['form_id'] ) ) {
			\GFAPI::delete_form( (int) $state['form_id'] );
		}

		delete_option( self::STATE_OPTION );
		$this->redirect_with_notice( 'removed' );
	}

	private function guard( string $action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permission.', 'gf-directory' ), '', [ 'response' => 403 ] );
		}
		$nonce = isset( $_REQUEST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ self::NONCE_FIELD ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'gf-directory' ), '', [ 'response' => 400 ] );
		}
	}

	private function redirect_with_notice( string $code ): void {
		wp_safe_redirect(
			add_query_arg(
				[ 'page' => 'gf-directory-tools', 'gfd_notice' => $code ],
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	public function is_installed(): bool {
		$state = $this->get_state();
		return ! empty( $state['form_id'] ) && (int) $state['form_id'] > 0;
	}

	private function get_state(): array {
		$state = get_option( self::STATE_OPTION, [] );
		return is_array( $state ) ? $state : [];
	}

	private function set_state( array $state ): void {
		update_option( self::STATE_OPTION, $state, false );
	}

	private function save_directory_settings( int $form_id ): void {
		$form = \GFAPI::get_form( $form_id );
		if ( ! is_array( $form ) ) {
			return;
		}

		$settings = [
			'enabled'             => '1',
			'enabled_views_card'  => '1',
			'enabled_views_list'  => '1',
			'default_view'        => 'card',
			'card_style'          => 'framed',
			'cards_per_row'       => '3',
			'show_hero'           => '1',
			'hero_title'          => 'Find your next property',
			'hero_subtitle'       => 'Curated investments and stays from across the globe',
			'search_placeholder'  => 'Search by name or location…',
			'no_results_text'     => 'No properties match your filters yet.',
			'cta_label'           => 'View details',
			'field_title'         => '1',
			'field_subtitle'      => '2',
			'field_image'         => '4',
			'field_badge'         => '5',
			'field_price'         => '6',
			'field_description'   => '3',
			'field_features'      => '16',
			'field_cta_url'       => '17',
			'meta_icon_1_field'   => '7',  'meta_icon_1_icon' => 'bed',
			'meta_icon_2_field'   => '8',  'meta_icon_2_icon' => 'bath',
			'meta_icon_3_field'   => '9',  'meta_icon_3_icon' => 'area',
			'meta_icon_4_field'   => '10', 'meta_icon_4_icon' => 'car',
			'stat_1_field'        => '11', 'stat_1_label' => 'Token price',
			'stat_2_field'        => '12', 'stat_2_label' => 'Projected IRR',
			'stat_3_field'        => '13', 'stat_3_label' => 'Project APR',
			'searchable_fields'   => [ '1', '2', '3' ],
			'filter_field_1'      => '14',
			'filter_field_2'      => '15',
			'sort_options'        => [ '6' ],
			'per_page'            => '12',
			'color_accent'        => '#6D5CE7',
			'color_badge'         => '#1A8C4A',
			'color_hero_bg'       => '#FFFFFF',
			'color_hero_text'     => '#0F1115',
		];

		DirectoryAddOn::get_instance()->save_form_settings( $form, $settings );
	}

	private function create_page( string $title, string $content ): int {
		$existing = get_page_by_path( sanitize_title( $title ) );
		if ( $existing instanceof \WP_Post ) {
			wp_update_post(
				[
					'ID'           => $existing->ID,
					'post_status'  => 'publish',
					'post_content' => $content,
				]
			);
			return (int) $existing->ID;
		}

		$id = wp_insert_post(
			[
				'post_title'   => $title,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $content,
			]
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	private function build_form(): array {
		return [
			'title'       => 'Properties Directory (Demo)',
			'description' => 'Sample directory generated by GF Directory.',
			'fields'      => [
				$this->field_text( 1, 'Property Name', true ),
				$this->field_text( 2, 'Location' ),
				$this->field_textarea( 3, 'Description' ),
				$this->field_fileupload( 4, 'Photos' ),
				$this->field_select( 5, 'Status', [ 'Active', 'Pending', 'Sold' ] ),
				$this->field_number( 6, 'Price (USD)' ),
				$this->field_number( 7, 'Bedrooms' ),
				$this->field_number( 8, 'Bathrooms' ),
				$this->field_number( 9, 'Area (sqft)' ),
				$this->field_number( 10, 'Parking spaces' ),
				$this->field_number( 11, 'Token price' ),
				$this->field_text( 12, 'Projected IRR' ),
				$this->field_text( 13, 'Project APR' ),
				$this->field_select( 14, 'Property Type', [ 'Apartment', 'House', 'Condo', 'Studio', 'Penthouse' ] ),
				$this->field_select( 15, 'City', [ 'London', 'Berlin', 'Paris', 'Madrid', 'Tokyo', 'New York', 'Karachi', 'Lisbon' ] ),
				$this->field_checkboxes( 16, 'Features', [ 'WiFi', 'Parking', 'Pool', 'Garden', 'Gym', 'Pet friendly', 'Air conditioning' ] ),
				$this->field_text( 17, 'Listing URL' ),
			],
		];
	}

	private function field_text( int $id, string $label, bool $required = false ): array {
		return [ 'id' => $id, 'type' => 'text', 'label' => $label, 'isRequired' => $required ];
	}

	private function field_textarea( int $id, string $label ): array {
		return [ 'id' => $id, 'type' => 'textarea', 'label' => $label ];
	}

	private function field_number( int $id, string $label ): array {
		return [ 'id' => $id, 'type' => 'number', 'label' => $label ];
	}

	private function field_fileupload( int $id, string $label ): array {
		return [ 'id' => $id, 'type' => 'fileupload', 'label' => $label, 'multipleFiles' => true ];
	}

	private function field_select( int $id, string $label, array $options ): array {
		$choices = array_map( static fn( string $o ): array => [ 'text' => $o, 'value' => $o ], $options );
		return [ 'id' => $id, 'type' => 'select', 'label' => $label, 'choices' => $choices ];
	}

	private function field_checkboxes( int $id, string $label, array $options ): array {
		$choices = [];
		$inputs  = [];
		foreach ( $options as $i => $opt ) {
			$choices[] = [ 'text' => $opt, 'value' => $opt ];
			$inputs[]  = [ 'id' => $id . '.' . ( $i + 1 ), 'label' => $opt ];
		}
		return [ 'id' => $id, 'type' => 'checkbox', 'label' => $label, 'choices' => $choices, 'inputs' => $inputs ];
	}

	/**
	 * @return int[]
	 */
	private function seed_entries( int $form_id ): array {
		$rows = [
			[
				'name' => 'Sunset Villa', 'location' => 'Marbella, Spain', 'desc' => 'Mediterranean villa with private pool, ten minutes from Puerto Banús.',
				'badge' => 'Active', 'price' => '850000', 'bed' => 3, 'bath' => 2, 'area' => 420, 'park' => 2,
				'token' => 50, 'irr' => '18.9%', 'apr' => '15.2%',
				'type' => 'House', 'city' => 'Madrid',
				'features' => [ 'WiFi', 'Pool', 'Garden', 'Air conditioning' ],
			],
			[
				'name' => 'Karachi Marriott Suite', 'location' => 'Karachi, Pakistan', 'desc' => 'Luxury suite in the heart of Karachi with skyline views and 24-hour service.',
				'badge' => 'Active', 'price' => '270', 'bed' => 1, 'bath' => 1, 'area' => 65, 'park' => 0,
				'token' => 12, 'irr' => '6.4%', 'apr' => '5.1%',
				'type' => 'Apartment', 'city' => 'Karachi',
				'features' => [ 'WiFi', 'Gym', 'Air conditioning' ],
			],
			[
				'name' => 'Tokyo Skyline Loft', 'location' => 'Shibuya, Tokyo', 'desc' => 'Modern loft with floor-to-ceiling windows overlooking the city.',
				'badge' => 'Active', 'price' => '1200000', 'bed' => 4, 'bath' => 3, 'area' => 600, 'park' => 1,
				'token' => 80, 'irr' => '22.1%', 'apr' => '17.4%',
				'type' => 'Apartment', 'city' => 'Tokyo',
				'features' => [ 'WiFi', 'Gym', 'Pet friendly' ],
			],
			[
				'name' => 'Berlin Modern Studio', 'location' => 'Mitte, Berlin', 'desc' => 'Compact studio with polished concrete floors and a smart-home setup.',
				'badge' => 'Active', 'price' => '480000', 'bed' => 1, 'bath' => 1, 'area' => 220, 'park' => 0,
				'token' => 25, 'irr' => '11.2%', 'apr' => '9.5%',
				'type' => 'Studio', 'city' => 'Berlin',
				'features' => [ 'WiFi', 'Pet friendly', 'Air conditioning' ],
			],
			[
				'name' => 'Lisbon Beach Condo', 'location' => 'Cascais, Portugal', 'desc' => 'Two-bedroom condo with private beach access and rooftop pool.',
				'badge' => 'Pending', 'price' => '620000', 'bed' => 2, 'bath' => 2, 'area' => 350, 'park' => 1,
				'token' => 38, 'irr' => '14.0%', 'apr' => '12.6%',
				'type' => 'Condo', 'city' => 'Lisbon',
				'features' => [ 'WiFi', 'Pool', 'Parking' ],
			],
			[
				'name' => 'NYC Tribeca Penthouse', 'location' => 'Tribeca, New York', 'desc' => 'Penthouse with wraparound terrace and Hudson river views.',
				'badge' => 'Active', 'price' => '4500000', 'bed' => 5, 'bath' => 4, 'area' => 1200, 'park' => 2,
				'token' => 250, 'irr' => '9.7%', 'apr' => '8.2%',
				'type' => 'Penthouse', 'city' => 'New York',
				'features' => [ 'WiFi', 'Gym', 'Pool', 'Pet friendly', 'Parking', 'Air conditioning' ],
			],
		];

		$ids = [];
		foreach ( $rows as $i => $row ) {
			$photos = wp_json_encode(
				[
					sprintf( 'https://picsum.photos/seed/gfd%dA/1024/700', $i + 1 ),
					sprintf( 'https://picsum.photos/seed/gfd%dB/1024/700', $i + 1 ),
					sprintf( 'https://picsum.photos/seed/gfd%dC/1024/700', $i + 1 ),
				]
			);

			$entry = [
				'form_id'      => $form_id,
				'1'            => $row['name'],
				'2'            => $row['location'],
				'3'            => $row['desc'],
				'4'            => $photos,
				'5'            => $row['badge'],
				'6'            => $row['price'],
				'7'            => $row['bed'],
				'8'            => $row['bath'],
				'9'            => $row['area'],
				'10'           => $row['park'],
				'11'           => $row['token'],
				'12'           => $row['irr'],
				'13'           => $row['apr'],
				'14'           => $row['type'],
				'15'           => $row['city'],
				'17'           => 'https://example.com/listings/' . sanitize_title( $row['name'] ),
				'date_created' => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( $i + 1 ) . ' days' ) ),
			];

			foreach ( $row['features'] as $idx => $feature ) {
				$key           = '16.' . ( array_search( $feature, [ 'WiFi', 'Parking', 'Pool', 'Garden', 'Gym', 'Pet friendly', 'Air conditioning' ], true ) + 1 );
				$entry[ $key ] = $feature;
			}

			$entry_id = \GFAPI::add_entry( $entry );
			if ( is_wp_error( $entry_id ) || $entry_id <= 0 ) {
				continue;
			}
			gform_update_meta( (int) $entry_id, EntryQuery::META_PUBLIC_KEY, '1' );
			$ids[] = (int) $entry_id;
		}

		return $ids;
	}
}
