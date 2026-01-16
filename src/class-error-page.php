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
	 * Constructor - Sets up error handling hooks and configuration
	 */
	public function __construct() {
		// Mirror WordPress: respect suppressed errors and catch fatal shutdowns.
		set_error_handler( array( $this, 'errors' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		set_exception_handler( array( $this, 'handle' ) );
		register_shutdown_function( array( $this, 'shutdown_handler' ) );
		error_reporting( -1 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Toggle used to prevent re-entrant handling.
	 *
	 * @var bool
	 */
	private $handling = false;

	private const FATAL_ERRORS = array(
		E_ERROR,
		E_PARSE,
		E_USER_ERROR,
		E_COMPILE_ERROR,
		E_CORE_ERROR,
		E_RECOVERABLE_ERROR,
	);

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
		// Skip errors silenced with @ just like core.
		if ( ! ( error_reporting() & $errno ) ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions
			return false;
		}

		if ( ! $this->should_handle_error( $errno ) ) {
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
		if ( function_exists( 'status_header' ) ) {
			status_header( 500 );
		} elseif ( ! headers_sent() ) {
			header( 'HTTP/1.1 500 Internal Server Error' ); // phpcs:ignore WordPress.Security.SafeRedirect.phperror
		}

		if ( function_exists( 'http_response_code' ) ) {
			http_response_code( 500 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_reporting_error_handling
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
		if ( ! headers_sent() ) {
			header( 'Content-Type: application/json; charset=utf-8' ); // phpcs:ignore WordPress.Security.SafeRedirect.phperror
		}

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
			'1.0.0'
		);

		wp_enqueue_style(
			'wp-debugger-prism',
			Template::get_asset( 'css/prism.css' ),
			array(),
			'1.0.0'
		);

		// Register script
		wp_enqueue_script(
			'wp-debugger-app',
			Template::get_asset( 'js/app.js' ),
			array(),
			'1.0.0',
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
		$trace         = $throwable->getTrace();
		$trigger_point = array(
			'file' => $throwable->getFile(),
			'line' => $throwable->getLine(),
		);
		$trace         = array_merge( array( $trigger_point ), $trace );

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

			$file_path    = $frame['file'];
			$line         = $frame['line'] ?? 0;
			$file_content = file_get_contents( $file_path ) ?? '';
			$lines        = explode( "\n", $file_content );
			$start_line   = max( 0, $line - 5 );
			$end_line     = min( count( $lines ), $line + 5 );
			$snippet      = implode( "\n", array_slice( $lines, $start_line, $end_line - $start_line ) );

			$formatted[] = array(
				'file'      => $file_path,
				'line'      => $line,
				'startLine' => $start_line,
				'endLine'   => $end_line,
				'snippet'   => $snippet,
				'args'      => $this->filter_array_recursive( $frame['args'] ?? array() ),
			);
		}

		return $formatted;
	}

	/**
	 * Format superglobals for JavaScript consumption
	 *
	 * @return array
	 */
	private function format_superglobals(): array {
		$super_globals = array(
			'$_REQUEST' => $_REQUEST,
			'$_SERVER'  => $_SERVER,
			'$_FILES'   => $_FILES,
			'$_COOKIE'  => $_COOKIE,
			'$_SESSION' => $_SESSION ?? array(),
			'headers'   => headers_list(),
		);

		$formatted = array();
		foreach ( $super_globals as $name => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				ksort( $value );
			}
			$formatted[ $name ] = $value;
		}

		return $formatted;
	}

	/**
	 * Output minimal HTML shell
	 *
	 * @return void
	 */
	private function output_html(): void {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=utf-8' );
		}
		?>
		<!doctype html>
		<html lang="en">
		<head>
			<meta charset="UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>WP Debugger</title>
			<?php wp_head(); ?>
		</head>
		<body>
			<div id="app"></div>
			<?php wp_footer(); ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Generates HTML code snippets from the exception trace
	 *
	 * @param array $trace The exception trace
	 * @return string HTML formatted code snippets
	 */
	private function generate_code_snippets( array $trace ): string {
		$code_snippet_template = Template::get_part( 'code' );
		$code_snippets         = '';
		foreach ( $trace as $index => $frame ) {
			if ( ! isset( $frame['file'] ) || ! is_readable( $frame['file'] ) ) {
				continue;
			}

			$file_path            = $frame['file'];
			$line                 = $frame['line'];
			$editor               = "vscode://file/$file_path:$line";
			$file_content         = file_get_contents( $file_path ) ?? '';
			$lines                = explode( "\n", $file_content );
			$start_line           = max( 0, $frame['line'] - 5 );
			$end_line             = min( count( $lines ), $frame['line'] + 5 );
			$snippet              = implode( "\n", array_slice( $lines, $start_line, $end_line - $start_line ) );
			$args                 = $this->filter_array_recursive( $frame['args'] ?? array() );
			$snippet_placeholders = array(
				'{{editor_link}}'  => htmlspecialchars( $editor ),
				'{{file_path}}'    => htmlspecialchars( $file_path ),
				'{{start_line}}'   => $start_line,
				'{{end_line}}'     => $end_line,
				'{{line_number}}'  => $frame['line'],
				'{{code_snippet}}' => htmlspecialchars( $snippet ),
				'{{args}}'         => empty( $args ) ? '' : sprintf( "<h1 class='text-xl font-semibold'>Arguments</h1>%s", self::dump_args( $frame['args'] ) ),
			);

			$code_snippets .= Template::compile( $snippet_placeholders, $code_snippet_template );
		}

		return $code_snippets;
	}

	private function dump_args( array $data ) {
		$template = '';
		foreach ( $data as $index => $value ) {
			$template .= self::dump( $value );
		}
		return $template;
	}

	/**
	 * Creates a formatted dump of variable data
	 *
	 * @param mixed $variable The data to dump
	 *
	 * @return string HTML formatted variable dump
	 */
	public static function dump( $variable ): string {
		$data = debugger_format_variable( $variable );

		return Template::compile(
			array( '{{content}}' => $data ),
			Template::get_part( 'dump' )
		);
	}

	/**
	 * Compiles super globals into HTML format
	 *
	 * @return string HTML formatted super globals
	 */
	private function compile_globals(): string {
		$super_globals = array(
			'$_REQUEST' => $_REQUEST,
			'$_SERVER'  => $_SERVER,
			'$_FILES'   => $_FILES,
			'$_COOKIE'  => $_COOKIE,
			'$_SESSION' => $_SESSION ?? array(),
			'headers'   => headers_list(),
		);

		$template = Template::get_part( 'variable' );
		$output   = '';
		$index    = 0;
		foreach ( $super_globals as $name => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			ksort( $value );
			$data = array(
				'{{open}}'  => 'open',
				'{{name}}'  => $name,
				'{{value}}' => self::dump( $value ),
			);

			++$index;
			$output .= Template::compile( $data, $template );
		}

		return $output;
	}

	/**
	 * Recursively filters an array to remove empty values.
	 *
	 * @param array $data The array to filter.
	 *
	 * @return array The filtered array.
	 */
	private function filter_array_recursive( array $data ): array {
		$filtered_data = array_filter( $data );
		foreach ( $filtered_data as $key => &$value ) {
			if ( is_object( $value ) ) {
				$filtered_data[ $key ] = get_class( $value );
			} elseif ( is_array( $value ) ) {
				$value = $this->filter_array_recursive( $value );
				if ( empty( $value ) ) {
					unset( $filtered_data[ $key ] );
				}
			}
		}
		return $filtered_data;
	}
}
