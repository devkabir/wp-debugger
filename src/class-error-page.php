<?php

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
		set_error_handler( array( $this, 'errors' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		set_exception_handler( array( $this, 'handle' ) );
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
		$this->handle( new ErrorException( $errstr, 0, $errno, $errfile, $errline ) );
	}

	/**
	 * Handles thrown exceptions and errors
	 *
	 * @param Throwable $throwable The exception or error to handle.
	 * @return void
	 */
	public function handle( Throwable $throwable ): void {
		if ( Plugin::get_instance()->is_json_request() ) {
			$this->json_handler( $throwable );
			error_log( $throwable->getMessage() ); // phpcs:ignore
		} else {
			$this->render( $throwable );
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
		if ( $this->should_ignore_exception( $throwable ) ) {
			return;
		}
		$layout        = Template::get_layout();
		$trace         = $throwable->getTrace();
		$trigger_point = array(
			'file' => $throwable->getFile(),
			'line' => $throwable->getLine(),
		);
		$trace         = array_merge( array( $trigger_point ), $trace );
		$data          = array(
			'{{exception_message}}' => htmlspecialchars( $throwable->getMessage() ),
			'{{code_snippets}}'     => $this->generate_code_snippets( $trace ),
			'{{stack_trace_text}}'  => htmlspecialchars( $this->format_stack_trace_text( $trace ) ),
			'{{superglobals}}'      => $this->compile_globals(),
		);
		$exception     = Template::get_part( 'exception' );
		$exception     = Template::compile( $data, $exception );
		$output        = Template::compile( array( '{{content}}' => $exception ), $layout );
		echo $output;
	}

	/**
	 * Determines whether an exception should be ignored by the renderer.
	 *
	 * @param Throwable $throwable The exception to check.
	 * @return bool
	 */
	private function should_ignore_exception( Throwable $throwable ): bool {
		$trace    = $throwable->getMessage();
		$messages = apply_filters(
			'wp_debugger_ignore_exception_messages',
			array(
				'wp_update_plugins',
			)
		);
		$ignored  = false;
		foreach ( $messages as $text ) {
			if ( strpos( $trace, $text ) !== false ) {
				$ignored = true;
				break;
			}
		}

		return $ignored;
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

	/**
	 * Formats the stack trace for copy-to-clipboard output.
	 *
	 * @param array $trace The exception trace.
	 * @return string
	 */
	private function format_stack_trace_text( array $trace ): string {
		$formatted = format_stack_trace( $trace );
		if ( empty( $formatted ) ) {
			return '';
		}

		$lines = array();
		foreach ( $formatted as $location => $frame ) {
			$function = $frame['function'] ?? 'unknown';
			$args     = $frame['args'] ?? false;
			$line     = $location . ' ' . $function;
			if ( ! empty( $args ) ) {
				$line .= ' ' . json_encode( $args );
			}
			$lines[] = $line;
		}

		return implode( "\n", $lines );
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
			'$_POST'    => $_POST,
			'$_GET'     => $_GET,
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
	 * Handles PHP shutdown and displays last error
	 *
	 * @return void
	 */
	public function handle_shutdown(): void {
		if ( empty( error_get_last() ) ) {
			echo self::dump( debug_backtrace() );
		} else {
			echo self::dump( error_get_last() );
		}
		die;
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
