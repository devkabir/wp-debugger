<?php // phpcs:disable Squiz.Commenting, WordPress.Security

namespace DevKabir\WPDebugger;

use ErrorException;
use Throwable;
/**
 * Class ErrorPage
 * Handles error and exception management for WordPress debugging
 *
 * @package DevKabir\WPDebugger
 */
class Error_Page {

	/**
	 * Toggle used to prevent re-entrant handling.
	 *
	 * @var bool
	 */
	private $handling = false;

	/**
	 * Cached ABSPATH value to avoid repeated lookups.
	 *
	 * @var string
	 */
	private $abspath = '';

	private const FATAL_ERRORS = array(
		E_ERROR,
		E_PARSE,
		E_USER_ERROR,
		E_COMPILE_ERROR,
		E_CORE_ERROR,
		E_RECOVERABLE_ERROR,
	);

	private const SUPERGLOBAL_ITEM_LIMIT = 200;
	private const CONTEXT_LINE_WINDOW    = 5;
	private const MAX_STACK_TRACE_DEPTH  = 50;
	private const MAX_RECURSION_DEPTH    = 10;
	/**
	 * Constructor - Sets up error handling hooks and configuration
	 */
	public function __construct() {
		// Cache ABSPATH once to avoid repeated lookups.
		$this->abspath = defined( 'ABSPATH' ) ? ABSPATH : '';

		// Mirror WordPress: respect suppressed errors and catch fatal shutdowns.
		set_error_handler( array( $this, 'errors' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		set_exception_handler( array( $this, 'handle' ) );
		register_shutdown_function( array( $this, 'shutdown_handler' ) );
		error_reporting( -1 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Converts PHP errors to Exceptions
	 *
	 * This function is the custom error handler for WordPress. It converts PHP
	 * errors into Exceptions, which can then be caught and handled by the
	 * `handle` method.
	 *
	 * @param int    $errno   The level of the error raised, as an integer.
	 * @param string $errstr  The error message, as a string.
	 * @param string $errfile The filename that the error was raised in, as a string.
	 * @param int    $errline The line number the error was raised at, as an integer.
	 *
	 * @throws \ErrorException The converted error as an Exception.
	 */
	public function errors( $errno, $errstr, $errfile, $errline ) {
		// Skip errors silenced with @ or not fatal.
		if ( ! ( error_reporting() & $errno ) || ! $this->should_handle_error( $errno ) ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions
			return false;
		}

		$this->handle( new ErrorException( $errstr, 0, $errno, $errfile, $errline ) );
		return true;
	}

	/**
	 * Shutdown handler to catch fatal errors WordPress style.
	 *
	 * @return void
	 */
	public function shutdown_handler(): void {
		$last_error = error_get_last();

		if ( null === $last_error || ! $this->should_handle_error( $last_error['type'] ?? 0 ) ) {
			return;
		}

		$this->clean_output_buffers();
		$this->handle(
			new ErrorException(
				$last_error['message'] ?? 'Fatal error',
				0,
				$last_error['type'] ?? E_ERROR,
				$last_error['file'] ?? '',
				$last_error['line'] ?? 0
			)
		);
	}

	/**
	 * Determine if an error type should be handled.
	 *
	 * @param int $type Error type.
	 * @return bool
	 */
	private function should_handle_error( int $type ): bool {
		return in_array( $type, self::FATAL_ERRORS, true );
	}

	/**
	 * Handles thrown exceptions and errors
	 *
	 * @param Throwable $throwable The exception or error to handle.
	 * @return void
	 */
	public function handle( Throwable $throwable ): void {
		if ( $this->handling ) {
			return;
		}

		$this->handling = true;
		$this->clean_output_buffers();
		$this->send_http_status();

		if ( Plugin::get_instance()->is_json_request() ) {
			$this->json_handler( $throwable );
			error_log( $throwable->getMessage() ); // phpcs:ignore
		} else {
			$this->render( $throwable );
		}

		die;
	}

	/**
	 * Clean any partial output so the error page mirrors core behaviour.
	 *
	 * @return void
	 */
	private function clean_output_buffers(): void {
		while ( ob_get_level() ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.ObFunctions
			ob_end_clean(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.ObFunctions
		}
	}

	/**
	 * Send a 500 status code where headers permit.
	 *
	 * @return void
	 */
	private function send_http_status(): void {
		if ( ! headers_sent() ) {
			function_exists( 'status_header' ) ? status_header( 500 ) : header( 'HTTP/1.1 500 Internal Server Error' ); // phpcs:ignore WordPress.Security.SafeRedirect.phperror, WordPress.PHP.DevelopmentFunctions.error_reporting_error_handling
		}
	}

	/**
	 * Handles exceptions by outputting them in JSON format
	 *
	 * @param Throwable $throwable The exception to handle.
	 *
	 * @return void
	 */
	public function json_handler( Throwable $throwable ): void {
		! headers_sent() && header( 'Content-Type: application/json; charset=utf-8' ); // phpcs:ignore WordPress.Security.SafeRedirect.phperror

		echo json_encode( // phpcs:ignore WordPress.WP.AlternativeFunctions
			array(
				'message'  => $throwable->getMessage(),
				'file'     => $throwable->getFile(),
				'line'     => $throwable->getLine(),
				'trace'    => format_stack_trace( $throwable->getTrace() ),
				'previous' => $throwable->getPrevious(),
			),
			JSON_PRETTY_PRINT
		);
	}

	/**
	 * Renders the exception in HTML format using a template
	 *
	 * @param Throwable $throwable The exception to render.
	 * @return void
	 */
	private function render( Throwable $throwable ): void {
		// Register and enqueue assets
		$this->enqueue_assets();

		// Prepare error data to pass to JavaScript
		$error_data = $this->prepare_error_data( $throwable );

		// Localize script with error data
		wp_localize_script( 'wp-debugger-app', 'wpDebuggerData', $error_data );

		// Output minimal HTML that JavaScript will populate
		$this->output_html();
	}

	/**
	 * Register and enqueue WordPress assets
	 *
	 * @return void
	 */
	private function enqueue_assets(): void {
		// Register styles
		wp_enqueue_style(
			'wp-debugger-page',
			Template::get_asset( 'css/page.css' ),
			array(),
			time()
		);

		// Register script
		wp_enqueue_script(
			'wp-debugger-app',
			Template::get_asset( 'js/page.js' ),
			array(),
			time(),
			array( 'in_footer' => false )
		);
	}

	/**
	 * Prepare error data as array for JavaScript
	 *
	 * @param Throwable $throwable The exception to process.
	 * @return array
	 */
	private function prepare_error_data( Throwable $throwable ): array {
		// Limit stack trace depth to prevent memory issues.
		$trace = array_slice( $throwable->getTrace(), 0, self::MAX_STACK_TRACE_DEPTH );

		// Prepend trigger point.
		array_unshift(
			$trace,
			array(
				'file' => $throwable->getFile(),
				'line' => $throwable->getLine(),
			)
		);

		return array(
			'message'      => $throwable->getMessage(),
			'stackTrace'   => $this->format_stack_trace( $trace ),
			'superglobals' => $this->format_superglobals(),
		);
	}

	/**
	 * Format stack trace for JavaScript consumption
	 *
	 * @param array $trace The exception trace.
	 * @return array
	 */
	private function format_stack_trace( array $trace ): array {
		$formatted = array();

		foreach ( $trace as $frame ) {
			if ( ! isset( $frame['file'] ) || ! is_readable( $frame['file'] ) ) {
				continue;
			}

			$file_path = $frame['file'];

			// Skip files outside WordPress installation.
			if ( $this->abspath && 0 !== strpos( $file_path, $this->abspath ) ) {
				continue;
			}

			$line = $frame['line'] ?? 0;
			$args = $frame['args'] ?? array();

			$snippet_data = $this->get_file_snippet( $file_path, $line );

			$formatted[] = array(
				'file'      => $this->normalize_path( $file_path ),
				'filePath'  => $file_path,
				'line'      => $line,
				'startLine' => $snippet_data['startLine'],
				'endLine'   => $snippet_data['endLine'],
				'snippet'   => $snippet_data['snippet'],
				'args'      => empty( $args ) ? array() : $this->process_array_value( $args, 0, 0 ),
			);
		}

		return $formatted;
	}

	/**
	 * Pull only the lines needed for a code snippet instead of loading the full file.
	 *
	 * @param string $file_path Path to the PHP file.
	 * @param int    $line      Target line number.
	 *
	 * @return array{startLine:int,endLine:int,snippet:string}
	 */
	private function get_file_snippet( string $file_path, int $line ): array {
		$start_line = max( 0, $line - self::CONTEXT_LINE_WINDOW );
		$end_line   = $line + self::CONTEXT_LINE_WINDOW;

		try {
			$file = new \SplFileObject( $file_path, 'r' );
			$file->seek( $start_line );

			$snippet      = '';
			$current_line = $start_line;

			while ( ! $file->eof() && $current_line <= $end_line ) {
				$snippet .= rtrim( (string) $file->current(), "\n" ) . "\n";
				$file->next();
				++$current_line;
			}

			// Unset file handle to free memory immediately.
			unset( $file );

			return array(
				'startLine' => $start_line,
				'endLine'   => $current_line - 1,
				'snippet'   => rtrim( $snippet, "\n" ),
			);
		} catch ( \RuntimeException $e ) {
			return array(
				'startLine' => $start_line,
				'endLine'   => $start_line,
				'snippet'   => '',
			);
		}
	}

	/**
	 * Format superglobals for JavaScript consumption
	 *
	 * @return array
	 */
	private function format_superglobals(): array {
		$formatted = array();

		$superglobals = array(
			'$_REQUEST' => $_REQUEST,
			'$_SERVER'  => $_SERVER,
			'$_FILES'   => $_FILES,
			'$_COOKIE'  => $_COOKIE,
			'$_SESSION' => $_SESSION ?? array(),
		);

		foreach ( $superglobals as $name => $value ) {
			if ( ! empty( $value ) ) {
				$formatted[ $name ] = $this->trim_array( $value, self::SUPERGLOBAL_ITEM_LIMIT );
			}
		}

		// Free memory.
		unset( $superglobals );

		return $formatted;
	}

	/**
	 * Print plugin assets without pulling in theme styles/scripts.
	 *
	 * @return void
	 */
	private function print_assets(): void {
		function_exists( 'wp_print_styles' ) && wp_print_styles( array( 'wp-debugger-page' ) );
		function_exists( 'wp_print_scripts' ) && wp_print_scripts( array( 'wp-debugger-app' ) );
	}

	/**
	 * Output minimal HTML shell
	 *
	 * @return void
	 */
	private function output_html(): void {
		! headers_sent() && header( 'Content-Type: text/html; charset=utf-8' );
		?>
		<!doctype html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>WP Debugger</title>
			<?php $this->print_assets(); ?>
		</head>
		<body>
			<div id="wp-debugger"></div>
		</body>
		</html>
		<?php
	}

	/**
	 * Process array values recursively - convert objects and filter/trim arrays.
	 *
	 * @param mixed $data  Input data.
	 * @param int   $limit Maximum items to keep per level.
	 * @param int   $depth Current recursion depth.
	 *
	 * @return mixed Processed data.
	 */
	private function process_array_value( $data, int $limit = 0, int $depth = 0 ) {
		// Prevent excessive recursion depth.
		if ( $depth >= self::MAX_RECURSION_DEPTH ) {
			return is_object( $data ) ? get_class( $data ) : '[Max depth reached]';
		}

		if ( is_object( $data ) ) {
			return get_class( $data );
		}

		if ( is_string( $data ) ) {
			// Truncate very long strings to save memory.
			return strlen( $data ) > 1000 ? substr( $data, 0, 1000 ) . '... [truncated]' : $this->normalize_path( $data );
		}

		if ( ! is_array( $data ) ) {
			return $data;
		}

		$count = count( $data );

		// Trim if limit is set.
		if ( $limit > 0 && $count > $limit ) {
			$data                = array_slice( $data, 0, $limit, true );
			$data['__truncated'] = sprintf( 'trimmed to first %d of %d entries', $limit, $count );
		}

		// Process nested values.
		foreach ( $data as $key => $value ) {
			$processed = $this->process_array_value( $value, $limit, $depth + 1 );

			if ( 0 === $limit && is_array( $processed ) && empty( $processed ) ) {
				unset( $data[ $key ] );
			} else {
				$data[ $key ] = $processed;
			}
		}

		return 0 === $limit ? array_filter( $data ) : $data;
	}

	/**
	 * Normalize file paths - convert to relative paths if within ABSPATH.
	 *
	 * @param string $path The path to normalize.
	 * @return string Normalized path.
	 */
	private function normalize_path( string $path ): string {
		// Only process if it looks like a file path and is within ABSPATH.
		if ( $this->abspath && 0 === strpos( $path, $this->abspath ) ) {
			return substr( $path, strlen( $this->abspath ) );
		}

		return $path;
	}

	/**
	 * Limit array size recursively to avoid duplicating very large superglobals.
	 *
	 * @param array $data  Input array.
	 * @param int   $limit Maximum items to keep per level.
	 *
	 * @return array
	 */
	private function trim_array( array $data, int $limit ): array {
		return $this->process_array_value( $data, $limit );
	}
}
