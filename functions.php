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
 * Outputs a formatted dump of a variable for debugging purposes.
 *
 * @param mixed $variable The variable to dump.
 * @return void
 */
function dump( $variable ) {
	$stylesheet = DevKabir\WPDebugger\Template::get_asset( 'prism.css' );
	$script = DevKabir\WPDebugger\Template::get_asset( 'prism.js' );
	$compiledData = DevKabir\WPDebugger\Template::compile( [ '{{content}}' => var_export( $variable, true ) ], DevKabir\WPDebugger\Template::get_part( 'dump' ) );
	echo '<link rel="stylesheet" href="' . $stylesheet . '">';
	echo '<script src="' . $script . '"></script>';
	echo '<div style="z-index: 9999; position: relative; width: 450px;float: right; margin-left: 1em; margin-right: 1em">' . $compiledData . '</div>';
}

/**
 * Dump a variable and stop execution.
 *
 * @return void
 */
function dd( $data ) {
	dump( $data );
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
