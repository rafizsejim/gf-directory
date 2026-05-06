<?php
/**
 * Plugin Name:       GF Directory
 * Plugin URI:        https://github.com/rafizsejim/gf-directory
 * Description:       Turns Gravity Forms entries into a frontend directory with card and list views, search, filters, saves and a user dashboard.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Rafiz Sejim
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gf-directory
 * Domain Path:       /languages
 *
 * @package GFDirectory
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'GFDIRECTORY_VERSION', '0.1.0' );
define( 'GFDIRECTORY_FILE', __FILE__ );
define( 'GFDIRECTORY_DIR', plugin_dir_path( __FILE__ ) );
define( 'GFDIRECTORY_URL', plugin_dir_url( __FILE__ ) );
define( 'GFDIRECTORY_MIN_GF_VERSION', '2.5' );

require_once GFDIRECTORY_DIR . 'src/Autoloader.php';
\GFDirectory\Autoloader::register();

add_action( 'plugins_loaded', static function (): void {
	\GFDirectory\Plugin::instance()->boot();
} );

register_activation_hook( __FILE__, [ \GFDirectory\Plugin::class, 'on_activate' ] );
register_deactivation_hook( __FILE__, [ \GFDirectory\Plugin::class, 'on_deactivate' ] );
