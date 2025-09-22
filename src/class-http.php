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

		if ( defined( 'ENABLE_MOCK_HTTP_INTERCEPTOR' ) && ENABLE_MOCK_HTTP_INTERCEPTOR ) {
			add_filter( 'pre_http_request', array( $this, 'intercept_http_requests' ), 10, 3 );
		}

		add_action( 'requests-requests.before_request', array( $this, 'start_timer' ), 10, 2 );
		add_action( 'requests-requests.after_request', array( $this, 'end_timer' ), 10, 2 );
	}
	/**
	 * Timer storage for performance monitoring.
	 *
	 * @var array
	 */
	private $request_timers = array();

	/**
	 * Intercepts outgoing HTTP requests and serves mock responses for predefined URLs.
	 *
	 * @param bool|array $preempt The preemptive response to return if available.
	 * @param array      $args Array of HTTP request arguments, including method and body.
	 * @param string     $url The URL of the outgoing HTTP request.
	 *
	 * @return array|bool|string|WP_Error Mock response or false to allow original request.
	 */
	public function intercept_http_requests( $preempt, array $args, string $url ) {
		if ( \strpos( $url, \site_url() ) !== false ) {
			return $preempt;
		}

		$mock_urls = array(
			'https://rest.akismet.com/1.1/comment-check' => array(
				'spam'    => false,
				'discard' => false,
			),
			'https://api.wordpress.org/core/version-check/1.7/' => array(
				'offers' => array(),
			),
		);

		/**
		 * Filter to modify the mock URLs array.
		 *
		 * @param array $mock_urls Mock URLs array with responses.
		 *
		 * @return array
		 */
		$mock_urls = apply_filters( 'wp_debugger_mock_urls', $mock_urls );

		$mock_response = $mock_urls[ $url ] ?? null;
		foreach ( $mock_urls as $mock_url => $response_data ) {
			if ( $url === $mock_url || fnmatch( $mock_url, $url ) ) {
				$mock_response = $response_data;
				break;
			}
		}

		if ( ! $mock_response ) {
			write_log( 'Mock response not found for URL: ' . $url );
			return $preempt;
		}

		if ( is_wp_error( $mock_response ) ) {
			return $mock_response;
		}

		return array(
			'body'          => wp_json_encode( $mock_response ),
			'response'      => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'headers'       => array(
				'content-type'    => 'application/json',
				'x-mock-response' => 'true',
			),
			'cookies'       => array(),
			'http_response' => null,
		);
	}

	/**
	 * Start timing for HTTP request performance monitoring.
	 *
	 * @param string $url Request URL.
	 * @param array  $headers Request headers.
	 */
	public function start_timer( $url, $headers = array() ) {
		$request_id                          = md5( $url . serialize( $headers ) );
		$this->request_timers[ $request_id ] = microtime( true );
	}

	/**
	 * End timing for HTTP request performance monitoring.
	 *
	 * @param string $url Request URL.
	 * @param array  $headers Request headers.
	 */
	public function end_timer( $url, $headers = array() ) {
		$request_id = time();
		if ( isset( $this->request_timers[ $request_id ] ) ) {
			$duration = microtime( true ) - $this->request_timers[ $request_id ];
			unset( $this->request_timers[ $request_id ] );

			if ( $duration > 2.0 ) {
				$domain = wp_parse_url( $url, PHP_URL_HOST );
				$log    = new Log( $domain . '-slow-requests.log' );
				$log->write(
					array(
						'URL'       => $url,
						'Duration'  => round( $duration, 3 ) . 's',
						'Timestamp' => current_time( 'mysql' ),
					),
					'WARNING'
				);
			}
		}
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
		if ( \strpos( $url, \site_url() ) !== false ) {
			return;
		}

		$domain = wp_parse_url( $url, PHP_URL_HOST ) ?? 'unknown';

		$sanitized_body    = $parsed_args['body'];
		$sanitized_headers = $parsed_args['headers'];

		$base_data = array(
			'URL'          => $url,
			'method'       => $parsed_args['method'] ?? 'GET',
			'timeout'      => $parsed_args['timeout'] ?? 5,
			'request_data' => array(
				'body' => $sanitized_body,
			),
		);

		if ( is_wp_error( $response ) ) {
			$error_data          = $base_data;
			$error_data['error'] = array(
				'message' => $response->get_error_message(),
				'code'    => $response->get_error_code(),
				'data'    => $response->get_error_data(),
			);
			$log                 = new Log( $domain . '-errors.log' );
			$log->write( $error_data, 'ERROR' );
			return;
		}

		$response_body    = wp_remote_retrieve_body( $response );
		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_headers = wp_remote_retrieve_headers( $response );

		$success_data             = $base_data;
		$success_data['response'] = array(
			'code'    => $response_code,
			'message' => wp_remote_retrieve_response_message( $response ),
			'body'    => json_decode( $response_body ),
		);

		$log_level = 'INFO';
		if ( $response_code >= 400 && $response_code < 500 ) {
			$log_level = 'WARNING';
		} elseif ( $response_code >= 500 ) {
			$log_level = 'ERROR';
		}
		$log = new Log( $domain . '-requests.log' );
		$log->write( $success_data, $log_level );
	}
}
