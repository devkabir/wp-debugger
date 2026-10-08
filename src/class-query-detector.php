<?php

namespace DevKabir\WPDebugger;

/**
 * Detects N+1 query patterns and reports them to CLI, browser and log.
 */
class Query_Detector {
	/**
	 * Hook the detector into shutdown.
	 */
	public function __construct() {
		add_action( 'shutdown', array( $this, 'detect_and_report' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Inspect collected queries and report any repeated pattern.
	 *
	 * @return void
	 */
	public function detect_and_report(): void {
		global $wpdb;

		if ( empty( $wpdb->queries ) ) {
			return;
		}

		$threshold = (int) apply_filters( 'wp_debugger_n1_threshold', defined( 'N1_QUERY_THRESHOLD' ) ? (int) N1_QUERY_THRESHOLD : 5 );
		$filter    = (string) apply_filters( 'wp_debugger_n1_caller_filter', '' );
		$patterns  = array();

		foreach ( $wpdb->queries as $query ) {
			list( $sql, $time, $caller ) = array_pad( (array) $query, 3, '' );

			if ( '' !== $filter && false === strpos( (string) $caller, $filter ) ) {
				continue;
			}

			$key = $this->normalize( (string) $sql );

			$patterns[ $key ]['count']    = ( $patterns[ $key ]['count'] ?? 0 ) + 1;
			$patterns[ $key ]['time']     = ( $patterns[ $key ]['time'] ?? 0 ) + (float) $time;
			$patterns[ $key ]['callers'][ (string) $caller ] = true;
		}

		$flagged = array_filter(
			$patterns,
			static function ( $item ) use ( $threshold ) {
				return $item['count'] >= $threshold;
			}
		);

		if ( ! $flagged ) {
			return;
		}

		uasort(
			$flagged,
			static function ( $a, $b ) {
				return $b['count'] <=> $a['count'];
			}
		);
		write_log( sprintf( 'N+1: %d pattern(s) repeated >= %d times.', count( $flagged ), $threshold ), $flagged );

		if ( is_terminal_request() ) {
			$this->report_cli( $flagged );
		} else {
			// Shutdown runs after admin_notices, so park the summary for the next admin load.
			set_transient( 'wp_debugger_n1_notice', count( $flagged ), MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Show the parked summary once; details are in the debug log.
	 *
	 * @return void
	 */
	public function admin_notice(): void {
		$count = get_transient( 'wp_debugger_n1_notice' );

		if ( ! $count ) {
			return;
		}

		delete_transient( 'wp_debugger_n1_notice' );

		printf(
			'<div class="notice notice-warning"><p><strong>WP Debugger:</strong> %d repeated query pattern(s) detected. See the debug log for details.</p></div>',
			(int) $count
		);
	}

	/**
	 * Reduce a query to a comparable signature.
	 *
	 * @param string $sql Raw SQL statement.
	 *
	 * @return string
	 */
	private function normalize( string $sql ): string {
		$sql = preg_replace( '/\'[^\']*\'|"[^"]*"/', '?', $sql );
		$sql = preg_replace( '/\bIN\s*\(\s*[^)]+\)/i', 'IN (?)', $sql );
		$sql = preg_replace( '/\b\d+\b/', '?', $sql );

		return trim( preg_replace( '/\s+/', ' ', $sql ) );
	}

	/**
	 * Print flagged patterns to the terminal.
	 *
	 * @param array $flagged Flagged patterns keyed by signature.
	 *
	 * @return void
	 */
	private function report_cli( array $flagged ): void {
		$out = sprintf( "\n\033[1;33m⚠ N+1 queries: %d pattern(s)\033[0m\n", count( $flagged ) );

		foreach ( $flagged as $pattern => $item ) {
			$out .= sprintf(
				"\n\033[1;31m%5dx\033[0m  %ss  \033[2m(~%sms each)\033[0m\n        %s\n",
				$item['count'],
				number_format( $item['time'], 4 ),
				number_format( ( $item['time'] / $item['count'] ) * 1000, 2 ),
				$pattern
			);

			foreach ( array_slice( array_keys( $item['callers'] ), 0, 3 ) as $caller ) {
				$out .= sprintf( "        \033[2mfrom\033[0m %s\n", $this->short_caller( $caller ) );
			}

			$extra = count( $item['callers'] ) - 3;
			if ( $extra > 0 ) {
				$out .= sprintf( "        \033[2m… and %d more call site(s)\033[0m\n", $extra );
			}
		}

		fwrite( STDERR, $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	}

	/**
	 * Keep the last few frames of a caller chain; the bootstrap prefix is noise.
	 *
	 * @param string $caller Comma separated caller chain from $wpdb->queries.
	 *
	 * @return string
	 */
	private function short_caller( string $caller ): string {
		$frames = array_map( 'trim', explode( ',', $caller ) );

		// Drop wrappers that say nothing about which code issued the query.
		$frames = array_values(
			array_filter(
				$frames,
				static function ( $frame ) {
					return ! preg_match( '/^(require|require_once|include|include_once)\b|^option_value$/', $frame );
				}
			)
		);

		return implode( ' → ', array_slice( $frames, -3 ) );
	}

}
