<?php

namespace DevKabir\WPDebugger;

use Whoops\Run;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
/**
 * Plugin class.
 */
class Plugin {

	/**
	 * The instance of the plugin.
	 *
	 * @var Plugin|null
	 */
	public static $instance = null;

	/**
	 * Returns the instance of this plugin.
	 * Ensures that only one instance is created.
	 *
	 * @return Plugin The instance of this plugin.
	 */
	public static function get_instance(): Plugin {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the error page setup for handling errors using Whoops library.
	 * Sets up different handlers based on the context, such as JsonResponseHandler for AJAX requests
	 * and PrettyPageHandler for other requests. Registers the handlers with Whoops.
	 */
	public function init_error_page() {
		$whoops = new Run();
		if ( wp_doing_ajax() ) {
			$page = new JsonResponseHandler();
		} else {
			$page = new PrettyPageHandler();
			$page->setEditor( 'vscode' );
		}
		$whoops->pushHandler( $page );
		$whoops->register();
		return $this;
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
					'waf' => array(
						'is_active' => false,
					),
				),
		);

		foreach ( $mock_urls as $mock_url => $mock_response ) {
			if ( strpos( $url, $mock_url ) !== false ) {
				if ( isset( $args['method'] ) && strtoupper( $args['method'] ) === 'POST' && isset( $args['body'] ) ) {
					$post_data = wp_parse_args( $args['body'] );
					$transient_key = 'mock_post_data_' . md5( $url . $args['method'] );
					set_transient( $transient_key, $post_data, 60 * 60 ); // Store for 1 hour
				}
				return json_encode(
					array(
						'body' => $mock_response,
						'response' => array(
							'code' => 200,
							'message' => 'OK',
						),
						'headers' => array(),
						'cookies' => array(),
						'http_response' => null,
					)
				);
			}
		}

		return new \WP_Error( '404', 'Interceptor enabled by wp-logger plugin.' );
	}

	/**
	 * Initializes the plugin, enabling the error page and HTTP request interceptor based on constants.
	 *
	 * Checks if the ENABLE_MOCK_HTTP_INTERCEPTOR constant is defined and true, and if so, enables the HTTP
	 * request interceptor. Also checks if the skip_error_page GET parameter is not set, and if so, enables
	 * the error page.
	 */
	public function init() {
		// Check if the plugin should be enabled based on the constant in wp-config.php.
		if ( defined( 'ENABLE_MOCK_HTTP_INTERCEPTOR' ) && ENABLE_MOCK_HTTP_INTERCEPTOR ) {
			add_filter( 'pre_http_request', array( $this, 'intercept_http_requests' ), 10, 3 );
		}
		$skip_error_page = sanitize_text_field( wp_unslash( $_GET['skip_error_page'] ?? '' ) );
		if ( empty( $skip_error_page ) ) {
			$this->init_error_page();
			new Bar();
		}
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
