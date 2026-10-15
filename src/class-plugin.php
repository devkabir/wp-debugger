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
		new Query_Detector();
	}

	/**
	 * Install the must-use plugin loader if it is not already present.
	 *
	 * @return void
	 */
	public static function install_mu_loader(): void {
		$source      = dirname( __DIR__ ) . '/stubs/mu-loader.stub';
		$mu_dir      = self::get_mu_plugins_dir();
		$destination = self::get_mu_loader_path();

		if ( ! file_exists( $source ) ) {
			return;
		}

		$wp_filesystem = self::get_wp_filesystem();
		if ( ! $wp_filesystem ) {
			return;
		}

		if ( $wp_filesystem->exists( $destination ) ) {
			return;
		}

		if ( ! $wp_filesystem->is_dir( $mu_dir ) ) {
			$wp_filesystem->mkdir( $mu_dir, defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : 0755 );
		}

		if ( ! $wp_filesystem->is_dir( $mu_dir ) || $wp_filesystem->exists( $destination ) ) {
			return;
		}

		$wp_filesystem->copy( $source, $destination, false, defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644 );
	}

	/**
	 * Remove the must-use plugin loader generated on activation.
	 *
	 * @return void
	 */
	public static function uninstall_mu_loader(): void {
		$wp_filesystem = self::get_wp_filesystem();

		if ( ! $wp_filesystem ) {
			return;
		}

		$source      = dirname( __DIR__ ) . '/stubs/mu-loader.stub';
		$destination = self::get_mu_loader_path();

		if (
			$wp_filesystem->exists( $destination )
			&& file_exists( $source )
			&& $wp_filesystem->get_contents( $destination ) === file_get_contents( $source ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		) {
			$wp_filesystem->delete( $destination );
		}
	}

	/**
	 * Get the absolute path to the generated MU loader.
	 *
	 * @return string
	 */
	private static function get_mu_loader_path(): string {
		return self::get_mu_plugins_dir() . '/000-wp-debugger.php';
	}

	/**
	 * Get the absolute path to the must-use plugins directory.
	 *
	 * @return string
	 */
	private static function get_mu_plugins_dir(): string {
		$content_dir = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : dirname( __DIR__, 3 );

		return rtrim( $content_dir, '/\\' ) . '/mu-plugins';
	}

	/**
	 * Initialize and return the WordPress filesystem API.
	 *
	 * @return \WP_Filesystem_Base|null
	 */
	private static function get_wp_filesystem() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			return null;
		}

		global $wp_filesystem;

		return $wp_filesystem ?? null;
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
		if ( defined( 'PEST_RUNNING' ) || defined( 'PHPUNIT_COMPOSER_INSTALL' ) || class_exists( 'Pest\TestSuite', false ) ) {
			return true;
		}
		return ( defined( 'WP_CLI' ) && WP_CLI ) || ( isset( $_SERVER['CONTENT_TYPE'] ) && 'application/json' === $_SERVER['CONTENT_TYPE'] ) || ( isset( $_SERVER['HTTP_ACCEPT'] ) && false !== strpos( wp_unslash( $_SERVER['HTTP_ACCEPT'] ), 'application/json' ) );
	}
}
