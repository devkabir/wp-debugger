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
 */

namespace DevKabir\WPDebugger;

/*
|--------------------------------------------------------------------------
| If this file is called directly, abort.
|--------------------------------------------------------------------------
*/
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


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
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;


$whoops = new Run();
$whoops->pushHandler( new PrettyPageHandler() );
$whoops->register();


/**
 * Writes a log message to a specified directory.
 *
 * @param mixed  $message The message to be logged.
 * @param string $dir The directory where the log file will be written.
 * @return void
 */
function write_log( $message, string $dir = WP_CONTENT_DIR ) {
	$message = format_message( $message );
	error_log($message . PHP_EOL, 3, $dir . '/wp-debugger.log'); // phpcs:ignore
}

/**
 * Formats a message with the current timestamp.
 *
 * @param mixed $message The message to be formatted.
 * @return string The formatted message with the timestamp.
 */
function format_message( $message ) {
	// format message if it's an array.
	if ( is_array( $message ) || is_object( $message ) || is_iterable( $message ) ) {
		$message = wp_json_encode( $message, 128 );
	} else {
		$decoded = json_decode( $message );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$message = wp_json_encode( $decoded, 128 );
		}
	}
	return gmdate( 'Y-m-d H:i:s' ) . ' - ' . $message;
}


/**
 * Writes the last SQL query error, query, and result to a log file.
 *
 * @param string $dir The directory and filename of the log file. Defaults to WP_CONTENT_DIR.
 * @return void
 */
function write_query( string $dir = WP_CONTENT_DIR ) {
	global $wpdb;
	write_log(
		array(
			'error'  => $wpdb->last_error,
			'query'  => $wpdb->last_query,
			'result' => $wpdb->last_result,
		),
		$dir
	);
}
