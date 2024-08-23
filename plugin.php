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
 * @param bool   $trace   Whether to log the backtrace.
 * @param string $dir The directory where the log file will be written.
 * @return void
 */
function write_log( $message, $trace = false, string $dir = WP_CONTENT_DIR ) {
	$message = format_message( $message );
       error_log( $message . PHP_EOL, 3, $dir . '/wp-debugger.log' ); // phpcs:ignore
	if ( $trace ) {
           error_log( format_message( array_column( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ), 'file', 'function' ) ) . PHP_EOL, 3, $dir . '/wp-debugger.log' ); // phpcs:ignore
	}

}
/**
 * Formats a message with the current timestamp.
 *
 * @param mixed $message The message to be formatted.
 * @return string The formatted message with the timestamp.
 */
function format_message( $message ) {
	// Format message if it's an array, object, or iterable.
	if ( is_array( $message ) || is_object( $message ) || is_iterable( $message ) ) {
		$message = wp_json_encode( $message, 128 );
	} else {
		$decoded = json_decode( $message );
		if ( JSON_ERROR_NONE === json_last_error() ) {
			$message = wp_json_encode( $decoded, 128 );
		}
	}
	return gmdate( 'Y-m-d H:i:s' ) . ' - ' . $message;

}
/**
 * Writes the last SQL query error, query, and result to a log file.
 *
 * @param string $dir The directory where the log file will be written.
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
		false,
		$dir
	);
}


// Check if the plugin should be enabled based on the constant in wp-config.php
if (  defined( 'ENABLE_MOCK_HTTP_PLUGIN' ) ||  ENABLE_MOCK_HTTP_PLUGIN ) {
/**
 * Intercepts outgoing HTTP requests and serves mock responses for specified URLs.
 * Stores POST request data in a transient for testing purposes.
 *
 * This function hooks into the HTTP request process using the `pre_http_request` filter.
 * When an outgoing HTTP request matches one of the predefined URLs, the function returns
 * a mock response instead of making an actual HTTP request. For POST requests, the function
 * stores the POST data in a transient for later inspection.
 *
 * @param bool|array $preempt The preemptive response to return if available. 
 *                            If `false`, the function will proceed to make the actual HTTP request.
 * @param array $args Array of HTTP request arguments, including method and body.
 * @param string $url The URL of the outgoing HTTP request.
 *
 * @return array|bool If a matching URL is found in the predefined mock responses, an 
 *                     associative array containing mock response details is returned. 
 *                     Otherwise, `false` is returned, allowing the original request to proceed.
 *
 */
function serve_mock_http_response( $preempt, $args, $url ) {
    // Define the mock responses for specific URLs
    $mock_urls = array(
        'https://api.example.com/v1/data' => json_encode( array( 'message' => 'Mock Data Response', 'status' => 'success' ) ),
        'https://api.example.com/v1/user' => json_encode( array( 'user' => 'John Doe', 'id' => 1234 ) ),
        'https://api.example.com/v1/error' => json_encode( array( 'error' => 'This is a mock error response' ) ),
    );

    // Check if the requested URL matches any mock URLs
    foreach ( $mock_urls as $mock_url => $mock_response ) {
        if ( strpos( $url, $mock_url ) !== false ) {
            // Store POST data in a transient if the request method is POST
            if ( isset( $args['method'] ) && strtoupper( $args['method'] ) === 'POST' && isset( $args['body'] ) ) {
                $post_data = wp_parse_args( $args['body'] );
                // Store POST data in a transient with a unique key based on the URL
                $transient_key = 'mock_post_data_' . md5( $url );
                set_transient( $transient_key, $post_data, 60 * 60 ); // Store for 1 hour
            }

            // Return a mock response instead of making an actual HTTP request
            return array(
                'body'        => $mock_response,
                'response'    => array(
                    'code' => 200,
                    'message' => 'OK',
                ),
                'headers'     => array(),
                'cookies'     => array(),
                'http_response' => null,
            );
        }
    }

    // If no match, return the original request (no interception)
    return false;
}

// Hook into the HTTP request process
add_filter( 'pre_http_request', 'serve_mock_http_response', 10, 3 );

	
}

