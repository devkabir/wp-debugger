<?php // phpcs:disable Squiz.Commenting, WordPress.Security

namespace DevKabir\WPDebugger;

use Throwable;
use LimitIterator;
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
	private const CONTEXT_LINE_COUNT     = 40;
	private const MAX_RECURSION_DEPTH    = 10;
	private const FATAL_ERROR_TYPES      = E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR;

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
		// Opt out via a cookie set from the UI.
		if ( ! empty( $_COOKIE['wp_debugger_ignore'] ) ) {
			return;
		}

		// Mirror WordPress: respect suppressed errors and catch fatal shutdowns.
		set_error_handler( array( $this, 'errors' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		set_exception_handler( array( $this, 'handle' ) );
		register_shutdown_function( array( $this, 'shutdown_handler' ) );
		error_reporting( self::FATAL_ERROR_TYPES ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Converts fatal PHP errors to an ErrorException and renders it.
	 *
	 * @param int $severity The level of the error raised, as an integer.
	 * @param string $message The error message, as a string.
	 * @param string $file The filename that the error was raised in, as a string.
	 * @param int $line The line number the error was raised at, as an integer.
	 */
	public function errors( int $severity, string $message, string $file, int $line ): bool {
		// Skip errors silenced with @ or not fatal.
		if ( ! ( error_reporting() & self::FATAL_ERROR_TYPES & $severity ) ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions
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

		$this->handling = true;

		// Drop partial output so the error page mirrors core behaviour.
		while ( ob_get_level() ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions.ObFunctions
			ob_end_clean(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.ObFunctions
		}

		if ( ! headers_sent() ) {
			function_exists( 'status_header' ) ? status_header( 500 ) : header( 'HTTP/1.1 500 Internal Server Error' ); // phpcs:ignore WordPress.Security.SafeRedirect.phperror, WordPress.PHP.DevelopmentFunctions.error_reporting_error_handling
		}

		if ( Plugin::get_instance()->is_json_request() ) {
			$this->json_handler( $throwable );
			error_log( $throwable->getMessage() ); // phpcs:ignore
		} else {
			! headers_sent() && header( 'Content-Type: text/html; charset=utf-8' );
			Template::render( 'page', $this->prepare_error_data( $throwable ) );
		}

		die;
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
				'trace'    => debugger_trace_summary( $throwable->getTrace() ),
				// A Throwable has no public props, so encoding it directly gives "{}".
				'previous' => $throwable->getPrevious() ? $throwable->getPrevious()->getMessage() : null,
			),
			JSON_PRETTY_PRINT
		);
	}

	/**
	 * Prepare error data as array for JavaScript
	 *
	 * @param Throwable $throwable The exception to process.
	 *
	 * @return array
	 */
	private function prepare_error_data( Throwable $throwable ): array {
		$trace = $throwable->getTrace();
		$file  = $throwable->getFile();
		$line  = $throwable->getLine();

		if ( ( $trace[0]['file'] ?? null ) === $file && ( $trace[0]['line'] ?? null ) === $line ) {
			// First frame is the trigger point; its args are error handler params.
			unset( $trace[0]['args'] );
		} else {
			array_unshift( $trace, compact( 'file', 'line' ) );
		}

		$superglobals = array_filter(
			array(
				'$_REQUEST' => $_REQUEST,
				'$_SERVER'  => $_SERVER,
				'$_FILES'   => $_FILES,
				'$_COOKIE'  => $_COOKIE,
				'$_SESSION' => $_SESSION ?? array(),
			)
		);

		return array(
			'message'      => $throwable->getMessage(),
			'type'         => $this->get_error_type( $throwable ),
			'stackTrace'   => $this->format_stack_trace( $trace ),
			'superglobals' => array_map( fn( $value ) => $this->process_array_value( $value, self::SUPERGLOBAL_ITEM_LIMIT ), $superglobals ),
			'triggerPoint' => compact( 'file', 'line' ),
		);
	}

	/**
	 * Get the human-readable error type/severity name
	 *
	 * @param Throwable $throwable The throwable object.
	 * @return string Error type name
	 */
	private function get_error_type( Throwable $throwable ): string {
		// Only fatal severities reach the page, so warnings/notices need no label.
		if ( $throwable instanceof ErrorException ) {
			return array(
				E_PARSE             => 'Parse Error',
				E_RECOVERABLE_ERROR => 'Recoverable Error',
			)[ $throwable->getSeverity() ] ?? 'Fatal Error';
		}

		$parts = explode( '\\', $throwable::class );
		return end( $parts );
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
			// Skip internal frames and files outside the WordPress installation.
			if ( ! isset( $frame['file'] ) || ! str_starts_with( $frame['file'], ABSPATH ) || ! is_readable( $frame['file'] ) ) {
				continue;
			}

			$line = $frame['line'] ?? 0;

			$formatted[] = array(
				'file'     => $this->normalize_path( $frame['file'] ),
				'filePath' => $frame['file'],
				'line'     => $line,
				'class'    => $frame['class'] ?? '',
				'type'     => $frame['type'] ?? '',
				'function' => $frame['function'] ?? '',
				'args'     => $this->process_array_value( $frame['args'] ?? array() ),
			) + $this->get_file_snippet( $frame['file'], $line );
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
		try {
			$file = new SplFileObject( $file_path, 'r' );
			$file->seek( PHP_INT_MAX );
			$total = $file->key() + 1;

			// Centre a fixed-size window on the line, sliding it back inside the file at either end.
			$count = min( self::CONTEXT_LINE_COUNT, $total );
			$start = max( 1, min( $line - intdiv( $count - 1, 2 ), $total - $count + 1 ) );
			$lines = iterator_to_array( new LimitIterator( $file, $start - 1, $count ), false );

			return array(
				'startLine' => $start,
				'endLine'   => $start + count( $lines ) - 1,
				'snippet'   => rtrim( implode( '', $lines ), "\n" ),
			);
		} catch ( RuntimeException ) {
			return array(
				'startLine' => max( 1, $line ),
				'endLine'   => max( 1, $line ),
				'snippet'   => '',
			);
		}
	}

	/**
	 * Convert a path inside ABSPATH to a relative one.
	 *
	 * @param string $path The path to normalize.
	 *
	 * @return string Normalized path.
	 */
	private function normalize_path( string $path ): string {
		return str_starts_with( $path, ABSPATH ) ? substr( $path, strlen( ABSPATH ) ) : $path;
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
		if ( is_object( $data ) ) {
			return $data::class;
		}

		if ( $depth >= self::MAX_RECURSION_DEPTH ) {
			return '[Max depth reached]';
		}

		if ( is_string( $data ) ) {
			// Truncate very long strings to save memory.
			return strlen( $data ) > 1000 ? substr( $data, 0, 1000 ) . '... [truncated]' : $this->normalize_path( $data );
		}

		if ( ! is_array( $data ) ) {
			return $data;
		}

		$count = count( $data );

		if ( $limit > 0 && $count > $limit ) {
			$data                = array_slice( $data, 0, $limit, true );
			$data['__truncated'] = sprintf( 'trimmed to first %d of %d entries', $limit, $count );
		}

		foreach ( $data as $key => $value ) {
			$data[ $key ] = $this->process_array_value( $value, $limit, $depth + 1 );
		}

		return $data;
	}

	/**
	 * Shutdown handler to catch fatal errors WordPress style.
	 *
	 * @return void
	 */
	public function shutdown_handler(): void {
		$error = error_get_last();

		if ( null === $error || ! ( self::FATAL_ERROR_TYPES & $error['type'] ) ) {
			return;
		}

		$this->handle( new ErrorException( $error['message'], 0, $error['type'], $error['file'], $error['line'] ) );
	}
}
