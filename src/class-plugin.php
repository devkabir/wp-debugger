<?php

namespace DevKabir\WPDebugger;

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
		new Http();
		new Error_Page();
		$this->register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers for the plugin
	 *
	 * @return void
	 */
	private function register_ajax_handlers(): void {
		add_action( 'wp_ajax_wp_debugger_ignore_trigger', array( $this, 'ajax_ignore_trigger' ) );
		add_action( 'wp_ajax_nopriv_wp_debugger_ignore_trigger', array( $this, 'ajax_ignore_trigger' ) );
	}

	/**
	 * AJAX handler for ignoring a trigger point
	 *
	 * @return void
	 */
	public function ajax_ignore_trigger(): void {
		// Get the trigger point data from the request
		$file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
		$line = isset( $_POST['line'] ) ? absint( $_POST['line'] ) : 0;

		if ( empty( $file ) || $line === 0 ) {
			wp_send_json_error(
				array(
					'message' => 'Invalid trigger point data',
				),
				400
			);
		}

		// Add the trigger to the ignored list
		$result = Ignored_Triggers::add_trigger( $file, $line );

		if ( $result ) {
			wp_send_json_success(
				array(
					'message' => 'Trigger point ignored for 24 hours',
					'file'    => $file,
					'line'    => $line,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => 'Failed to ignore trigger point',
				),
				500
			);
		}
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
	 * Determines if the current request expects a JSON response
	 *
	 * @return bool True if request expects JSON, false otherwise
	 */
	public function is_json_request(): bool {
		return ( defined( 'WP_CLI' ) && WP_CLI ) || ( isset( $_SERVER['CONTENT_TYPE'] ) && 'application/json' === $_SERVER['CONTENT_TYPE'] ) || ( isset( $_SERVER['HTTP_ACCEPT'] ) && false !== strpos( wp_unslash( $_SERVER['HTTP_ACCEPT'] ), 'application/json' ) );
	}
}
