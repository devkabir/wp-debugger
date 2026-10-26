<?php

namespace DevKabir\WPDebugger;

/**
 * Detects N+1 query patterns and slow queries, then reports them to CLI, admin notice and log.
 */
class Query_Detector {
	/**
	 * Hook the detector into shutdown.
	 */
	public function __construct() {
		if ( defined( 'PEST_RUNNING' ) || defined( 'PHPUNIT_COMPOSER_INSTALL' ) || class_exists( \Pest\TestSuite::class, false ) ) {
			return;
		}

		// A SAVEQUERIES already defined as false is respected; redefining it would only warn.
		if ( ! defined( 'SAVEQUERIES' ) ) {
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

		$threshold  = (int) apply_filters( 'wp_debugger_n1_threshold', 5 );
		$slow_limit = (float) apply_filters( 'wp_debugger_slow_query_threshold', 0.05 );
		$needles    = array_filter( (array) apply_filters( 'wp_debugger_n1_caller_filter', '' ), 'strlen' );
		$groups     = array();
		$slow       = array();

		foreach ( $wpdb->queries as $query ) {
			list( $sql, $time, $caller ) = $query;

			if ( $needles && ! array_filter( $needles, static fn( $needle ) => str_contains( $caller, (string) $needle ) ) ) {
				continue;
			}

			if ( $time >= $slow_limit ) {
				$slow[] = compact( 'time', 'sql', 'caller' );
			}

			// Group by query shape so a caller running N different queries is not mistaken for N+1.
			$shape = $this->normalize_sql( $sql );
			$key   = $caller . "\n" . $shape;

			$groups[ $key ] ??= array(
				'count'  => 0,
				'time'   => 0.0,
				'sql'    => $shape,
				'sample' => $sql,
				'caller' => $caller,
			);
			++$groups[ $key ]['count'];
			$groups[ $key ]['time'] += $time;
		}

		$flagged = array_filter( $groups, static fn( $item ) => $item['count'] >= $threshold );

		if ( ! $flagged && ! $slow ) {
			return;
		}

		uasort( $flagged, static fn( $a, $b ) => $b['count'] <=> $a['count'] );
		usort( $slow, static fn( $a, $b ) => $b['time'] <=> $a['time'] );

		( new Log( 'query-detector.log' ) )->write(
			array(
				'n1'   => array_values( $flagged ),
				'slow' => $slow,
			)
		);

		if ( is_terminal_request() ) {
			$this->report_cli( $flagged, $slow );
			return;
		}

		// Shutdown runs after admin_notices, so park the summary for the next admin load.
		set_transient( 'wp_debugger_n1_notice', array( count( $flagged ), count( $slow ) ), MINUTE_IN_SECONDS );
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

		printf(
			'<div class="notice notice-warning"><p><strong>WP Debugger:</strong> %d repeated query pattern(s), %d slow query(s). See logs/query-detector.log for details.</p></div>',
			(int) $counts[0],
			(int) $counts[1]
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
		return (string) preg_replace(
			array( '/\s+/', '/\'[^\']*\'|"[^"]*"/', '/\b\d+\b/', '/\?(\s*,\s*\?)+/' ),
			array( ' ', '?', '?', '?' ),
			trim( $sql )
		);
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
		$sql = (string) preg_replace( '/\s+/', ' ', trim( $sql ) );

		return strlen( $sql ) > 160 ? substr( $sql, 0, 157 ) . '...' : $sql;
	}
}
