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
	$compiled_data = DevKabir\WPDebugger\Template::compile( array( '{{content}}' => var_export( $variable, true ) ), DevKabir\WPDebugger\Template::get_part( 'dump' ) );
	echo DevKabir\WPDebugger\Template::compile( array( '{{content}}' => $compiled_data ), DevKabir\WPDebugger\Template::get_layout() );
}

/**
 * Dump a variable and stop execution.
 *
 * @param mixed $data The variable to dump.
 *
 * @return void
 */
function dd( $data ) {
	dump( $data );
	die;
}

/**
 * Renders a checkbox field in the WordPress settings page.
 *
 * @param array $args { .
 *     @var string $id     The ID of the option to be saved in the database.
 *     @var string $label   The label for the checkbox.
 *
 *     @see add_settings_field()
 * }
 *
 * @return void
 */
function render_settings_fields( $args ) {
	$option = get_option( $args['id'] );
	echo '<input type="checkbox" id="' . $args['id'] . '" name="' . $args['id'] . '" value="1"' . checked( 1, $option, false ) . ' />';
	echo '<label for="' . $args['id'] . '"> ' . $args['label'] . '</label>';
}

/**
 * Retrieves the value of the `show_debugbar` option from the database.
 *
 * @return bool Whether the debug bar should be shown.
 */
function show_debugbar() {
	return (bool) get_option( 'show_debugbar', false );
}

/**
 * Retrieves the value of the `enable_debugger` option from the database.
 *
 * @return bool Whether the debugger should be enabled.
 */
function enable_debugger() {
	return (bool) get_option( 'enable_debugger', false );
}
