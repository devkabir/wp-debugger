<?php

namespace DevKabir\WPDebugger;

/**
 * Template helper for asset management
 */
class Template {
	/**
	 * Cached manifest data
	 *
	 * @var array|null
	 */
	private static $manifest = null;

	/**
	 * Load and cache the Vite manifest
	 *
	 * @return array
	 * @throws \RuntimeException If manifest file doesn't exist or is invalid
	 */
	private static function get_manifest(): array {
		if ( null !== self::$manifest ) {
			return self::$manifest;
		}

		$manifest_path = plugin_dir_path( FILE ) . 'dist/manifest.json';
		if ( ! file_exists( $manifest_path ) ) {
			throw new \RuntimeException( "Manifest file not found at {$manifest_path}" );
		}

		$manifest_content = file_get_contents( $manifest_path );
		self::$manifest   = json_decode( $manifest_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new \RuntimeException( 'Failed to parse manifest.json: ' . json_last_error_msg() );
		}

		return self::$manifest;
	}

	/**
	 * Get the URL for a plugin asset
	 *
	 * @param string $file Relative path to asset file (e.g., 'css/page.css')
	 * @return string Full URL to the asset
	 * @throws \RuntimeException If asset file doesn't exist
	 */
	public static function get_asset( string $file ): string {
		$path = plugin_dir_path( FILE ) . 'dist/' . $file;
		if ( ! file_exists( $path ) ) {
			throw new \RuntimeException( "Asset {$file} not found at {$path}" );
		}

		return plugins_url( 'dist/' . $file, FILE );
	}

	/**
	 * Get asset URLs for an entry point from the Vite manifest
	 *
	 * @param string $entry Entry point name (e.g., 'dump', 'page')
	 * @return array{js: string, css: string[]} Array with 'js' and 'css' keys
	 * @throws \RuntimeException If entry not found in manifest
	 */
	public static function get_entry_assets( string $entry ): array {
		$manifest   = self::get_manifest();
		$entry_key  = "src/{$entry}/index.js";
		$entry_data = $manifest[ $entry_key ] ?? null;

		if ( ! $entry_data ) {
			throw new \RuntimeException( "Entry '{$entry}' not found in manifest" );
		}

		$assets = array(
			'js'  => self::get_asset( $entry_data['file'] ),
			'css' => array(),
		);

		if ( isset( $entry_data['css'] ) && is_array( $entry_data['css'] ) ) {
			foreach ( $entry_data['css'] as $css_file ) {
				$assets['css'][] = self::get_asset( $css_file );
			}
		}

		// Load CSS from imported modules
		if ( isset( $entry_data['imports'] ) && is_array( $entry_data['imports'] ) ) {
			foreach ( $entry_data['imports'] as $import_key ) {
				$import_data = $manifest[ $import_key ] ?? null;
				if ( $import_data && isset( $import_data['css'] ) && is_array( $import_data['css'] ) ) {
					foreach ( $import_data['css'] as $css_file ) {
						$assets['css'][] = self::get_asset( $css_file );
					}
				}
			}
		}

		return $assets;
	}

	/**
	 * Render a template file with enqueued assets
	 *
	 * @param string $template Template name (e.g., 'dump', 'page')
	 * @param array  $data     Data to inject into the template
	 * @return void
	 * @throws \RuntimeException If template file doesn't exist
	 */
	public static function render( string $template, array $data = array() ): void {
		$template_path = plugin_dir_path( FILE ) . "templates/{$template}.html";
		if ( ! file_exists( $template_path ) ) {
			throw new \RuntimeException( "Template {$template}.html not found" );
		}

		$assets = self::get_entry_assets( $template );

		// Output HTML with injected assets and data
		$html = file_get_contents( $template_path );

		// Inject CSS in head
		$css_links = '';
		foreach ( $assets['css'] as $css_url ) {
			$css_links .= sprintf( '<link rel="stylesheet" href="%s" />', esc_url( $css_url ) ) . "\n        ";
		}
		$html = str_replace( '</head>', "        {$css_links}</head>", $html );

		// Inject data script
		$data_json   = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		$data_script = sprintf(
			'<script id="wp-debugger-%s-data">var wpDebuggerData = %s;</script>',
			esc_attr( $template ),
			$data_json
		);
		$html        = str_replace(
			sprintf( '<script id="wp-debugger-%s-data">', $template ),
			$data_script . "\n        " . sprintf( '<script type="module" src="%s"></script>', esc_url( $assets['js'] ) ) . "\n        " . sprintf( '<!--<script id="wp-debugger-%s-data">', $template ),
			$html
		);

		echo $html;
	}
}
