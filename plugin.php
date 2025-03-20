<?php
/**
 * WP Debugger
 *
 * @wordpress-plugin
 * Plugin Name:       WP Debugger
 * Plugin URI:        https://github.com/devkabir/wp-debugger
 * Description:       Nice Error page for WordPress Developers
 * Version:           1.0.0
 * Requires at least: 5.3
 * Requires PHP:      7.1
 * Author:            Dev Kabir
 * Author URI:        https://devkabir.github.io/
 * Text Domain:       wp-debugger
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Network:           true
 * @package DevKabir\WPDebugger
 */

/*
|--------------------------------------------------------------------------
| If this file is called directly, abort.
|--------------------------------------------------------------------------
*/
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'DevKabir\WPDebugger\FILE', __FILE__ );

/*
|--------------------------------------------------------------------------
| Loading all registered methods.
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/autoload.php';

/*
|--------------------------------------------------------------------------
| Initiate error page.
|--------------------------------------------------------------------------
*/
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	DevKabir\WPDebugger\Plugin::get_instance();
}
