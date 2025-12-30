<?php

/**
 * Logs a message to a specified directory.
 *
 * @param mixed ...$messages The messages to be logged. If the last argument is a string
 *                           ending with '.log', it will be treated as the filename.
 *
 * @return void
 *
 * @example write_log('Debug message'); // Logs to default file
 * @example write_log('Error occurred', 'errors.log'); // Logs to custom file
 * @example write_log($data, $trace, 'debug.log'); // Multiple messages to custom file
 */
function write_log( ...$messages ) {
	$filename = '0-debugger.log';

	if ( count( $messages ) > 0 ) {
		$last_message = end( $messages );
		if ( is_string( $last_message ) && str_ends_with( $last_message, '.log' ) ) {
			$filename = array_pop( $messages );
		}
	}

	$log = new \DevKabir\WPDebugger\Log( $filename );
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
			'function' => $value['function'] ?? '',
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
 * Debug from called spot.
 *
 * @return void
 * @throws Exception
 */
function init_debugger() {
	DevKabir\WPDebugger\Plugin::get_instance()->throw_exception();
}

/**
 * Outputs a formatted dump of a variable for debugging purposes.
 *
 * @return void
 * @throws Exception
 */
function dump() {
	if ( \DevKabir\WPDebugger\Plugin::get_instance()->is_json_request() ) {
		echo debugger_format_variable( ...func_get_args() );
	} else {
		$compiled_data = DevKabir\WPDebugger\Error_Page::dump( ...func_get_args() );
		echo DevKabir\WPDebugger\Template::compile( array( '{{content}}' => $compiled_data ), DevKabir\WPDebugger\Template::get_layout() );
	}
}

/**
 * Dump a variable and stop execution.
 *
 * @param mixed  The variable to dump.
 *
 * @return void
 * @throws Exception
 */
function dd() {
	dump( ...func_get_args() );
	die;
}

/**
 * Dump all callbacks registered for a specific WordPress filter.
 *
 * @param string $filter The name of the filter.
 * @param bool   $dump   Optional. Whether to dump or log the callbacks. Default is false for log.
 */
function dump_filter_callbacks( $filter, bool $dump = true ) {
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
	 * @param mixed $message The message to be formatted.
	 *
	 * @return string The formatted message with the timestamp.
	 */
	function debugger_format_variable( $message ): string {
		if ( is_array( $message ) ) {
			$message = wp_json_encode( $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		} elseif ( is_object( $message ) ) {
			$message = get_class( $message );
		} elseif ( is_string( $message ) ) {
			$decoded = json_decode( $message, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				$message = wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			}
		}

		return (string) $message;
	}
}
