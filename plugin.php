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
use DevKabir\WPDebugger\Plugin;
$debugger = new Plugin();
$debugger->init_error_page();


/*
|--------------------------------------------------------------------------
| Initiate Debugbar
|--------------------------------------------------------------------------
*/
$debugger->init_debugbar();

/**
 * Logs a message to a specified directory.
 *
 * @param  mixed  $message The message to be logged.
 * @param  bool   $trace   Whether to log the backtrace.
 * @param  string $dir     The directory where the log file will be written.
 * @return void
 */
function write_log( $message, $trace = false, string $dir = WP_CONTENT_DIR ) {
	$log_file = $dir . '/wp-debugger.log';

	if ( file_exists( $log_file ) && filesize( $log_file ) > 1 * 1024 * 1024 ) {
		file_put_contents( $log_file, '' );
	}

	$message = Plugin::format_log_message( $message );
    error_log($message . PHP_EOL, 3, $log_file); // phpcs:ignore
	if ( $trace ) {
		throw new Exception( $message, 1 );
	}
}


/**
 * Logs the current WordPress filter hook.
 *
 * @global string[] $wp_current_filter List of current filters with the current one last.
 * @return void
 */
function log_current_filter_hook() {
	global $wp_current_filter;
	write_log( implode( ' > ', $wp_current_filter ) );
}

/**
 * Logs the last SQL query error, query, and result.
 *
 * @param  string $dir The directory where the log file will be written.
 * @return void
 */
function log_sql_query( string $dir = WP_CONTENT_DIR ) {
	global $wpdb;
	write_log(
		array(
			'error'  => $wpdb->last_error,
			'query'  => $wpdb->last_query,
			'result' => $wpdb->last_result,
		),
		false,
		$dir
	);
}

// Check if the plugin should be enabled based on the constant in wp-config.php.
if ( defined( 'ENABLE_MOCK_HTTP_INTERCEPTOR' ) && ENABLE_MOCK_HTTP_INTERCEPTOR ) {
	add_filter( 'pre_http_request', array( $debugger, 'intercept_http_requests' ), 10, 3 );
}
