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
 * @param string $class_name The fully qualified class name.
 */
spl_autoload_register(
	static function ( $class_name ) {
		// Define the base directory for the namespace prefix.
		$base_dir = __DIR__ . '/src/';

		// Replace namespace separators with directory separators.
		$relative_class = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );

		// Construct the full file path.
		$file = $base_dir . $relative_class . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

require_once __DIR__ . '/functions.php';
