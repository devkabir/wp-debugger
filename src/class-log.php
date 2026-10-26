<?php
/**
 * File logger with size-based rotation.
 *
 * @package DevKabir\WPDebugger
 */

namespace DevKabir\WPDebugger;

/**
 * Class Log
 */
class Log {

	/**
	 * Supported log levels.
	 */
	private const LEVELS = array( 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' );

	/**
	 * Path to the log file.
	 *
	 * @var string
	 */
	private string $log_file;

	/**
	 * Set up the logger.
	 *
	 * @param string $file_path     File name inside wp-content/logs.
	 * @param int    $max_file_size Maximum file size in bytes before rotation.
	 * @param int    $backup_files  Number of backup files to keep.
	 */
	public function __construct( string $file_path = '0-debugger.log', private int $max_file_size = 1048576, private int $backup_files = 5 ) {
		$this->log_file = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $file_path;
	}

	/**
	 * Logs a message.
	 *
	 * @param mixed  $message The message to log.
	 * @param string $level The log level (DEBUG, INFO, WARNING, ERROR, CRITICAL).
	 *
	 * @return void
	 */
	public function write( $message, string $level = 'INFO' ) {
		$level = strtoupper( $level );
		$level = in_array( $level, self::LEVELS, true ) ? $level : 'INFO';

		$this->rotate_log_file();

		if ( ! is_dir( dirname( $this->log_file ) ) ) {
			mkdir( dirname( $this->log_file ), 0775, true );
		}

		file_put_contents( $this->log_file, sprintf( '[%s] [%s]: %s%s', gmdate( 'Y-m-d H:i:s' ), $level, debugger_format_variable( $message ), PHP_EOL ), FILE_APPEND | LOCK_EX );
	}

	/**
	 * Rotates the log file if it exceeds the maximum file size: log => log.1 => log.2 ... and the oldest is overwritten.
	 *
	 * @return void
	 */
	private function rotate_log_file() {
		if ( ! file_exists( $this->log_file ) || filesize( $this->log_file ) < $this->max_file_size ) {
			return;
		}

		// rename() replaces an existing target, so the oldest backup is dropped by the first shift.
		for ( $i = $this->backup_files - 1; $i >= 1; $i-- ) {
			if ( file_exists( "{$this->log_file}.{$i}" ) ) {
				rename( "{$this->log_file}.{$i}", "{$this->log_file}." . ( $i + 1 ) );
			}
		}

		rename( $this->log_file, "{$this->log_file}.1" );
	}

	/**
	 * Clears the log file.
	 *
	 * @return void
	 */
	public function clear_log() {
		file_put_contents( $this->log_file, '' );
	}
}
