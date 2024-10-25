<?php
/**
 * Logs a message to a specified directory.
 *
 * @param mixed  $message The message to be logged.
 * @param bool   $trace   Whether to log the backtrace.
 * @param string $dir     The directory where the log file will be written.
 *
 * @return void
 * @throws \Exception
 */
function write_log( $message, bool $trace = false, string $dir = WP_CONTENT_DIR ) {
	DevKabir\WPDebugger\Plugin::get_instance()->log( $message, $trace, $dir );
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
 * Dump a variable's information for debugging purposes.
 *
 * @return void
 */
function dump() {
	\DevKabir\WPDebugger\ErrorPage::dump( ...func_get_args() );
}

/**
 * Dump a variable and stop execution.
 *
 * @return void
 */
function dd() {
	dump( ...func_get_args() );
	die;
}

/**
 * Adds a message to the debug bar.
 *
 * @param mixed $message The message to add to the debug bar.
 *
 * @return void
 */
function push_to_bar( $message ) {
	DevKabir\WPDebugger\DebugBar::get_instance()->add_message( $message );
}
