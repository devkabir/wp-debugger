<?php

namespace DevKabir\WPDebugger;

use Throwable;
use DevKabir\WPDebugger\ErrorPage\PageHandler;

class ErrorPage {

	public function __construct() {
		error_reporting( E_ALL );
		set_exception_handler( [ $this, 'handle' ] );
	}

	public function handle( Throwable $throwable ): void {
		new PageHandler( $throwable );
	}


}