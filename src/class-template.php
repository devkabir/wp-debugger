<?php

namespace DevKabir\WPDebugger;

/**
 * Renders a PHP template from the templates directory.
 */
class Template {
	/**
	 * Render a template file.
	 *
	 * @param string $template Template name (e.g., 'dump', 'page').
	 * @param array  $data     Data to inject into the template.
	 *
	 * @return void
	 * @throws \RuntimeException If the template file doesn't exist.
	 */
	public static function render( string $template, array $data = array() ): void {
		$path = plugin_dir_path( FILE ) . "templates/{$template}.php";

		if ( ! file_exists( $path ) ) {
			throw new \RuntimeException( "Template {$template} not found" );
		}

		// Extract variables so they are accessible inside the PHP template.
		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include $path;
	}
}
