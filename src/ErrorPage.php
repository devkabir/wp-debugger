<?php

namespace DevKabir\WPDebugger;

use Throwable;
/**
 * Class ErrorPage
 * Handles error and exception management for WordPress debugging
 *
 * @package DevKabir\WPDebugger
 */
class ErrorPage {

	/**
	 * Constructor - Sets up error handling hooks and configuration
	 */
	public function __construct() {
		add_filter( 'wp_die_handler', array( $this, 'handle_shutdown' ) );
		set_exception_handler( array( $this, 'handle' ) );
		error_reporting( -1 );
	}
	/**
	 * Handles thrown exceptions and errors
	 *
	 * @param Throwable $throwable The exception or error to handle
	 * @return void
	 */
	public function handle( Throwable $throwable ): void {
		if ( $this->isJsonRequest() ) {
			$this->jsonHandler( $throwable );
		} else {
			$this->render( $throwable );
		}

		die;
	}
	/**
	 * Determines if the current request expects a JSON response
	 *
	 * @return bool True if request expects JSON, false otherwise
	 */
	private function isJsonRequest(): bool {
		return ( isset( $_SERVER['CONTENT_TYPE'] ) && $_SERVER['CONTENT_TYPE'] === 'application/json' ) || ( isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER['HTTP_ACCEPT'], 'application/json' ) !== false );
	}

	/**
	 * Handles exceptions by outputting them in JSON format
	 *
	 * @param Throwable $throwable The exception to handle
	 * @return void
	 */
	public function jsonHandler( Throwable $throwable ): void {
		echo json_encode(
			array(
				'message'  => $throwable->getMessage(),
				'file'     => $throwable->getFile(),
				'line'     => $throwable->getLine(),
				'trace'    => $throwable->getTrace(),
				'previous' => $throwable->getPrevious(),
			),
			JSON_PRETTY_PRINT
		);
	}

	/**
	 * Renders the exception in HTML format using a template
	 *
	 * @param Throwable $throwable The exception to render
	 * @return void
	 */
	private function render( Throwable $throwable ): void {
		$layout    = Template::get_layout();
		$data      = array(
			'{{exception_message}}' => htmlspecialchars( $throwable->getMessage() ),
			'{{code_snippets}}'     => $this->generate_code_snippets( $throwable->getTrace() ),
			'{{superglobals}}'      => $this->compile_globals(),
		);
		$exception = Template::get_part( 'exception' );
		$exception = Template::compile( $data, $exception );
		$output    = Template::compile( array( '{{content}}' => $exception ), $layout );
		http_response_code( 500 );
		echo $output;
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

			$file_path    = $frame['file'];
			$line         = $frame['line'];
			$editor       = "vscode://file/$file_path:$line";
			$file_content = wp_remote_get( $file_path ) ?? '';
			$lines        = explode( "\n", $file_content );
			$start_line   = max( 0, $frame['line'] - 5 );
			$end_line     = min( count( $lines ), $frame['line'] + 5 );
			$snippet      = implode( "\n", array_slice( $lines, $start_line, $end_line - $start_line ) );

			$snippet_placeholders = array(
				'{{open}}'         => $index ? '' : 'open',
				'{{even}}'         => $index % 2 ? '' : 'bg-gray-200',
				'{{editor_link}}'  => htmlspecialchars( $editor ),
				'{{file_path}}'    => htmlspecialchars( $file_path ),
				'{{start_line}}'   => $start_line,
				'{{end_line}}'     => $end_line,
				'{{line_number}}'  => $frame['line'],
				'{{code_snippet}}' => htmlspecialchars( $snippet ),
				'{{args}}'         => self::dump( $frame['args'] ),
			);

			$code_snippets .= Template::compile( $snippet_placeholders, $code_snippet_template );
		}

		return $code_snippets;
	}
	/**
	 * Creates a formatted dump of variable data
	 *
	 * @param mixed $data The data to dump
	 * @return string HTML formatted variable dump
	 */
	public static function dump( $data ): string {
		return Template::compile( array( '{{content}}' => var_export( $data, true ) ), Template::get_part( 'dump' ) );
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
			$data = array(
				'{{open}}'  => $index ? '' : 'open',
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
		echo self::dump( error_get_last() );
		die;
	}
}
