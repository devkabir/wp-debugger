<?php

namespace DevKabir\WPDebugger;

use WP_Error;
use Exception;

/**
 * Plugin class.
 */
class Plugin {

	/**
	 * The instance of the plugin.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Initializes the plugin, enabling the error page and HTTP request interceptor based on constants.
	 *
	 * Checks if the ENABLE_MOCK_HTTP_INTERCEPTOR constant is defined and true, and if so, enables the HTTP
	 * request interceptor. Also checks if the skip_error_page GET parameter is not set, and if so, enables
	 * the error page.
	 */
	public function __construct() {
		define( 'SAVEQUERIES', true );
		add_action(
			'init',
			function (): void {
				wp_deregister_script( 'heartbeat' );
			}
		);
		add_action( 'admin_init', array( $this, 'render_settings' ) );
		// Check if the plugin should be enabled based on the constant in wp-config.php.
		if ( defined( 'ENABLE_MOCK_HTTP_INTERCEPTOR' ) && ENABLE_MOCK_HTTP_INTERCEPTOR ) {
			add_filter( 'pre_http_request', array( $this, 'intercept_http_requests' ), 10, 3 );
		}
		$skip_error_page = sanitize_text_field( wp_unslash( $_GET['skip_error_page'] ?? '' ) );
		if ( empty( $skip_error_page ) ) {
			if ( enable_debugger() ) {
				new ErrorPage();
			}
			if ( show_debugbar() ) {
				new DebugBar();
			}
		}
	}

	/**
	 * Registers settings and fields for the debug bar and debugger options in the WordPress general settings page.
	 *
	 * This function adds two settings fields to the general settings page:
	 * - 'Show Debug Bar': A checkbox to toggle the display of the debug bar.
	 * - 'Enable Debugger': A checkbox to enable or disable the debugger.
	 *
	 * @return void
	 */
	public function render_settings(): void {
		register_setting( 'general', 'show_debugbar' );
		register_setting( 'general', 'enable_debugger' );

		add_settings_field(
			'enable_debugger',
			'Enable Debugger',
			'render_settings_fields',
			'general',
			'default',
			array(
				'id'    => 'enable_debugger',
				'label' => 'Enable Debugger',
			)
		);

		add_settings_field(
			'show_debugbar',
			'Show Debug Bar',
			'render_settings_fields',
			'general',
			'default',
			array(
				'id'    => 'show_debugbar',
				'label' => 'Show Debug Bar',
			)
		);
	}

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
	 * Intercepts outgoing HTTP requests and serves mock responses for predefined URLs.
	 * Stores POST request data in a transient for testing purposes.
	 *
	 * @param bool|array $preempt The preemptive response to return if available.
	 * @param array      $args    Array of HTTP request arguments, including method and body.
	 * @param string     $url     The URL of the outgoing HTTP request.
	 *
	 * @return array|bool|string|\WP_Error Mock response or false to allow original request.
	 */
	public function intercept_http_requests( $preempt, array $args, string $url ) {
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

		return new WP_Error( '404', 'Interceptor enabled by wp-logger plugin.' );
	}

	/**
	 * Logs a message to a specified directory.
	 *
	 * @param mixed  $message The message to be logged.
	 * @param bool   $trace   Whether to log the backtrace.
	 * @param string $dir     The directory where the log file will be written.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function log( $message, bool $trace = false, string $dir = WP_CONTENT_DIR ) {
		$log_file = $dir . '/wp-debugger.log';

		if ( file_exists( $log_file ) && filesize( $log_file ) > 1 * 1024 * 1024 ) {
			file_put_contents( $log_file, '' );
		}

		$message = $this->format_log_message( $message );
		error_log( $message . PHP_EOL, 3, $log_file ); // phpcs:ignore
		if ( $trace ) {
			$this->throw_exception();
		}
	}

	/**
	 * Formats a message with the current timestamp for logging.
	 *
	 * @param mixed $message The message to be formatted.
	 *
	 * @return string The formatted message with the timestamp.
	 */
	public function format_log_message( $message ): string {
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

	/**
	 * Throws an exception to trigger the error page.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function throw_exception() {
		throw new Exception( 'Debugger initialized', 1 );
	}
}
