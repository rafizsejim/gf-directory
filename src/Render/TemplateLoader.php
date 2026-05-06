<?php
/**
 * Template resolver with theme-override hierarchy.
 *
 * Lookup order:
 *   1. {child theme}/gf-directory/{template}.php
 *   2. {parent theme}/gf-directory/{template}.php
 *   3. {plugin}/templates/{template}.php
 *
 * @package GFDirectory\Render
 */

declare( strict_types=1 );

namespace GFDirectory\Render;

defined( 'ABSPATH' ) || exit;

final class TemplateLoader {

	public function render( string $template, array $vars = [] ): string {
		$file = $this->locate( $template );
		if ( $file === '' ) {
			return '';
		}

		ob_start();
		( static function ( string $__file, array $__vars ): void {
			extract( $__vars, EXTR_SKIP );
			include $__file;
		} )( $file, $vars );

		return (string) ob_get_clean();
	}

	public function locate( string $template ): string {
		$template = ltrim( $template, '/' );
		if ( substr( $template, -4 ) !== '.php' ) {
			$template .= '.php';
		}

		$relative = 'gf-directory/' . $template;
		$theme    = locate_template( [ $relative ], false, false );
		if ( $theme !== '' ) {
			return $theme;
		}

		$plugin = GFDIRECTORY_DIR . 'templates/' . $template;
		return is_readable( $plugin ) ? $plugin : '';
	}
}
