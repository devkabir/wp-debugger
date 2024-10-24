<?php

namespace DevKabir\WPDebugger;

use Throwable;
use DevKabir\WPDebugger\ErrorPage\PageHandler;

class ErrorPage {
	public function __construct() {
		set_exception_handler( [ $this, 'handle' ] );
		ini_set( "display_errors", "off" );
		error_reporting( - 1 );
	}

	public function handle( Throwable $throwable ): void {
		if ( $this->isJsonRequest() || wp_doing_ajax() ) {
			$this->jsonHandler( $throwable );
		}

		new PageHandler( $throwable );
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
		echo json_encode( [
			'message' => $throwable->getMessage(),
			'file'    => $throwable->getFile(),
			'line'    => $throwable->getLine(),
			'trace'   => array_column( $throwable->getTrace(), 'file', 'function' ),
		], JSON_PRETTY_PRINT );
		exit;
	}
}