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
		if ( ! defined( 'SAVEQUERIES' ) || ! SAVEQUERIES ) {
			define( 'SAVEQUERIES', true );
		}
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

		$threshold = (int) apply_filters( 'wp_debugger_n1_threshold', 5 );
		$needles   = array_filter( (array) apply_filters( 'wp_debugger_n1_caller_filter', '' ), 'strlen' );
		$groups    = array();

		foreach ( $wpdb->queries as $query ) {
			list( $sql, $time, $caller ) = array_pad( (array) $query, 3, '' );

			if ( $needles && ! $this->caller_matches( (string) $caller, $needles ) ) {
				continue;
			}

			$key = (string) $caller;

			$groups[ $key ]['count'] = ( $groups[ $key ]['count'] ?? 0 ) + 1;
			$groups[ $key ]['time']  = ( $groups[ $key ]['time'] ?? 0 ) + (float) $time;
			$groups[ $key ]['sql']   = (string) $sql;
		}

		$flagged = array_filter(
			$groups,
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
		$log = new Log( 'query-detector.log' );
		$log->write( $flagged );

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
			'<div class="notice notice-warning"><p><strong>WP Debugger:</strong> %d repeated query caller(s) detected. See the debug log for details.</p></div>',
			(int) $count
		);
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
	 * Print flagged callers to the terminal.
	 *
	 * @param array $flagged Flagged groups keyed by caller chain.
	 *
	 * @return void
	 */
	private function report_cli( array $flagged ): void {
		$out = sprintf( "\n\033[1;33m⚠ N+1 queries: %d caller(s)\033[0m\n", count( $flagged ) );

		foreach ( $flagged as $caller => $item ) {
			$out .= sprintf(
				"\n\033[1;31m%5dx\033[0m  %ss  \033[2m(~%sms each)\033[0m\n        \033[2mfrom\033[0m %s\n",
				$item['count'],
				number_format( $item['time'], 4 ),
				number_format( ( $item['time'] / $item['count'] ) * 1000, 2 ),
				$caller
			);
		}

		fwrite( STDERR, $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	}
}
