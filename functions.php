<?php

/**
 * Logs a message to a specified directory.
 *
 * @param mixed ...$messages The messages to be logged.
 *
 * @return void
 */
function write_log( ...$messages ) {
    $log = \DevKabir\WPDebugger\Log::get_instance();
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

    return array_reverse( $formatted_trace );
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
 * @param mixed $variable The variable to dump.
 *
 * @return void
 * @throws Exception
 */
function dump( $variable ) {
    if ( \DevKabir\WPDebugger\Plugin::get_instance()->is_json_request() ) {
        echo json_encode( $variable, JSON_PRETTY_PRINT );
    } else {
        $compiled_data = DevKabir\WPDebugger\Error_Page::dump( $variable );
        echo DevKabir\WPDebugger\Template::compile( array( '{{content}}' => $compiled_data ), DevKabir\WPDebugger\Template::get_layout() );
    }
}

/**
 * Dump a variable and stop execution.
 *
 * @param mixed $data The variable to dump.
 *
 * @return void
 * @throws Exception
 */
function dd( ...$data ) {
    dump( func_get_args() );
    die;
}