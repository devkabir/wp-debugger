<?php

namespace DevKabir\WPDebugger;

use Exception;
use RuntimeException;
use WP_Error;

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
		// new Debug_Bar();
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
	 * Throws an exception to trigger the error page.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function throw_exception(): void {
		throw new RuntimeException( 'Debugger initialized', 1 );
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
