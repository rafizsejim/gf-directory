<?php
/**
 * PSR-4 autoloader for the GFDirectory namespace.
 *
 * @package GFDirectory
 */

declare( strict_types=1 );

namespace GFDirectory;

defined( 'ABSPATH' ) || exit;

final class Autoloader {

	private const PREFIX = __NAMESPACE__ . '\\';

	public static function register(): void {
		spl_autoload_register( [ self::class, 'load' ] );
	}

	public static function load( string $class ): void {
		if ( strpos( $class, self::PREFIX ) !== 0 ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$path     = GFDIRECTORY_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
