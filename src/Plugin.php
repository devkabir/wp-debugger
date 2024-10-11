<?php

namespace DevKabir\WPDebugger;

use Whoops\Run;
use DebugBar\DebugBar;
use DebugBar\StandardDebugBar;
use Whoops\Handler\PrettyPageHandler;

class Plugin {

	public function __construct() {
		$this->debugbar = new DebugBar();
	}

	public function init_error_page() {
		$whoops = new Run();
		$page   = new PrettyPageHandler();
		$page->setEditor( 'vscode' );
		$whoops->pushHandler( $page );
		$whoops->register();
	}

	/**
	 * Intercepts outgoing HTTP requests and serves mock responses for predefined URLs.
	 * Stores POST request data in a transient for testing purposes.
	 *
	 * @param bool|array $preempt The preemptive response to return if available.
	 * @param array      $args    Array of HTTP request arguments, including method and body.
	 * @param string     $url     The URL of the outgoing HTTP request.
	 *
	 * @return mixed Mock response or false to allow original request.
	 */
	public function intercept_http_requests( $preempt, $args, $url ) {
		if ( strpos( $url, 'https://wpmudev.com/api/' ) === false ) {
			return $preempt;
		}

		$mock_logs_dir = WP_CONTENT_DIR . '/mock-logs';
		if ( ! is_dir( $mock_logs_dir ) ) {
			wp_mkdir_p( $mock_logs_dir );
		}

		$mock_urls = array(
			'/hosting' =>
				array(
					'is_enabled' => false,
					'waf'        => array(
						'is_active' => false,
					),
				),
		);

		foreach ( $mock_urls as $mock_url => $mock_response ) {
			if ( strpos( $url, $mock_url ) !== false ) {
				if ( isset( $args['method'] ) && strtoupper( $args['method'] ) === 'POST' && isset( $args['body'] ) ) {
					$post_data     = wp_parse_args( $args['body'] );
					$transient_key = 'mock_post_data_' . md5( $url . $args['method'] );
					set_transient( $transient_key, $post_data, 60 * 60 ); // Store for 1 hour
				}

				write_log( array( $url, $args, $mock_response ), false, $mock_logs_dir );
				return json_encode(
					array(
						'body'          => $mock_response,
						'response'      => array(
							'code'    => 200,
							'message' => 'OK',
						),
						'headers'       => array(),
						'cookies'       => array(),
						'http_response' => null,
					)
				);
			}
		}

		return new \WP_Error( '404', 'Interceptor enabled by wp-logger plugin.' );
	}

	public function init_debugbar() {
		$debugbar         = new StandardDebugBar();
		$debugbarRenderer = $debugbar->getJavascriptRenderer();

		// Use Debugbar to log queries, data, etc.
		$debugbar['messages']->addMessage( 'Debugbar is loaded!' );

		// Example usage of logging a database query
		global $wpdb;
		$query   = "SELECT * FROM $wpdb->posts LIMIT 10";
		$results = $wpdb->get_results( $query );
		$debugbar['messages']->addMessage( 'Query executed: ' . $query );
		$debugbar['messages']->addMessage( $results );
		// Add Debugbar HTML and JavaScript to WordPress footer
		add_action(
			'wp_footer',
			function () use ( $debugbarRenderer ) {
				echo $debugbarRenderer->renderHead();
				echo $debugbarRenderer->render();
			}
		);
	}

	/**
	 * Formats a message with the current timestamp for logging.
	 *
	 * @param  mixed $message The message to be formatted.
	 * @return string The formatted message with the timestamp.
	 */
	public static function format_log_message( $message ) {
		if ( is_array( $message ) || is_object( $message ) || is_iterable( $message ) ) {
			$message = wp_json_encode( $message, 128 );
		} else {
			$decoded = json_decode( $message, true );
			if ( JSON_ERROR_NONE === json_last_error() && is_array( $decoded ) ) {
				$message = wp_json_encode( $decoded, 128 );
			}
		}
		return gmdate( 'Y-m-d H:i:s' ) . ' - ' . $message;
	}
}
