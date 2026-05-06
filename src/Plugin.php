<?php
/**
 * Plugin bootstrap. Wires every module after Gravity Forms is confirmed loaded.
 *
 * @package GFDirectory
 */

declare( strict_types=1 );

namespace GFDirectory;

use GFDirectory\Addon\DirectoryAddOn;
use GFDirectory\Admin\DemoInstaller;
use GFDirectory\Admin\EntryActions;
use GFDirectory\Admin\EntryColumn;
use GFDirectory\Frontend\Assets;
use GFDirectory\Frontend\DashboardShortcode;
use GFDirectory\Frontend\Rewrite;
use GFDirectory\Frontend\Shortcode;
use GFDirectory\Query\Cache;
use GFDirectory\Render\ArchiveRenderer;
use GFDirectory\Render\CardRenderer;
use GFDirectory\Render\ListRenderer;
use GFDirectory\Render\PaginationRenderer;
use GFDirectory\Render\SearchBarRenderer;
use GFDirectory\Render\SingleRenderer;
use GFDirectory\Render\TemplateLoader;
use GFDirectory\Saves\RestController;
use GFDirectory\Saves\SavesRepository;
use GFDirectory\Saves\SavesTable;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?Plugin $instance = null;
	private bool $booted = false;

	private ?Assets $assets = null;

	public static function instance(): Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		load_plugin_textdomain(
			'gf-directory',
			false,
			dirname( plugin_basename( GFDIRECTORY_FILE ) ) . '/languages'
		);

		// Self-heal: install the saves table even if the user activated before
		// it existed (avoids requiring a deactivate/reactivate cycle).
		add_action( 'init', [ SavesTable::class, 'maybe_install' ], 5 );

		if ( ! $this->gravity_forms_available() ) {
			add_action( 'admin_notices', [ $this, 'render_missing_gf_notice' ] );
			return;
		}

		if ( did_action( 'gform_loaded' ) ) {
			$this->register_addon();
		} else {
			add_action( 'gform_loaded', [ $this, 'register_addon' ], 5 );
		}

		$this->assets = new Assets();
		$this->assets->register();

		( new Rewrite() )->register();

		$loader  = new TemplateLoader();
		$card    = new CardRenderer( $loader );
		$list    = new ListRenderer( $loader );
		$saves   = new SavesRepository();
		$archive = new ArchiveRenderer(
			$loader,
			$card,
			$list,
			new SearchBarRenderer( $loader ),
			new PaginationRenderer(),
			new Cache(),
			$saves
		);
		$single  = new SingleRenderer( $loader, $card, $saves );

		( new Shortcode( $archive, $single, $this->assets ) )->register();
		( new DashboardShortcode( $loader, $card, $saves, $this->assets ) )->register();
		( new RestController( $saves ) )->register();

		( new EntryActions() )->register();
		( new EntryColumn() )->register();
		( new Cache() )->register();
		( new DemoInstaller() )->register();

		add_action( 'init', [ $this, 'register_block' ], 20 );

		$this->booted = true;
	}

	public function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		register_block_type( GFDIRECTORY_DIR . 'blocks/directory' );
	}

	public function register_addon(): void {
		if ( ! method_exists( '\GFForms', 'include_addon_framework' ) ) {
			return;
		}

		\GFForms::include_addon_framework();

		if ( ! class_exists( '\GFAddOn' ) ) {
			return;
		}

		\GFAddOn::register( DirectoryAddOn::class );
		DirectoryAddOn::get_instance();
	}

	private function gravity_forms_available(): bool {
		return class_exists( '\GFForms' );
	}

	public function render_missing_gf_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__(
				'GF Directory requires Gravity Forms to be installed and active.',
				'gf-directory'
			)
		);
	}

	public static function on_activate(): void {
		SavesTable::maybe_install();
		add_rewrite_endpoint( Rewrite::ENDPOINT, EP_PAGES, Rewrite::QUERY_VAR );
		flush_rewrite_rules( false );
	}

	public static function on_deactivate(): void {
		flush_rewrite_rules( false );
	}
}
