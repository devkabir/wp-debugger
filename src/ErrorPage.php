<?php

namespace DevKabir\WPDebugger;

use Throwable;

class ErrorPage {
	public function __construct() {
		set_exception_handler( array( $this, 'handle' ) );
		ini_set( 'display_errors', 'off' );
		error_reporting( - 1 );
	}

	public function handle( Throwable $throwable ): void {
		if ( $this->isJsonRequest() || wp_doing_ajax() ) {
			$this->jsonHandler( $throwable );
		}

		$this->render( $throwable );
	}

	private function isJsonRequest(): bool {
		return ( isset( $_SERVER['CONTENT_TYPE'] ) && $_SERVER['CONTENT_TYPE'] === 'application/json' ) || ( isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER['HTTP_ACCEPT'], 'application/json' ) !== false );
	}

	/**
	 * @param \Throwable $throwable
	 *
	 * @return void
	 */
	public function jsonHandler( Throwable $throwable ): void {
		echo json_encode(
			array(
				'message' => $throwable->getMessage(),
				'file'    => $throwable->getFile(),
				'line'    => $throwable->getLine(),
				'trace'   => array_column( $throwable->getTrace(), 'file', 'function' ),
			),
			JSON_PRETTY_PRINT
		);
		exit;
	}

	/**
	 * Renders the exception by loading the HTML template and replacing placeholders.
	 */
	private function render( Throwable $throwable ) {
		$layout    = Template::get_part( 'layout' );
		$data      = array(
			'{{exception_message}}' => htmlspecialchars( $throwable->getMessage() ),
			'{{code_snippets}}'     => $this->generateCodeSnippets( $throwable->getTrace() ),
			'{{superglobals}}'      => $this->generateSuperglobals(),
		);
		$exception = Template::get_part( 'exception' );
		$exception = Template::compile( $data, $exception );
		$output    = Template::compile(
			array(
				'{{tailwind_css_url}}' => Template::get_asset( 'tailwind.css' ),
				'{{prism_css_url}}'    => Template::get_asset( 'prism.css' ),
				'{{content}}'          => $exception,
				'{{prism_js_url}}'     => Template::get_asset( 'prism.js' ),
			),
			$layout
		);
		http_response_code( 500 );
		echo $output;
	}

	/**
	 * Generates the HTML for code snippets based on the exception trace.
	 *
	 * @return string The HTML content for the code snippets.
	 */
	private function generateCodeSnippets( $trace ): string {
		$codeSnippetTemplate = Template::get_part( 'code' );
		$codeSnippets        = '';

		foreach ( $trace as $frame ) {
			if ( ! isset( $frame['file'] ) || ! is_readable( $frame['file'] ) ) {
				continue;
			}

			$filePath    = $frame['file'];
			$line        = $frame['line'];
			$fileName    = basename( $filePath );
			$editor      = "vscode://file/$filePath:$line";
			$fileContent = file_get_contents( $filePath ) ?? '';
			$lines       = explode( "\n", $fileContent );
			$startLine   = max( 0, $frame['line'] - 5 );
			$endLine     = min( count( $lines ), $frame['line'] + 5 );
			$snippet     = implode( "\n", array_slice( $lines, $startLine, $endLine - $startLine ) );

			$snippetPlaceholders = array(
				'{{file}}'         => $fileName,
				'{{editor_link}}'  => htmlspecialchars( $editor ),
				'{{file_path}}'    => htmlspecialchars( $filePath ),
				'{{start_line}}'   => $startLine,
				'{{end_line}}'     => $endLine,
				'{{line_number}}'  => $frame['line'],
				'{{code_snippet}}' => htmlspecialchars( $snippet ),
			);

			$codeSnippets .= Template::compile( $snippetPlaceholders, $codeSnippetTemplate );
		}

		return $codeSnippets;
	}

	/**
	 * Generates the HTML for the superglobals section.
	 *
	 * @return string The HTML content for the superglobals.
	 */
	private function generateSuperglobals(): string {
		$superglobals = array(
			'$_GET'     => $_GET,
			'$_POST'    => $_POST,
			'$_SERVER'  => $_SERVER,
			'$_FILES'   => $_FILES,
			'$_COOKIE'  => $_COOKIE,
			'$_SESSION' => $_SESSION ?? array(),
			'$_ENV'     => $_ENV,
		);

		$template = Template::get_part( 'variable' );
		$output   = '';

		foreach ( $superglobals as $name => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			$data = array(
				'{{name}}'  => $name,
				'{{value}}' => var_export( $value, true ),
			);

			$output .= Template::compile( $data, $template );
		}

		return $output;
	}
}
