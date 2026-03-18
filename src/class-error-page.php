<?php // phpcs:disable Squiz.Commenting, WordPress.Security

namespace DevKabir\WPDebugger;

use Throwable;
use SplFileObject;
use ErrorException;
use RuntimeException;

/**
 * Class ErrorPage
 * Handles error and exception management for WordPress debugging
 *
 * @package DevKabir\WPDebugger
 */
class Error_Page {

	private const SUPERGLOBAL_ITEM_LIMIT = 200;
	private const CONTEXT_LINE_WINDOW    = 5;
	private const MAX_RECURSION_DEPTH    = 10;
	/**
	 * Toggle used to prevent re-entrant handling.
	 *
	 * @var bool
	 */
	private bool $handling = false;

	/**
	 * Constructor - Sets up error handling hooks and configuration
	 */
	public function __construct() {
		if ( $this->should_skip_handling() ) {
			return;
		}

		// Mirror WordPress: respect suppressed errors and catch fatal shutdowns.
		set_error_handler( array( $this, 'errors' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		set_exception_handler( array( $this, 'handle' ) );
		register_shutdown_function( array( $this, 'shutdown_handler' ) );
		error_reporting( - 1 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Converts PHP errors to Exceptions
	 *
	 * This function is the custom error handler for WordPress. It converts PHP
	 * errors into Exceptions, which can then be caught and handled by the
	 * `handle` method.
	 *
	 * @param int $severity The level of the error raised, as an integer.
	 * @param string $message The error message, as a string.
	 * @param string $file The filename that the error was raised in, as a string.
	 * @param int $line The line number the error was raised at, as an integer.
	 */
	public function errors( int $severity, string $message, string $file, int $line ): bool {
		// Skip errors silenced with @ or not fatal.
		if ( ! ( error_reporting() & $severity ) ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions
			return false;
		}

		$this->handle( new ErrorException( $message, 0, $severity, $file, $line ) );

		return true;
	}

	/**
	 * Handles thrown exceptions and errors
	 *
	 * @param Throwable $throwable The exception or error to handle.
	 *
	 * @return void
	 */
	public function handle( Throwable $throwable ): void {
		if ( $this->handling ) {
			return;
		}

		// Check if this trigger point should be ignored
		if ( Ignored_Triggers::should_ignore( $throwable->getFile(), $throwable->getLine() ) ) {
			error_log( // phpcs:ignore
				sprintf(
					'[WP Debugger] Ignored error at %s:%d - %s',
					$throwable->getFile(),
					$throwable->getLine(),
					$throwable->getMessage()
				)
			);
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
	 * Determine if error handling should be skipped for this request.
	 *
	 * Allows opting out via a cookie (set from the UI) or query parameter.
	 *
	 * @return bool
	 */
	private function should_skip_handling(): bool {
		return ! empty( $_COOKIE['wp_debugger_ignore'] );
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
	 *
	 * @return void
	 */
	private function render( Throwable $throwable ): void {
		! headers_sent() && header( 'Content-Type: text/html; charset=utf-8' );
		Template::render( 'page', $this->prepare_error_data( $throwable ) );
	}

	/**
	 * Prepare error data as array for JavaScript
	 *
	 * @param Throwable $throwable The exception to process.
	 *
	 * @return array
	 */
	private function prepare_error_data( Throwable $throwable ): array {
		$trace        = $throwable->getTrace();
		$trigger_file = $throwable->getFile();
		$trigger_line = $throwable->getLine();

		// Only prepend trigger point if it's not already the first frame
		$first_frame = $trace[0] ?? null;
		$first_file  = $first_frame['file'] ?? null;
		$first_line  = $first_frame['line'] ?? null;

		if ( ! $first_frame || $first_file !== $trigger_file || $first_line !== $trigger_line ) {
			$trigger_point = array(
				'file' => $trigger_file,
				'line' => $trigger_line,
			);
			$trace         = array_merge( array( $trigger_point ), $trace );
		} else {
			// If first frame is the trigger point, remove its args since they're error handler params
			unset( $trace[0]['args'] );
		}

		return array(
			'message'      => $throwable->getMessage(),
			'stackTrace'   => $this->format_stack_trace( $trace ),
			'superglobals' => $this->format_superglobals(),
			'triggerPoint' => array(
				'file' => $trigger_file,
				'line' => $trigger_line,
			),
		);
	}

	/**
	 * Format stack trace for JavaScript consumption
	 *
	 * @param array $trace The exception trace.
	 *
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
			if ( ABSPATH && 0 !== strpos( $file_path, ABSPATH ) ) {
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
				'args'      => empty( $args ) ? array() : $this->process_array_value( $args ),
			);
		}

		return $formatted;
	}

	/**
	 * Pull only the lines needed for a code snippet instead of loading the full file.
	 *
	 * @param string $file_path Path to the PHP file.
	 * @param int $line Target line number.
	 *
	 * @return array{startLine:int,endLine:int,snippet:string}
	 */
	private function get_file_snippet( string $file_path, int $line ): array {
		$start_line = max( 0, $line - self::CONTEXT_LINE_WINDOW );
		$end_line   = $line + self::CONTEXT_LINE_WINDOW;

		try {
			$file = new SplFileObject( $file_path, 'r' );
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
		} catch ( RuntimeException $e ) {
			return array(
				'startLine' => $start_line,
				'endLine'   => $start_line,
				'snippet'   => '',
			);
		}
	}

	/**
	 * Normalize file paths - convert to relative paths if within ABSPATH.
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string Normalized path.
	 */
	private function normalize_path( string $path ): string {
		// Only process if it looks like a file path and is within ABSPATH.
		if ( ABSPATH && 0 === strpos( $path, ABSPATH ) ) {
			return substr( $path, strlen( ABSPATH ) );
		}

		return $path;
	}

	/**
	 * Process array values recursively - convert objects and filter/trim arrays.
	 *
	 * @param mixed $data Input data.
	 * @param int $limit Maximum items to keep per level.
	 * @param int $depth Current recursion depth.
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
				$formatted[ $name ] = $this->trim_array( $value );
			}
		}

		// Free memory.
		unset( $superglobals );

		return $formatted;
	}

	/**
	 * Limit array size recursively to avoid duplicating very large superglobals.
	 *
	 * @param array $data Input array.
	 *
	 * @return array
	 */
	private function trim_array( array $data ): array {
		return $this->process_array_value( $data, self::SUPERGLOBAL_ITEM_LIMIT );
	}

	/**
	 * Shutdown handler to catch fatal errors WordPress style.
	 *
	 * @return void
	 */
	public function shutdown_handler(): void {
		$last_error = error_get_last();

		if ( null === $last_error ) {
			return;
		}

		$this->clean_output_buffers();
		$this->handle( new ErrorException( $last_error['message'] ?? 'Fatal error', 0, $last_error['type'] ?? E_ERROR, $last_error['file'] ?? '', $last_error['line'] ?? 0 ) );
	}
}
