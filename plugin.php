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
 *
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
| Loading all registered classes.
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Initiate error page.
|--------------------------------------------------------------------------
*/
DevKabir\WPDebugger\Plugin::get_instance();

/**
 * Logs a message to a specified directory.
 *
 * @param mixed  $message The message to be logged.
 * @param bool   $trace Whether to log the backtrace.
 * @param string $dir The directory where the log file will be written.
 * @return void
 */
function write_log( $message, $trace = false, string $dir = WP_CONTENT_DIR ) {
	DevKabir\WPDebugger\Plugin::get_instance()->log( $message, $trace, $dir );
}

/**
 * Debug from called spot.
 *
 * @return void
 * @throws Exception
 */
function init_debugger() {
	DevKabir\WPDebugger\Plugin::get_instance()->throw_exception();
}