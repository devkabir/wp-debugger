<?php
/**
 * Log Class with Singleton Pattern
 *
 * Handles logging with support for log rotation and ensures a single instance of the logger.
 *
 * @package MyPluginNamespace
 */

namespace DevKabir\WPDebugger;

use Exception;

/**
 * Class Log
 */
class Log {

	/**
	 * The single instance of the Log class.
	 *
	 * @var Log|null
	 */
	private static ?Log $instance = null;

	/**
	 * Path to the log file.
	 *
	 * @var string
	 */
	private string $log_file;

	/**
	 * Maximum file size in bytes before rotation.
	 *
	 * @var int
	 */
	private int $max_file_size;

	/**
	 * Number of backup files to keep.
	 *
	 * @var int
	 */
	private int $backup_files;

	/**
	 * Supported log levels.
	 *
	 * @var array
	 */
	private array $log_levels = array( 'DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL' );

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @param string $file_path Path to the log file.
	 * @param int    $max_file_size Maximum file size in bytes before rotation.
	 * @param int    $backup_files Number of backup files to keep.
	 */
	private function __construct( string $file_path = '0-debugger.log', int $max_file_size = 1048576, int $backup_files = 5 ) {
		$this->log_file      = WP_CONTENT_DIR . '/' . $file_path;
		$this->max_file_size = $max_file_size;
		$this->backup_files  = $backup_files;
	}

	/**
	 * Retrieves the single instance of the Log class.
	 *
	 * @return Log The single instance of the Log class.
	 */
	public static function get_instance(): ?Log {
		if ( null === self::$instance ) {
			self::$instance = new self( ...func_get_args() );
		}

		return self::$instance;
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

		if ( ! in_array( $level, $this->log_levels, true ) ) {
			$level = 'INFO';
		}

		$timestamp = gmdate( 'Y-m-d H:i:s' );

		$log_entry = sprintf(
			'[%s] [%s]: %s%s',
			$timestamp,
			$level,
			$this->format( $message ),
			PHP_EOL
		);

		// Rotate the log file if necessary.
		$this->rotate_log_file();

		// Write the log entry to the file.
		file_put_contents( $this->log_file, $log_entry, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Formats a message with the current timestamp for logging.
	 *
	 * @param mixed $message The message to be formatted.
	 *
	 * @return string The formatted message with the timestamp.
	 */
	public function format( $message ): string {
		if ( is_array( $message ) ) {
			$message = wp_json_encode( $message, 128 );
		} elseif ( is_object( $message ) ) {
			$message = get_class( $message );
		} elseif ( is_string( $message ) ) {
			$decoded = json_decode( $message, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				$message = wp_json_encode( $decoded, 128 );
			}
		}

		return $message;
	}

	/**
	 * Rotates the log file if it exceeds the maximum file size.
	 *
	 * @return void
	 */
	private function rotate_log_file() {
		if ( ! file_exists( $this->log_file ) ) {
			return;
		}
		if ( filesize( $this->log_file ) >= $this->max_file_size ) {
			// Remove the oldest backup file if it exists.
			$oldest_backup = $this->log_file . '.' . $this->backup_files;
			if ( file_exists( $oldest_backup ) ) {
				unlink( $oldest_backup );
			}

			// Shift existing backup files up by one.
			for ( $i = $this->backup_files - 1; $i >= 1; $i-- ) {
				$current_backup = $this->log_file . '.' . $i;
				$next_backup    = $this->log_file . '.' . ( $i + 1 );
				if ( file_exists( $current_backup ) ) {
					rename( $current_backup, $next_backup );
				}
			}

			// Rename the current log file to the first backup.
			$first_backup = $this->log_file . '.1';
			rename( $this->log_file, $first_backup );

			// Create a new empty log file.
			touch( $this->log_file );
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
