<?php
/**
 * HTTP API debugging class.
 *
 * This class logs all HTTP requests and responses to a debug log, for
 * debugging purposes.
 *
 * @package WPDebugger
 */

namespace DevKabir\WPDebugger;

use WP_Error;

/**
 * Debug HTTP API requests and responses to a debug log.
 */
class Http {
	/**
	 * Constructor for the Http class.
	 *
	 * Initializes the HTTP API debugging by adding an action hook that logs requests and responses.
	 */
	public function __construct() {
		add_action( 'http_api_debug', array( $this, 'debug_api' ), 10, 5 );
		// Check if the plugin should be enabled based on the constant in wp-config.php.
		if ( defined( 'ENABLE_MOCK_HTTP_INTERCEPTOR' ) && ENABLE_MOCK_HTTP_INTERCEPTOR ) {
			add_filter( 'pre_http_request', array( $this, 'intercept_http_requests' ), 10, 3 );
		}
	}
	/**
	 * Intercepts outgoing HTTP requests and serves mock responses for predefined URLs.
	 * Stores POST request data in a transient for testing purposes.
	 *
	 * @param bool|array $preempt The preemptive response to return if available.
	 * @param array      $args Array of HTTP request arguments, including method and body.
	 * @param string     $url The URL of the outgoing HTTP request.
	 *
	 * @return array|bool|string|WP_Error Mock response or false to allow original request.
	 */
	public function intercept_http_requests( $preempt, array $args, string $url ) {
		// Only log requests to the external site.
		if ( \strpos( $url, \site_url() ) !== false ) {
			return $preempt;
		}

		$allowed_domains = array(
			'wpmudev.com',
			'rest.akismet.com',
		);
		$requested_host  = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! in_array( $requested_host, $allowed_domains, true ) ) {
			error_log( 'Skipping request logging for  ' . $requested_host );
			return $preempt;
		}

		$mock_urls = array(
			'https://rest.akismet.com/1.1/comment-check' => true,
		);
		/**
		 * Add a filter to the mock_urls array.
		 *
		 * @param array $mock_urls Mock URLs array.
		 *
		 * @return array
		 */
		$mock_urls     = apply_filters( 'wp_debugger_mock_urls', $mock_urls );
		$mock_response = $mock_urls[ $url ] ?? false;
		if ( $mock_response ) {
			return array(
				'body'          => wp_json_encode( $mock_response ),
				'response'      => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'headers'       => array(),
				'cookies'       => array(),
				'http_response' => null,
			);
		}

		return new WP_Error( '404', 'Interceptor enabled by wp-logger plugin.' );
	}

	/**
	 * Logs HTTP API requests and responses to a debug log.
	 *
	 * @param array|\WP_Error $response    HTTP response or \WP_Error object.
	 * @param string          $context     Context under which the hook is fired.
	 * @param string          $class       HTTP transport used.
	 * @param array           $parsed_args HTTP request arguments.
	 * @param string          $url         The request URL.
	 */
	public function debug_api( $response, $context, $class, $parsed_args, $url ): void {
		$domain = wp_parse_url( $url, PHP_URL_HOST );

		// Only log requests to the current site.
		if ( \strpos( $url, \site_url() ) !== false ) {
			return;
		}

		$log = new Log( $domain . '-api-debug.log' );

		// Log errors.
		if ( is_wp_error( $response ) ) {
			$log->write(
				array(
					'URL'      => $url,
					'Response' => $response->get_error_message(),
					'Code'     => $response->get_error_code(),
					'Data'     => array(
						'method'  => $parsed_args['method'],
						'body'    => recursively_decode_json( $parsed_args['body'] ),
						'headers' => $parsed_args['headers'],
					),
				),
				'ERROR'
			);

			return;
		}

		// Log successful responses if the constant is defined.
		if ( defined( 'WP_DEBUGGER_API_DEBUG' ) && WP_DEBUGGER_API_DEBUG ) {
			$log->write(
				array(
					'URL'      => $url,
					'Response' => recursively_decode_json( wp_remote_retrieve_body( $response ) ),
					'Code'     => wp_remote_retrieve_response_code( $response ),
					'Data'     => array(
						'method'  => $parsed_args['method'],
						'body'    => recursively_decode_json( $parsed_args['body'] ),
						'headers' => $parsed_args['headers'],
					),
				),
				'INFO'
			);
		}
	}
}
