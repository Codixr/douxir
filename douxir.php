<?php
/**
 * Plugin Name:       Douxir
 * Description:       Build a clean, searchable documentation hub with nested documents, categories, a table of contents and a drag-and-drop admin screen.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Codixr
 * Author URI:        https://codixr.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       douxir
 * Domain Path:       /languages
 *
 * @package Douxir
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Plugin constants.
if ( ! defined( 'DOUXIR_FILE' ) ) {
	define( 'DOUXIR_FILE', __FILE__ );
}

if ( ! defined( 'DOUXIR_VERSION' ) ) {
	define( 'DOUXIR_VERSION', '1.0.0' );
}

if ( ! defined( 'DOUXIR_URL' ) ) {
	define( 'DOUXIR_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
}

if ( ! defined( 'DOUXIR_PATH' ) ) {
	define( 'DOUXIR_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
}

require_once DOUXIR_PATH . '/inc/class-douxir-plugin.php';

register_activation_hook( DOUXIR_FILE, array( 'Douxir_Plugin', 'activate' ) );
register_deactivation_hook( DOUXIR_FILE, array( 'Douxir_Plugin', 'deactivate' ) );

new Douxir_Plugin();
