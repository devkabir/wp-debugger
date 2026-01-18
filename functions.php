<?php

/**
 * Logs a message to a specified directory.
 *
 * @param mixed ...$messages The messages to be logged.
 *
 * @return void
 */
function write_log( ...$messages ) {
	$log = new \DevKabir\WPDebugger\Log();
	foreach ( $messages as $message ) {
		$log->write( $message );
	}
}

/**
 * Format stack trace.
 *
 * @param array $trace The stack trace.
 *
 * @return array The formatted trace.
 */
function format_stack_trace( array $trace ): array {
	$formatted_trace = array();
	foreach ( $trace as $value ) {
		if ( ! isset( $value['file'] ) ) {
			continue;
		}
		$formatted_trace[ $value['file'] . ':' . $value['line'] ] = array(
			'function' => $value['function'],
			'args'     => isset( $value['args'] ) ? array_filter(
				$value['args'],
				function ( $arg ) {
					return ! empty( $arg );
				}
			) : false,
		);
	}

	return $formatted_trace;
}

/**
 * Log stack trace.
 *
 * @param array $trace The stack trace.
 *
 * @return void
 * @throws Exception
 */
function log_stack_trace( array $trace ) {
	write_log( format_stack_trace( $trace ) );
}

/**
 * Outputs a formatted dump of a variable for debugging purposes.
 *
 * @return void
 * @throws Exception
 */
function dump() {
	\DevKabir\WPDebugger\Template::render( 'dump', recursively_decode_json( func_get_args() ) );
}

/**
 * Dump a variable and stop execution.
 *
 * @param mixed ...$args The variables to dump.
 *
 * @return void
 * @throws Exception
 */
function dd( ...$args ) {
	dump( ...$args );
	die;
}

/**
 * Dump all callbacks registered for a specific WordPress filter.
 *
 * @param string $filter The name of the filter.
 * @param bool   $dump Optional. Whether to dump or log the callbacks. Default is false for log.
 */
function dump_filter_callbacks( string $filter, bool $dump = true ) {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $filter ] ) ) {
		echo "No callbacks found for filter: {$filter}";

		return;
	}

	// WordPress 5.0+ uses WP_Hook objects.
	$callbacks = $wp_filter[ $filter ];

	if ( is_a( $callbacks, 'WP_Hook' ) ) {
		$callbacks = $callbacks->callbacks;
	}

	if ( $dump ) {
		dump( $callbacks );
	} else {
		write_log( $callbacks );
	}
}

/**
 * Recursively checks if a value is a JSON string, decodes it into an array,
 * and ensures all elements are strings. If any element is JSON, it gets decoded recursively.
 *
 * @param mixed $data The input value to check.
 *
 * @return mixed The processed array or original value.
 */
function recursively_decode_json( $data ) {
	// Check if input is a JSON string.
	if ( is_string( $data ) ) {
		$decoded = json_decode( $data, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			$data = $decoded; // Convert to array.
		}
	}

	// If it's an array, process its elements.
	if ( is_array( $data ) ) {
		foreach ( $data as $key => $value ) {
			$data[ $key ] = recursively_decode_json( $value );
		}
	}

	return $data;
}

if ( ! function_exists( 'debugger_format_variable' ) ) {
	/**
	 * Formats a message with the current timestamp for logging.
	 *
	 * @return string The formatted message with the timestamp.
	 */
	function debugger_format_variable(): string {
		$args   = func_get_args();
		$result = '';
		foreach ( $args as $message ) {
			if ( is_array( $message ) ) {
				ksort( $message );
				$message = wp_json_encode( $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			} elseif ( is_object( $message ) ) {
				$message = get_class( $message );
			} elseif ( is_string( $message ) ) {
				$decoded = json_decode( $message, true );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					$message = wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
				}
			}
			$result .= $message . PHP_EOL;
		}

		return (string) $result;
	}
}
