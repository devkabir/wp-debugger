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
 * Format a stack trace into a compact file:line keyed summary.
 *
 * @param array $trace The stack trace.
 *
 * @return array The formatted trace.
 */
function debugger_trace_summary( array $trace ): array {
	$formatted_trace = array();
	foreach ( $trace as $frame ) {
		if ( isset( $frame['file'] ) ) {
			$formatted_trace[ $frame['file'] . ':' . $frame['line'] ] = array(
				'function' => $frame['function'],
				'args'     => isset( $frame['args'] ) ? array_filter( $frame['args'] ) : false,
			);
		}
	}

	return $formatted_trace;
}

/**
 * Returns true when the current process is running in a terminal (CLI/WP-CLI) with no browser client.
 *
 * @return bool
 */
function is_terminal_request(): bool {
	return PHP_SAPI === 'cli' || ( defined( 'WP_CLI' ) && WP_CLI );
}

/**
 * Outputs a formatted dump of a variable for debugging purposes.
 *
 * @param mixed ...$args The variables to dump.
 *
 * @return void
 * @throws Exception
 */
function dump( ...$args ) {
	$data = recursively_decode_json( $args );

	if ( is_terminal_request() ) {
		echo json_encode( count( $data ) === 1 ? $data[0] : $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . PHP_EOL;
		return;
	}

	\DevKabir\WPDebugger\Template::render( 'dump', $data );
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
 * @param bool   $dump Optional. Whether to dump or log the callbacks. Default is true for dump.
 */
function dump_filter_callbacks( string $filter, bool $dump = true ) {
	global $wp_filter;

	if ( ! isset( $wp_filter[ $filter ] ) ) {
		echo "No callbacks found for filter: {$filter}";

		return;
	}

	// WordPress 5.0+ wraps callbacks in WP_Hook objects.
	$callbacks = $wp_filter[ $filter ] instanceof WP_Hook ? $wp_filter[ $filter ]->callbacks : $wp_filter[ $filter ];

	$dump ? dump( $filter, $callbacks ) : write_log( $filter, $callbacks );
}

/**
 * Recursively decode JSON strings, so nested JSON is shown as data.
 *
 * Any valid JSON string is decoded, so "123", "true" and "null" become scalars too.
 *
 * @param mixed $data The input value to check.
 *
 * @return mixed The processed array or original value.
 */
function recursively_decode_json( $data ) {
	if ( is_string( $data ) ) {
		$decoded = json_decode( $data, true );
		if ( JSON_ERROR_NONE === json_last_error() ) {
			$data = $decoded;
		}
	}

	return is_array( $data ) ? array_map( 'recursively_decode_json', $data ) : $data;
}

if ( ! function_exists( 'debugger_format_variable' ) ) {
	/**
	 * Format values as text lines for logging; arrays and JSON strings are pretty-printed.
	 *
	 * @param mixed ...$messages The values to format.
	 *
	 * @return string The formatted text.
	 */
	function debugger_format_variable( ...$messages ): string {
		$result = '';
		foreach ( $messages as $message ) {
			if ( is_array( $message ) ) {
				ksort( $message );
				$message = wp_json_encode( $message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
			} elseif ( is_object( $message ) ) {
				$message = $message::class;
			} elseif ( is_string( $message ) ) {
				$decoded = json_decode( $message, true );
				if ( JSON_ERROR_NONE === json_last_error() ) {
					$message = wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
				}
			}
			$result .= $message . PHP_EOL;
		}

		return $result;
	}
}
