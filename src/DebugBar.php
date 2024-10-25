<?php

namespace DevKabir\WPDebugger;

class DebugBar {
	/**
	 * The array of messages to display in the debug bar.
	 *
	 * @var array
	 */
	private $messages = array();

	/**
	 * The current execution time in seconds when the debug bar was initialized.
	 *
	 * @var float
	 */
	private $start_time;

	/**
	 * The current memory usage in bytes when the debug bar was initialized.
	 *
	 * @var int
	 */
	private $start_memory;

	/**
	 * The instance of this class.
	 *
	 * @var DebugBar
	 */
	private static $instance = null;

	/**
	 * Returns the instance of this class.
	 *
	 * Ensures that only one instance is created.
	 *
	 * @return DebugBar The instance of this class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializes the debug bar.
	 *
	 * Stores the current execution time and memory usage for later use.
	 * Hooks into `admin_enqueue_scripts` to enqueue the CSS file, and
	 * `admin_footer` to render the debug bar.
	 */
	public function __construct() {
		$this->start_time   = microtime( true );
		$this->start_memory = memory_get_usage();
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		// add_action( 'admin_footer', array( $this, 'render' ) );
		add_action( 'wp_loaded', array( $this, 'action_wp_loaded' ) );
	}

	/**
	 * This hook is fired once WP, all plugins, and the theme are fully loaded and instantiated.
	 */
	public function action_wp_loaded(): void {
		$end_memory     = memory_get_usage();
		$end_time       = microtime( true );
		$execution_time = $this->format_time( $end_time - $this->start_time );
		$memory_usage   = $this->format_memory( $end_memory - $this->start_memory );
		$this->add_message( $execution_time, 'timer' );
		$this->add_message( $memory_usage );
	}


	/**
	 * Enqueues the debug bar CSS style in the WordPress admin area.
	 *
	 * This function hooks into the 'admin_enqueue_scripts' action to ensure
	 * that the necessary CSS file for the debug bar is loaded in the admin
	 * interface.
	 */
	public function scripts() {
		wp_enqueue_style( 'debug-bar', Template::get_asset( 'debugbar.css' ), array(), time() );
	}

	/**
	 * Adds a message to the debug bar.
	 */
	public function add_message( $message, $icon = null ) {
		if ( ! empty( $icon ) ) {
			$this->messages[ $icon ] = $message;
		} else {
			$this->messages[] = $message;
		}
	}

	/**
	 * Renders the debug bar at the bottom of the WordPress admin area.
	 *
	 * Compiles the debug bar template with the execution time and memory usage
	 * since the debug bar was initialized. Also renders any messages that were
	 * added to the debug bar using the `add_message` method.
	 */
	public function render() {
		init_debugger();
		$template  = Template::get_part( 'bar' );
		$part      = Template::get_part( 'bar-item' );
		$icon_part = Template::get_part( 'bar-item-with-icon' );
		$output    = '';
		if ( ! empty( $this->messages ) ) {
			foreach ( $this->messages as $icon => $message ) {
				$icon_path = Template::get_asset( 'icons/' . $icon . '.png' );
				$output   .= Template::compile(
					array(
						'{{item}}'      => $message,
						'{{icon_path}}' => $icon_path,
						'{{icon}}'      => $icon,
					),
					file_exists( $icon_path ) ? $icon_part : $part
				);
			}
			echo Template::compile( array( '{{content}}' => $output ), $template );
		}
	}

	/**
	 * Converts a given size in bytes to a human-readable string.
	 *
	 * @param int $size The size in bytes to format.
	 *
	 * @return string The formatted string.
	 */
	private function format_memory( $size ): string {
		$units     = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$unitIndex = 0;
		while ( $size >= 1024 && $unitIndex < count( $units ) - 1 ) {
			$size /= 1024;
			++$unitIndex;
		}

		return round( $size, 2 ) . ' ' . $units[ $unitIndex ];
	}

	/**
	 * Converts a given time in seconds to a human-readable string.
	 *
	 * @param float $time The time in seconds to format.
	 *
	 * @return string The formatted string.
	 */
	private function format_time( $time ): string {
		if ( $time < 1e-6 ) {
			return round( $time * 1e9, 2 ) . ' ns'; // Nanoseconds
		} elseif ( $time < 1e-3 ) {
			return round( $time * 1e6, 2 ) . ' μs'; // Microseconds
		} elseif ( $time < 1 ) {
			return round( $time * 1e3, 2 ) . ' ms'; // Milliseconds
		} else {
			return round( $time, 2 ) . ' s'; // Seconds
		}
	}
}
