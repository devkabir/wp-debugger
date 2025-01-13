<?php

namespace DevKabir\WPDebugger;

class Debug_Bar {
	/**
	 * Array of messages to display in the debug bar.
	 *
	 * @var array
	 */
	private $messages = array();

	/**
	 * Time and memory usage when the debug bar was initialized.
	 *
	 * @var float
	 */
	private $start_time;

	/**
	 * Memory usage in bytes when the debug bar was initialized.
	 *
	 * @var int
	 */
	private $start_memory;

	/**
	 * Array of contents to display in the debug bar.
	 *
	 * @var array
	 */
	private $contents;

	/**
	 * Initializes the debug bar with timing and memory usage.
	 */
	public function __construct() {
		$this->start_time   = microtime( true );
		$this->start_memory = memory_get_usage();
		$this->contents     = array(
			'Requests' => array(
				'Get'     => $_GET,
				'Post'    => $_POST,
				'Files'   => $_FILES,
				'Session' => $_SESSION ?? array(),
				'Cookie'  => $_COOKIE,
			),
		);
		/**
		 * Fires once a single activated plugin has loaded.
		 *
		 * @param string $plugin Full path to the plugin's main file.
		 */
		add_action(
			'plugin_loaded',
			function ( string $plugin ): void {
				$plugin                              = basename( dirname( $plugin ) );
				$this->contents['Plugin'][ $plugin ] = $this->get_time();
			}
		);
		/**
		 * Fires just before PHP shuts down execution.
		 */
		add_action(
			'shutdown',
			function (): void {
				global $wpdb;
				foreach ( $wpdb->queries as $query ) {
					$this->contents['Queries'][] = $query;
				}
			}
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'render' ) );
		add_action( 'wp_loaded', array( $this, 'log_execution' ) );
	}

	/**
	 * Parses an array of queries and adds them to the debug bar.
	 *
	 * @param array|null $queries An array of queries, as returned by wpdb::queries.
	 */
	public function parse_query( array $queries ): void {
		foreach ( $queries as $query ) {
			$this->contents['Queries'][] = $query;
		}
	}

	/**
	 * Gets the time elapsed since the start of the debug bar.
	 *
	 * @return string A human-readable string representing the time elapsed.
	 */
	public function get_time(): string {
		return $this->format_time( microtime( true ) - $this->start_time );
	}

	/**
	 * Logs execution time and memory usage at the end of loading.
	 */
	public function log_execution(): void {
		$this->add_message( $this->format_time( microtime( true ) - $this->start_time ), 'timer' );
		$this->add_message( $this->format_memory( memory_get_usage() - $this->start_memory ), 'chip' );
	}

	/**
	 * Enqueues the debug bar CSS style in the WordPress admin area.
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_style( 'debug-bar', Template::get_asset( 'css/bar.css' ), array(), time() );
	}

	/**
	 * Adds a message to the debug bar.
	 *
	 * @param string      $message
	 * @param string|null $icon
	 */
	public function add_message( string $message, ?string $icon = null ): void {
		$this->messages[] = $icon ? array(
			'icon'    => $icon,
			'message' => $message,
		) : array( 'message' => $message );
	}

	/**
	 * Renders the debug bar at the bottom of the WordPress admin area.
	 */
	public function render(): void {
		$output = '';
		foreach ( $this->messages as $message ) {
			$output .= Template::compile(
				array(
					'{{item}}'      => $message['message'],
					'{{icon_path}}' => $message['icon'] ? Template::get_asset( 'icons/' . $message['icon'] . '.png' ) : '',
				),
				$message['icon'] ? Template::get_part( 'with-icon', 'bar' ) : Template::get_part( 'item', 'bar' )
			);
		}

		echo Template::compile(
			array(
				'{{bar}}'  => $output,
				'{{body}}' => $this->render_table( $this->get_contents() ),
			),
			Template::get_part( 'bar', 'bar' )
		);
	}

	/**
	 * Converts a given size in bytes to a human-readable string.
	 *
	 * @param int $size
	 *
	 * @return string
	 */
	private function format_memory( int $size ): string {
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
	 * @param float $time
	 *
	 * @return string
	 */
	private function format_time( float $time ): string {
		return $time < 1e-6 ? round( $time * 1e9, 2 ) . ' ns'
			: ( $time < 1e-3 ? round( $time * 1e6, 2 ) . ' μs'
				: ( $time < 1 ? round( $time * 1e3, 2 ) . ' ms'
					: round( $time, 2 ) . ' s' ) );
	}

	/**
	 * Gathers and applies filters to debugging information.
	 *
	 * @return array
	 */
	private function get_contents(): array {
		/**
		 * Filter to push data into the debug bar contents.
		 *
		 * @param array $contents Collection of debug data.
		 */
		$filter_data = apply_filters( 'wp_debugger_contents', $this->contents );

		return array_merge( $this->contents, $filter_data );
	}

	private function render_table( array $contents ): string {
		$table = Template::get_part( 'table', 'bar' );
		$row   = Template::get_part( 'table_row', 'bar' );

		$rows = '';

		foreach ( $contents as $key => $value ) {
			if ( empty( $value ) ) {
				continue;
			}
			$rows .= Template::compile(
				array(
					'{{key}}'   => $key,
					'{{value}}' => is_array( $value ) ? $this->render_table( $value ) : $value,
				),
				$row
			);
		}

		return Template::compile( array( '{{rows}}' => $rows ), $table );
	}
}
