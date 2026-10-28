<?php
/**
 * File logger that empties the file once it exceeds a size limit.
 *
 * @package DevKabir\WPDebugger
 */

namespace DevKabir\WPDebugger;

/**
 * Class Log
 *
 * Writes to wp-content/logs/<file>. Used by write_log() (0-debugger.log), Http (<host>-requests.log,
 * <host>-errors.log) and Query_Detector (query-detector.log). No .1/.2 backups by design.
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
	 * Set up the logger. Callers create a new instance per write; holds no state besides the path.
	 *
	 * @param string $file_path     File name inside wp-content/logs.
	 * @param int    $max_file_size Maximum file size in bytes before the file is emptied.
	 */
	public function __construct( string $file_path = '0-debugger.log', private int $max_file_size = 1048576 ) {
		$this->log_file = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $file_path;
	}

	/**
	 * Appends "[gmt time] [LEVEL]: <message>". Unknown levels become INFO. Callers must keep messages small;
	 * this method does not truncate.
	 *
	 * @param mixed  $message The message to log. Formatted by debugger_format_variable() (functions.php): arrays and JSON strings pretty-printed, objects reduced to class name.
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
	 * Empties the log file once it reaches the maximum file size. Runs before each write. Keeps no backups.
	 *
	 * @return void
	 */
	private function rotate_log_file() {
		if ( file_exists( $this->log_file ) && filesize( $this->log_file ) >= $this->max_file_size ) {
			$this->clear_log();
		}
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
