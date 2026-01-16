<?php

namespace DevKabir\WPDebugger;

/**
 * Template helper for asset management
 */
class Template {
	/**
	 * Get the URL for a plugin asset
	 *
	 * @param string $file Relative path to asset file (e.g., 'css/page.css')
	 * @return string Full URL to the asset
	 * @throws \RuntimeException If asset file doesn't exist
	 */
	public static function get_asset( string $file ): string {
		$path = plugin_dir_path( FILE ) . 'assets/' . $file;
		if ( ! file_exists( $path ) ) {
			throw new \RuntimeException( "Asset {$file} not found at {$path}" );
		}

		return plugins_url( 'assets/' . $file, FILE );
	}
}
