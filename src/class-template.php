<?php

namespace DevKabir\WPDebugger;

class Template {
	public static function get_asset( string $file ): string {
		$path = plugin_dir_path( FILE ) . 'assets/' . $file;
		if ( ! file_exists( $path ) ) {
			throw new \RuntimeException( "Asset {$file} not found" );
		}

		return plugins_url( 'assets/' . $file, FILE );
	}


	/**
	 * Loads the specified template file content.
	 *
	 * @param string $name
	 * @param string $folder
	 *
	 * @return string
	 */
	public static function get_part( string $name, string $folder = 'page' ): string {
		$template = plugin_dir_path( FILE ) . 'assets/templates/' . $folder . '/' . $name . '.html';

		if ( ! file_exists( $template ) ) {
			throw new \RuntimeException( "Template {$name} not found" );
		}

		return file_get_contents( $template );
	}

	/**
	 * Replaces placeholders in the template with provided data.
	 *
	 * @param mixed  $data
	 * @param string $template
	 *
	 * @return string
	 */
	public static function compile( $data, string $template ): string {
		if ( is_array( $data ) && isset( $data['{{content}}'] ) ) {
			$content = (string) $data['{{content}}'];
			if ( false === strpos( $content, 'id="wp-debugger"' ) && false === strpos( $content, "id='wp-debugger'" ) ) {
				$data['{{content}}'] = '<div id="wp-debugger">' . $content . '</div>';
			}
		}

		return str_replace( array_keys( $data ), array_values( $data ), $template );
	}

	public static function get_layout(): string {
		return self::compile(
			array(
				'{{css_url}}'           => self::get_asset( 'css/page.css' ),
				'{{prism_css_url}}'     => self::get_asset( 'css/prism.css' ),
				'{{prism_js_url}}'      => self::get_asset( 'js/prism.js' ),
				'{{copy_trace_js_url}}' => self::get_asset( 'js/copy-stack-trace.js' ),
			),
			self::get_part( 'layout' )
		);
	}
}
