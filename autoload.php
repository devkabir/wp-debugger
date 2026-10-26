<?php
/**
 * Loads DevKabir\WPDebugger classes from src/ using WordPress file naming: Foo_Bar => src/class-foo-bar.php.
 *
 * @package DevKabir\WPDebugger
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( $class_name ) {
		$namespace = 'DevKabir\\WPDebugger\\';

		if ( ! str_starts_with( $class_name, $namespace ) ) {
			return;
		}

		$file = __DIR__ . '/src/class-' . strtolower( str_replace( array( '_', '\\' ), array( '-', '/' ), substr( $class_name, strlen( $namespace ) ) ) ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

require_once __DIR__ . '/functions.php';
