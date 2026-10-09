<?php

namespace DevKabir\WPDebugger;

/**
 * Detects N+1 query patterns and slow queries, then reports them to CLI, browser and log.
 */
class Query_Detector {
	/**
	 * Hook the detector into shutdown.
	 */
	public function __construct() {
		if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES ) {
			define( 'SAVEQUERIES', true );
		}
		add_action( 'shutdown', array( $this, 'detect_and_report' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Inspect collected queries and report repeated patterns and slow queries.
	 *
	 * @return void
	 */
	public function detect_and_report(): void {
		global $wpdb;

		if ( empty( $wpdb->queries ) ) {
			return;
		}

		$threshold = (int) apply_filters( 'wp_debugger_n1_threshold', 5 );
		$slow_limit = (float) apply_filters( 'wp_debugger_slow_query_threshold', 0.05 );
		$needles   = array_filter( (array) apply_filters( 'wp_debugger_n1_caller_filter', '' ), 'strlen' );
		$groups    = array();
		$slow      = array();

		foreach ( $wpdb->queries as $query ) {
			list( $sql, $time, $caller ) = array_pad( (array) $query, 3, '' );

			$sql    = (string) $sql;
			$time   = (float) $time;
			$caller = (string) $caller;

			if ( $needles && ! $this->caller_matches( $caller, $needles ) ) {
				continue;
			}

			if ( $time >= $slow_limit ) {
				$slow[] = array(
					'time'   => $time,
					'sql'    => $sql,
					'caller' => $caller,
				);
			}

			// Group by query shape so a caller running N different queries is not mistaken for N+1.
			$shape = $this->normalize_sql( $sql );
			$key   = $caller . "\n" . $shape;

			$groups[ $key ]['count']  = ( $groups[ $key ]['count'] ?? 0 ) + 1;
			$groups[ $key ]['time']   = ( $groups[ $key ]['time'] ?? 0 ) + $time;
			$groups[ $key ]['sql']    = $shape;
			$groups[ $key ]['sample'] = $groups[ $key ]['sample'] ?? $sql;
			$groups[ $key ]['caller'] = $caller;
		}

		$flagged = array_filter(
			$groups,
			static function ( $item ) use ( $threshold ) {
				return $item['count'] >= $threshold;
			}
		);

		uasort(
			$flagged,
			static function ( $a, $b ) {
				return $b['count'] <=> $a['count'];
			}
		);

		usort(
			$slow,
			static function ( $a, $b ) {
				return $b['time'] <=> $a['time'];
			}
		);

		if ( ! $flagged && ! $slow ) {
			return;
		}

		$log = new Log( 'query-detector.log' );
		$log->write(
			array(
				'n1'   => array_values( $flagged ),
				'slow' => $slow,
			)
		);

		if ( is_terminal_request() ) {
			$this->report_cli( $flagged, $slow );
		} else {
			// Shutdown runs after admin_notices, so park the summary for the next admin load.
			set_transient(
				'wp_debugger_n1_notice',
				array(
					'n1'   => count( $flagged ),
					'slow' => count( $slow ),
				),
				MINUTE_IN_SECONDS
			);
		}
	}

	/**
	 * Show the parked summary once; details are in the debug log.
	 *
	 * @return void
	 */
	public function admin_notice(): void {
		$counts = get_transient( 'wp_debugger_n1_notice' );

		if ( ! $counts ) {
			return;
		}

		delete_transient( 'wp_debugger_n1_notice' );

		$counts = (array) $counts;
		$parts  = array();

		if ( ! empty( $counts['n1'] ) ) {
			$parts[] = sprintf( '%d repeated query pattern(s)', (int) $counts['n1'] );
		}

		if ( ! empty( $counts['slow'] ) ) {
			$parts[] = sprintf( '%d slow query(s)', (int) $counts['slow'] );
		}

		if ( ! $parts ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>WP Debugger:</strong> %s detected. See the debug log for details.</p></div>',
			esc_html( implode( ' and ', $parts ) )
		);
	}

	/**
	 * Reduce a query to its shape by replacing literals with placeholders.
	 *
	 * @param string $sql Raw SQL from $wpdb->queries.
	 *
	 * @return string
	 */
	private function normalize_sql( string $sql ): string {
		$sql = preg_replace( '/\s+/', ' ', trim( $sql ) );
		$sql = preg_replace( '/\'[^\']*\'|"[^"]*"/', '?', (string) $sql );
		$sql = preg_replace( '/\b\d+\b/', '?', (string) $sql );
		$sql = preg_replace( '/\?(\s*,\s*\?)+/', '?', (string) $sql );

		return (string) $sql;
	}

	/**
	 * Check a caller chain against any of the configured needles.
	 *
	 * @param string $caller  Comma separated caller chain from $wpdb->queries.
	 * @param array  $needles Substrings to look for; any hit keeps the query.
	 *
	 * @return bool
	 */
	private function caller_matches( string $caller, array $needles ): bool {
		foreach ( $needles as $needle ) {
			if ( str_contains( $caller, (string) $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Print flagged patterns and slow queries to the terminal.
	 *
	 * @param array $flagged Flagged groups keyed by caller chain and query shape.
	 * @param array $slow    Slow queries, slowest first.
	 *
	 * @return void
	 */
	private function report_cli( array $flagged, array $slow ): void {
		$out = '';

		if ( $flagged ) {
			$out .= sprintf( "\n\033[1;33m⚠ N+1 queries: %d pattern(s)\033[0m\n", count( $flagged ) );

			foreach ( $flagged as $item ) {
				$out .= sprintf(
					"\n\033[1;31m%5dx\033[0m  %ss  \033[2m(~%sms each)\033[0m\n        \033[2mquery\033[0m %s\n        \033[2mfrom\033[0m %s\n",
					$item['count'],
					number_format( $item['time'], 4 ),
					number_format( ( $item['time'] / $item['count'] ) * 1000, 2 ),
					$this->truncate( $item['sql'] ),
					$item['caller']
				);
			}
		}

		if ( $slow ) {
			$out .= sprintf( "\n\033[1;33m⚠ Slow queries: %d\033[0m\n", count( $slow ) );

			foreach ( $slow as $item ) {
				$out .= sprintf(
					"\n\033[1;31m%8sms\033[0m\n        \033[2mquery\033[0m %s\n        \033[2mfrom\033[0m %s\n",
					number_format( $item['time'] * 1000, 2 ),
					$this->truncate( $item['sql'] ),
					$item['caller']
				);
			}
		}

		fwrite( STDERR, $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	}

	/**
	 * Shorten a query for single-line terminal output.
	 *
	 * @param string $sql Query text.
	 *
	 * @return string
	 */
	private function truncate( string $sql ): string {
		$sql = preg_replace( '/\s+/', ' ', trim( $sql ) );

		return strlen( (string) $sql ) > 160 ? substr( (string) $sql, 0, 157 ) . '...' : (string) $sql;
	}
}
