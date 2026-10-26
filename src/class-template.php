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
		$path = dirname( __DIR__ ) . "/templates/{$template}.php";

		if ( ! file_exists( $path ) ) {
			throw new \RuntimeException( "Template {$template} not found" );
		}

		// EXTR_SKIP: a data key like "path" must not replace the file we include.
		extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include $path;
	}
}
