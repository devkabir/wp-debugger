<?php
/**
 * This script dynamically loads all classes in the `src` directory
 * following the PSR-4 standard.
 *
 * @package MyProject
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Autoloader function for classes in the `src` directory.
 *
 * @param string $class The fully qualified class name.
 */
spl_autoload_register(
	static function ( $class_name ) {
		// Base namespace for the plugin.
		$namespace = 'DevKabir\\WPDebugger\\';

		// Ensure the class belongs to this namespace.
		if ( strpos( $class_name, $namespace ) === 0 ) {
			// Remove the base namespace from the class.
			// Replace namespace separators with directory separators.
			$relative_class = str_replace( array( $namespace, '\\' ), array( '', DIRECTORY_SEPARATOR ), $class_name );

			// Build the full file path.
			$file = __DIR__ . '/src/' . $relative_class . '.php';

			// Include the file if it exists.
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
);

require_once __DIR__ . '/functions.php';
