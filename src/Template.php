<?php

namespace DevKabir\WPDebugger;

use Throwable;

class Template {
	public static function get_asset( $file ) {
		return plugins_url( 'assets/' . $file, FILE );
	}


	/**
	 * Loads the specified template file content.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public static function get_part( string $name ): string {
		$template = plugin_dir_path( FILE ) . 'assets/templates/' . $name . '.html';

		if ( ! file_exists( $template ) ) {
			die( "Template: $template not found." );
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
		return str_replace( array_keys( $data ), array_values( $data ), $template );
	}

	public static function get_layout(): string {
		return self::compile(
			array(
				'{{css_url}}' => self::get_asset( 'page.css' ),
				'{{prism_css_url}}' => self::get_asset( 'prism.css' ),
				'{{prism_js_url}}' => self::get_asset( 'prism.js' ),
			),
			self::get_part( 'layout' )
		);
	}
}
